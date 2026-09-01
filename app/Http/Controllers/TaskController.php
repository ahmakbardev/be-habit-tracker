<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\NoteFolder;
use App\Models\NoteWorkspace;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAttachment;
use App\Models\TaskColumn;
use App\Models\TaskComment;
use App\Models\TaskNote;
use App\Models\TaskProject;
use App\Models\TaskSubtask;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    /**
     * Find a Project by id, scoped to projects whose parent Workspace
     * belongs to the given user. Returns null if it doesn't exist or
     * isn't owned by that user.
     */
    private function findUserProject(string $projectId, int $userId): ?TaskProject
    {
        return TaskProject::where('id', $projectId)
            ->whereHas('workspace', fn ($q) => $q->where('user_id', $userId))
            ->first();
    }

    /**
     * Find a Task by id, scoped to tasks whose parent Project's Workspace
     * belongs to the given user. Returns null if it doesn't exist or
     * isn't owned by that user.
     */
    private function findUserTask(string $taskId, int $userId): ?Task
    {
        $task = Task::find($taskId);
        if (! $task || ! $this->findUserProject($task->project_id, $userId)) {
            return null;
        }

        return $task;
    }

    /**
     * Find a Note by id, scoped to notes whose parent Folder's Workspace
     * belongs to the given user. Mirrors NoteController::findUserNote.
     */
    private function findUserNote(string $noteId, int $userId): ?Note
    {
        $note = Note::find($noteId);
        if (! $note) {
            return null;
        }
        $folder = NoteFolder::where('id', $note->folder_id)
            ->whereHas('workspace', fn ($q) => $q->where('user_id', $userId))
            ->first();

        return $folder ? $note : null;
    }

    private function loadTask(Task $task): Task
    {
        $task->load(['subtasks.assignee', 'attachments', 'comments.user', 'activities', 'assignees']);
        $this->attachTaskNotes(collect([$task]));

        return $task;
    }

    /**
     * Recompute a task's progress from its subtasks' completion ratio.
     * A task with no subtasks keeps whatever progress was set manually —
     * subtasks only take over once they exist.
     */
    private function syncProgressFromSubtasks(Task $task): void
    {
        $total = $task->subtasks()->count();
        if ($total === 0) {
            return;
        }

        $completed = $task->subtasks()->where('completed', true)->count();
        $progress = (int) round(($completed / $total) * 100);

        if ($progress !== (int) $task->progress) {
            $task->update(['progress' => $progress]);
        }
    }

    /**
     * Resolve each task's linked Notes and set them as a 'notes' attribute.
     * Notes live in MongoDB, so this can't be a normal Eloquent eager load
     * (no cross-database joins) — pivot rows are read from MySQL, then the
     * actual Note documents and their parent Folder (for workspace_id) are
     * bulk-fetched separately and stitched together in PHP.
     */
    private function attachTaskNotes(Collection $tasks): void
    {
        if ($tasks->isEmpty()) {
            return;
        }

        $pivotsByTask = TaskNote::whereIn('task_id', $tasks->pluck('id'))->get()->groupBy('task_id');

        $noteIds = $pivotsByTask->flatten()->pluck('note_id')->unique()->values();
        $notes = $noteIds->isEmpty() ? collect() : Note::whereIn('id', $noteIds)->get()->keyBy('id');

        $folderIds = $notes->pluck('folder_id')->filter()->unique()->values();
        $folders = $folderIds->isEmpty() ? collect() : NoteFolder::whereIn('id', $folderIds)->get()->keyBy('id');

        $tasks->each(function (Task $task) use ($pivotsByTask, $notes, $folders) {
            $rows = $pivotsByTask->get($task->id, collect());
            $resolved = $rows->map(function (TaskNote $pivot) use ($notes, $folders) {
                $note = $notes->get($pivot->note_id);
                if (! $note) {
                    return null; // dangling reference — the note was deleted
                }
                $folder = $folders->get($note->folder_id);

                return [
                    'id' => $pivot->id,
                    'note_id' => $note->id,
                    'title' => $note->title,
                    'folder_id' => $note->folder_id,
                    'workspace_id' => $folder?->workspace_id,
                ];
            })->filter()->values();
            $task->setAttribute('notes', $resolved);
        });
    }

    /**
     * Confirm a column belongs to the given project. Without this, a
     * column_id from an unrelated (possibly another user's) project would
     * be accepted since it only needs to exist in task_columns.
     */
    private function columnBelongsToProject(string $columnId, string $projectId): bool
    {
        return TaskColumn::where('id', $columnId)->where('project_id', $projectId)->exists();
    }

    /**
     * List Task Projects for a given Workspace.
     */
    public function index(Request $request)
    {
        try {
            $workspaceId = $request->query('workspace_id');
            if (! $workspaceId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'workspace_id is required.',
                ], 422);
            }

            $workspace = NoteWorkspace::where('id', $workspaceId)
                ->where('user_id', $request->user()->id)
                ->first();

            if (! $workspace) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Workspace not found.',
                ], 404);
            }

            $projects = TaskProject::where('workspace_id', $workspaceId)
                ->withCount('tasks')
                ->orderBy('order_index')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $projects,
            ]);
        } catch (Exception $e) {
            Log::error('Tasks Index Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch task projects.',
            ], 500);
        }
    }

    /**
     * Column titles treated as "done" for the purposes of the cross-project
     * active-tasks overview. Matches the default "Done" column created for
     * every new project; a column with a different title but the same intent
     * (renamed by the user) is matched case-insensitively.
     */
    private const DONE_COLUMN_TITLES = ['done', 'completed', 'complete', 'finished', 'selesai', 'closed', 'cancelled', 'canceled'];

    /**
     * Flat list of not-yet-done tasks across every Project in every
     * Workspace owned by the user — powers the "quick board" shortcut shown
     * on the Tasks index before a workspace/project is picked. Each task is
     * annotated with its parent project & workspace so the UI can flag them.
     */
    public function activeOverview(Request $request)
    {
        try {
            $userId = $request->user()->id;

            $projects = TaskProject::whereHas('workspace', fn ($q) => $q->where('user_id', $userId))
                ->with([
                    'workspace:id,name,icon_name',
                    'columns' => fn ($q) => $q->orderBy('order_index'),
                    'columns.tasks' => fn ($q) => $q->orderBy('order_index'),
                    'columns.tasks.assignees',
                ])
                ->get();

            $tasks = collect();
            foreach ($projects as $project) {
                foreach ($project->columns as $column) {
                    if (in_array(strtolower(trim($column->title)), self::DONE_COLUMN_TITLES, true)) {
                        continue;
                    }
                    foreach ($column->tasks as $task) {
                        $tasks->push([
                            'id' => $task->id,
                            'title' => $task->title,
                            'description' => $task->description,
                            'priority' => $task->priority,
                            'start_date' => $task->start_date,
                            'due_date' => $task->due_date,
                            'tags' => $task->tags,
                            'column_id' => $column->id,
                            'column_title' => $column->title,
                            'project_id' => $project->id,
                            'project_name' => $project->name,
                            'project_icon_name' => $project->icon_name,
                            'workspace_id' => $project->workspace_id,
                            'workspace_name' => $project->workspace?->name,
                            'workspace_icon_name' => $project->workspace?->icon_name,
                            'assignees' => $task->assignees->map(fn ($u) => [
                                'id' => $u->id,
                                'name' => $u->name,
                                'avatar_url' => $u->avatar_url,
                            ])->values(),
                        ]);
                    }
                }
            }

            $sorted = $tasks->sortBy(fn ($t) => $t['due_date']?->timestamp ?? PHP_INT_MAX)->values();

            return response()->json([
                'status' => 'success',
                'data' => $sorted,
            ]);
        } catch (Exception $e) {
            Log::error('Tasks Active Overview Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch active tasks overview.',
            ], 500);
        }
    }

    /**
     * Get project detail with columns and tasks (Kanban)
     */
    public function showProject(Request $request, $id)
    {
        try {
            $project = $this->findUserProject($id, $request->user()->id);

            if (! $project) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Project not found.',
                ], 404);
            }

            $project->load(['columns' => function ($q) {
                $q->orderBy('order_index');
            }, 'columns.tasks' => function ($q) {
                $q->orderBy('order_index');
            }, 'columns.tasks.subtasks.assignee', 'columns.tasks.attachments', 'columns.tasks.comments.user', 'columns.tasks.activities', 'columns.tasks.assignees']);

            $this->attachTaskNotes($project->columns->flatMap->tasks);

            return response()->json([
                'status' => 'success',
                'data' => $project,
            ]);
        } catch (Exception $e) {
            Log::error('Show Project Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch project detail.',
            ], 500);
        }
    }

    /**
     * Store new Project (with default columns)
     */
    public function storeProject(Request $request)
    {
        return DB::transaction(function () use ($request) {
            try {
                $validated = $request->validate([
                    'workspace_id' => 'required|uuid|exists:note_workspaces,id',
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'icon_name' => 'nullable|string|max:50',
                ]);

                $workspace = NoteWorkspace::where('id', $validated['workspace_id'])
                    ->where('user_id', $request->user()->id)
                    ->first();
                if (! $workspace) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Workspace not found.',
                    ], 404);
                }

                $project = TaskProject::create($validated);

                $defaults = ['To Do', 'In Progress', 'Done'];
                foreach ($defaults as $index => $title) {
                    TaskColumn::create([
                        'project_id' => $project->id,
                        'title' => $title,
                        'order_index' => $index,
                    ]);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Project created with default columns',
                    'data' => $project->load('columns'),
                ], 201);
            } catch (ValidationException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            } catch (Exception $e) {
                Log::error('Store Project Error: '.$e->getMessage());

                return response()->json([
                    'status' => 'error',
                    'message' => 'Could not create project.',
                ], 500);
            }
        });
    }

    /**
     * Update a Project
     */
    public function updateProject(Request $request, $id)
    {
        try {
            $project = $this->findUserProject($id, $request->user()->id);
            if (! $project) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Project not found.',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|nullable|string',
                'icon_name' => 'sometimes|nullable|string|max:50',
                'status' => 'sometimes|in:planning,active,on-hold,completed',
                'start_date' => 'sometimes|nullable|date',
                'end_date' => 'sometimes|nullable|date',
                'metadata' => 'sometimes|nullable|array',
                'order_index' => 'sometimes|integer',
            ]);

            $project->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Project updated successfully',
                'data' => $project,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Update Project Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update project.',
            ], 500);
        }
    }

    /**
     * Delete a Project
     */
    public function destroyProject(Request $request, $id)
    {
        try {
            $project = $this->findUserProject($id, $request->user()->id);
            if (! $project) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Project not found.',
                ], 404);
            }

            $project->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Project deleted successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Destroy Project Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete project.',
            ], 500);
        }
    }

    /**
     * Store new Task
     */
    public function storeTask(Request $request)
    {
        try {
            $validated = $request->validate([
                'project_id' => 'required|uuid|exists:task_projects,id',
                'column_id' => 'required|uuid|exists:task_columns,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'priority' => 'nullable|in:low,medium,high',
                'start_date' => 'nullable|date',
                'due_date' => 'nullable|date',
                'tags' => 'nullable|array',
            ]);

            $project = $this->findUserProject($validated['project_id'], $request->user()->id);
            if (! $project) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Project not found.',
                ], 404);
            }

            if (! $this->columnBelongsToProject($validated['column_id'], $project->id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Column does not belong to this project.',
                ], 422);
            }

            $task = Task::create($validated);
            TaskActivity::create(['task_id' => $task->id, 'message' => 'Task created']);

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Store Task Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Could not create task.',
            ], 500);
        }
    }

    /**
     * Update a Task — also logs a human-readable activity entry for
     * every watched field that actually changed.
     */
    public function updateTask(Request $request, $id)
    {
        try {
            $task = $this->findUserTask($id, $request->user()->id);
            if (! $task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.',
                ], 404);
            }

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|nullable|string',
                'priority' => 'sometimes|in:low,medium,high',
                'column_id' => 'sometimes|uuid|exists:task_columns,id',
                'start_date' => 'sometimes|nullable|date',
                'due_date' => 'sometimes|nullable|date',
                'tags' => 'sometimes|nullable|array',
                'progress' => 'sometimes|integer|min:0|max:100',
                'order_index' => 'sometimes|integer',
            ]);

            if (array_key_exists('column_id', $validated) && ! $this->columnBelongsToProject($validated['column_id'], $task->project_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Column does not belong to this task\'s project.',
                ], 422);
            }

            // Once a task has subtasks, its progress is derived from their
            // completion ratio (see syncProgressFromSubtasks) — manual
            // overrides are only allowed before any subtask exists.
            if (array_key_exists('progress', $validated) && $task->subtasks()->count() > 0) {
                unset($validated['progress']);
            }

            $this->logFieldChanges($task, $validated);

            $task->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Task updated successfully',
                'data' => $this->loadTask($task),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Update Task Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update task.',
            ], 500);
        }
    }

    private function logFieldChanges(Task $task, array $validated): void
    {
        $messages = [];

        if (array_key_exists('title', $validated) && $validated['title'] !== $task->title) {
            $messages[] = "Title changed to \"{$validated['title']}\"";
        }
        if (array_key_exists('priority', $validated) && $validated['priority'] !== $task->priority) {
            $messages[] = 'Priority changed to '.ucfirst($validated['priority']);
        }
        if (array_key_exists('column_id', $validated) && $validated['column_id'] !== $task->column_id) {
            $columnTitle = TaskColumn::find($validated['column_id'])?->title ?? 'Unknown';
            $messages[] = "Status changed to \"{$columnTitle}\"";
        }
        if (array_key_exists('due_date', $validated) && $validated['due_date'] !== optional($task->due_date)->toDateTimeString()) {
            $messages[] = $validated['due_date']
                ? 'Due date set to '.Carbon::parse($validated['due_date'])->format('M j, Y g:i A')
                : 'Due date removed';
        }
        if (array_key_exists('start_date', $validated) && $validated['start_date'] !== optional($task->start_date)->toDateTimeString()) {
            $messages[] = $validated['start_date']
                ? 'Start time set to '.Carbon::parse($validated['start_date'])->format('M j, Y g:i A')
                : 'Start time removed';
        }
        if (array_key_exists('progress', $validated) && (int) $validated['progress'] !== (int) $task->progress) {
            $messages[] = "Progress updated to {$validated['progress']}%";
        }

        foreach ($messages as $message) {
            TaskActivity::create(['task_id' => $task->id, 'message' => $message]);
        }
    }

    /**
     * Delete a Task
     */
    public function destroyTask(Request $request, $id)
    {
        try {
            $task = $this->findUserTask($id, $request->user()->id);
            if (! $task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.',
                ], 404);
            }

            $task->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Task deleted successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Destroy Task Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete task.',
            ], 500);
        }
    }

    /**
     * Reorder tasks (Same column or cross column)
     */
    public function reorderTasks(Request $request)
    {
        try {
            $validated = $request->validate([
                'tasks' => 'required|array',
                'tasks.*.id' => 'required|uuid|exists:tasks,id',
                'tasks.*.column_id' => 'required|uuid|exists:task_columns,id',
                'tasks.*.order_index' => 'required|integer',
            ]);

            $userId = $request->user()->id;

            foreach ($validated['tasks'] as $taskData) {
                $task = $this->findUserTask($taskData['id'], $userId);
                if (! $task || ! $this->columnBelongsToProject($taskData['column_id'], $task->project_id)) {
                    continue;
                }
                $task->update([
                    'column_id' => $taskData['column_id'],
                    'order_index' => $taskData['order_index'],
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Tasks reordered successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Reorder Tasks Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reorder tasks.',
            ], 500);
        }
    }

    /**
     * Add a Subtask to a Task
     */
    public function storeSubtask(Request $request, $taskId)
    {
        try {
            $task = $this->findUserTask($taskId, $request->user()->id);
            if (! $task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.',
                ], 404);
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'priority' => 'nullable|in:low,medium,high',
                'due_date' => 'nullable|date',
                'assignee_id' => 'nullable|exists:users,id',
            ]);

            $subtask = TaskSubtask::create([
                'task_id' => $task->id,
                'title' => $validated['title'],
                'priority' => $validated['priority'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'assignee_id' => $validated['assignee_id'] ?? null,
                'order_index' => $task->subtasks()->count(),
            ]);

            TaskActivity::create(['task_id' => $task->id, 'message' => "Subtask \"{$subtask->title}\" added"]);
            $this->syncProgressFromSubtasks($task);

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Store Subtask Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Could not create subtask.',
            ], 500);
        }
    }

    /**
     * Update a Subtask (rename or toggle completed)
     */
    public function updateSubtask(Request $request, $id)
    {
        try {
            $subtask = TaskSubtask::find($id);
            $task = $subtask ? $this->findUserTask($subtask->task_id, $request->user()->id) : null;
            if (! $subtask || ! $task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Subtask not found.',
                ], 404);
            }

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'completed' => 'sometimes|boolean',
                'priority' => 'sometimes|nullable|in:low,medium,high',
                'due_date' => 'sometimes|nullable|date',
                'assignee_id' => 'sometimes|nullable|exists:users,id',
            ]);

            $subtask->update($validated);

            if (array_key_exists('completed', $validated)) {
                $state = $validated['completed'] ? 'complete' : 'incomplete';
                TaskActivity::create(['task_id' => $task->id, 'message' => "Subtask \"{$subtask->title}\" marked {$state}"]);
            }
            $this->syncProgressFromSubtasks($task);

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Update Subtask Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update subtask.',
            ], 500);
        }
    }

    /**
     * Delete a Subtask
     */
    public function destroySubtask(Request $request, $id)
    {
        try {
            $subtask = TaskSubtask::find($id);
            $task = $subtask ? $this->findUserTask($subtask->task_id, $request->user()->id) : null;
            if (! $subtask || ! $task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Subtask not found.',
                ], 404);
            }

            $title = $subtask->title;
            $subtask->delete();
            TaskActivity::create(['task_id' => $task->id, 'message' => "Subtask \"{$title}\" removed"]);
            $this->syncProgressFromSubtasks($task);

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task),
            ]);
        } catch (Exception $e) {
            Log::error('Destroy Subtask Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete subtask.',
            ], 500);
        }
    }

    /**
     * Attach an existing Note (from the same Workspace) to a Task.
     */
    public function storeTaskNote(Request $request, $taskId)
    {
        try {
            $task = $this->findUserTask($taskId, $request->user()->id);
            if (! $task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.',
                ], 404);
            }

            $validated = $request->validate([
                'note_id' => 'required|string',
            ]);

            $note = $this->findUserNote($validated['note_id'], $request->user()->id);
            if (! $note) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Note not found.',
                ], 404);
            }

            $taskNote = TaskNote::firstOrCreate([
                'task_id' => $task->id,
                'note_id' => $note->id,
            ]);

            if ($taskNote->wasRecentlyCreated) {
                TaskActivity::create(['task_id' => $task->id, 'message' => "Note \"{$note->title}\" attached"]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Store Task Note Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Could not attach note.',
            ], 500);
        }
    }

    /**
     * Detach a Note from a Task (does not delete the Note itself).
     */
    public function destroyTaskNote(Request $request, $id)
    {
        try {
            $taskNote = TaskNote::find($id);
            $task = $taskNote ? $this->findUserTask($taskNote->task_id, $request->user()->id) : null;
            if (! $taskNote || ! $task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Linked note not found.',
                ], 404);
            }

            $note = Note::find($taskNote->note_id);
            $taskNote->delete();
            if ($note) {
                TaskActivity::create(['task_id' => $task->id, 'message' => "Note \"{$note->title}\" removed"]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task),
            ]);
        } catch (Exception $e) {
            Log::error('Destroy Task Note Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove note.',
            ], 500);
        }
    }

    /**
     * Promote a Subtask into a standalone Task in the same Project (first
     * column), carrying over its title/priority/due date/assignee, then
     * remove the subtask.
     */
    public function convertSubtask(Request $request, $id)
    {
        try {
            $subtask = TaskSubtask::find($id);
            $task = $subtask ? $this->findUserTask($subtask->task_id, $request->user()->id) : null;
            if (! $subtask || ! $task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Subtask not found.',
                ], 404);
            }

            $firstColumn = TaskColumn::where('project_id', $task->project_id)
                ->orderBy('order_index')
                ->first();
            if (! $firstColumn) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This project has no columns to place the new task in.',
                ], 422);
            }

            $newTask = Task::create([
                'project_id' => $task->project_id,
                'column_id' => $firstColumn->id,
                'title' => $subtask->title,
                'priority' => $subtask->priority ?? 'medium',
                'due_date' => $subtask->due_date,
                'order_index' => Task::where('column_id', $firstColumn->id)->count(),
            ]);
            TaskActivity::create(['task_id' => $newTask->id, 'message' => "Created by converting subtask from \"{$task->title}\""]);

            if ($subtask->assignee_id) {
                $newTask->assignees()->attach($subtask->assignee_id);
            }

            $title = $subtask->title;
            $subtask->delete();
            TaskActivity::create(['task_id' => $task->id, 'message' => "Subtask \"{$title}\" converted to a task"]);
            $this->syncProgressFromSubtasks($task);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'task' => $this->loadTask($task),
                    'created_task' => $this->loadTask($newTask),
                ],
            ], 201);
        } catch (Exception $e) {
            Log::error('Convert Subtask Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to convert subtask.',
            ], 500);
        }
    }

    /**
     * Add a Comment to a Task
     */
    public function storeComment(Request $request, $taskId)
    {
        try {
            $task = $this->findUserTask($taskId, $request->user()->id);
            if (! $task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.',
                ], 404);
            }

            $validated = $request->validate([
                'body' => 'nullable|string',
                'image_url' => 'nullable|string',
                'image_name' => 'nullable|string',
            ]);

            if (empty($validated['body']) && empty($validated['image_url'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Comment must have text or an image.',
                ], 422);
            }

            $comment = TaskComment::create([
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'body' => $validated['body'] ?? '',
                'image_url' => $validated['image_url'] ?? null,
                'image_name' => $validated['image_name'] ?? null,
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Store Comment Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Could not create comment.',
            ], 500);
        }
    }

    /**
     * Update a Comment (toggle pin)
     */
    public function updateComment(Request $request, $id)
    {
        try {
            $comment = TaskComment::find($id);
            $task = $comment ? $this->findUserTask($comment->task_id, $request->user()->id) : null;
            if (! $comment || ! $task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Comment not found.',
                ], 404);
            }

            $validated = $request->validate([
                'pinned' => 'sometimes|boolean',
            ]);

            $comment->update($validated);

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Update Comment Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update comment.',
            ], 500);
        }
    }

    /**
     * Delete a Comment
     */
    public function destroyComment(Request $request, $id)
    {
        try {
            $comment = TaskComment::find($id);
            $task = $comment ? $this->findUserTask($comment->task_id, $request->user()->id) : null;
            if (! $comment || ! $task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Comment not found.',
                ], 404);
            }

            $comment->delete();

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task),
            ]);
        } catch (Exception $e) {
            Log::error('Destroy Comment Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete comment.',
            ], 500);
        }
    }

    /**
     * Record a Task Attachment (the file itself is uploaded beforehand
     * via the shared media upload endpoint)
     */
    public function storeAttachment(Request $request, $taskId)
    {
        try {
            $task = $this->findUserTask($taskId, $request->user()->id);
            if (! $task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                // Only http(s) is allowed — anything else (javascript:, data:, etc.)
                // would execute in the app's own origin when a viewer opens it later.
                'url' => 'required|string|regex:/^https?:\/\//i',
                'type' => 'nullable|string|in:file,url',
                'extension' => 'nullable|string|max:20',
                'size' => 'nullable|integer',
            ]);

            $validated['type'] = $validated['type'] ?? 'file';

            TaskAttachment::create(array_merge($validated, ['task_id' => $task->id]));
            TaskActivity::create(['task_id' => $task->id, 'message' => "Attachment \"{$validated['name']}\" added"]);

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Store Attachment Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Could not save attachment.',
            ], 500);
        }
    }

    /**
     * Delete a Task Attachment
     */
    public function destroyAttachment(Request $request, $id)
    {
        try {
            $attachment = TaskAttachment::find($id);
            $task = $attachment ? $this->findUserTask($attachment->task_id, $request->user()->id) : null;
            if (! $attachment || ! $task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Attachment not found.',
                ], 404);
            }

            $name = $attachment->name;
            $attachment->delete();
            TaskActivity::create(['task_id' => $task->id, 'message' => "Attachment \"{$name}\" removed"]);

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task),
            ]);
        } catch (Exception $e) {
            Log::error('Destroy Attachment Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete attachment.',
            ], 500);
        }
    }

    /**
     * Assign a user to a Task
     */
    public function addAssignee(Request $request, $taskId)
    {
        try {
            $task = $this->findUserTask($taskId, $request->user()->id);
            if (! $task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.',
                ], 404);
            }

            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            $task->assignees()->syncWithoutDetaching([$validated['user_id']]);
            $assignee = $task->assignees()->find($validated['user_id']);
            if ($assignee) {
                TaskActivity::create(['task_id' => $task->id, 'message' => "{$assignee->name} added as assignee"]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Add Assignee Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add assignee.',
            ], 500);
        }
    }

    /**
     * Remove a user from a Task's assignees
     */
    public function removeAssignee(Request $request, $taskId, $userId)
    {
        try {
            $task = $this->findUserTask($taskId, $request->user()->id);
            if (! $task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.',
                ], 404);
            }

            $assignee = $task->assignees()->find($userId);
            $task->assignees()->detach($userId);
            if ($assignee) {
                TaskActivity::create(['task_id' => $task->id, 'message' => "{$assignee->name} removed from assignees"]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task),
            ]);
        } catch (Exception $e) {
            Log::error('Remove Assignee Error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove assignee.',
            ], 500);
        }
    }
}
