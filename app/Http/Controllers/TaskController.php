<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAttachment;
use App\Models\TaskColumn;
use App\Models\TaskComment;
use App\Models\TaskProject;
use App\Models\TaskSubtask;
use App\Models\NoteWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

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
            ->whereHas('workspace', fn($q) => $q->where('user_id', $userId))
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
        if (!$task || !$this->findUserProject($task->project_id, $userId)) {
            return null;
        }
        return $task;
    }

    private function loadTask(Task $task): Task
    {
        return $task->load(['subtasks', 'attachments', 'comments.user', 'activities', 'assignees']);
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
            if (!$workspaceId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'workspace_id is required.'
                ], 422);
            }

            $workspace = NoteWorkspace::where('id', $workspaceId)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$workspace) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Workspace not found.'
                ], 404);
            }

            $projects = TaskProject::where('workspace_id', $workspaceId)
                ->withCount('tasks')
                ->orderBy('order_index')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $projects
            ]);
        } catch (Exception $e) {
            Log::error('Tasks Index Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch task projects.'
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

            if (!$project) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Project not found.'
                ], 404);
            }

            $project->load(['columns' => function ($q) {
                $q->orderBy('order_index');
            }, 'columns.tasks' => function ($q) {
                $q->orderBy('order_index');
            }, 'columns.tasks.subtasks', 'columns.tasks.attachments', 'columns.tasks.comments.user', 'columns.tasks.activities', 'columns.tasks.assignees']);

            return response()->json([
                'status' => 'success',
                'data' => $project
            ]);
        } catch (Exception $e) {
            Log::error('Show Project Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch project detail.'
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
                if (!$workspace) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Workspace not found.'
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
                    'data' => $project->load('columns')
                ], 201);
            } catch (ValidationException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            } catch (Exception $e) {
                Log::error('Store Project Error: ' . $e->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Could not create project.'
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
            if (!$project) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Project not found.'
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
                'data' => $project
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Update Project Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update project.'
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
            if (!$project) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Project not found.'
                ], 404);
            }

            $project->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Project deleted successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Destroy Project Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete project.'
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
            if (!$project) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Project not found.'
                ], 404);
            }

            if (!$this->columnBelongsToProject($validated['column_id'], $project->id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Column does not belong to this project.'
                ], 422);
            }

            $task = Task::create($validated);
            TaskActivity::create(['task_id' => $task->id, 'message' => 'Task created']);

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task)
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Store Task Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Could not create task.'
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
            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.'
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

            if (array_key_exists('column_id', $validated) && !$this->columnBelongsToProject($validated['column_id'], $task->project_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Column does not belong to this task\'s project.'
                ], 422);
            }

            $this->logFieldChanges($task, $validated);

            $task->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Task updated successfully',
                'data' => $this->loadTask($task)
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Update Task Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update task.'
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
            $messages[] = 'Priority changed to ' . ucfirst($validated['priority']);
        }
        if (array_key_exists('column_id', $validated) && $validated['column_id'] !== $task->column_id) {
            $columnTitle = TaskColumn::find($validated['column_id'])?->title ?? 'Unknown';
            $messages[] = "Status changed to \"{$columnTitle}\"";
        }
        if (array_key_exists('due_date', $validated) && $validated['due_date'] !== optional($task->due_date)->toDateTimeString()) {
            $messages[] = $validated['due_date']
                ? 'Due date set to ' . \Carbon\Carbon::parse($validated['due_date'])->format('M j, Y g:i A')
                : 'Due date removed';
        }
        if (array_key_exists('start_date', $validated) && $validated['start_date'] !== optional($task->start_date)->toDateTimeString()) {
            $messages[] = $validated['start_date']
                ? 'Start time set to ' . \Carbon\Carbon::parse($validated['start_date'])->format('M j, Y g:i A')
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
            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.'
                ], 404);
            }

            $task->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Task deleted successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Destroy Task Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete task.'
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
                if (!$task || !$this->columnBelongsToProject($taskData['column_id'], $task->project_id)) {
                    continue;
                }
                $task->update([
                    'column_id' => $taskData['column_id'],
                    'order_index' => $taskData['order_index'],
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Tasks reordered successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Reorder Tasks Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reorder tasks.'
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
            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.'
                ], 404);
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
            ]);

            $subtask = TaskSubtask::create([
                'task_id' => $task->id,
                'title' => $validated['title'],
                'order_index' => $task->subtasks()->count(),
            ]);

            TaskActivity::create(['task_id' => $task->id, 'message' => "Subtask \"{$subtask->title}\" added"]);

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task)
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Store Subtask Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Could not create subtask.'
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
            if (!$subtask || !$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Subtask not found.'
                ], 404);
            }

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'completed' => 'sometimes|boolean',
            ]);

            $subtask->update($validated);

            if (array_key_exists('completed', $validated)) {
                $state = $validated['completed'] ? 'complete' : 'incomplete';
                TaskActivity::create(['task_id' => $task->id, 'message' => "Subtask \"{$subtask->title}\" marked {$state}"]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task)
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Update Subtask Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update subtask.'
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
            if (!$subtask || !$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Subtask not found.'
                ], 404);
            }

            $title = $subtask->title;
            $subtask->delete();
            TaskActivity::create(['task_id' => $task->id, 'message' => "Subtask \"{$title}\" removed"]);

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task)
            ]);
        } catch (Exception $e) {
            Log::error('Destroy Subtask Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete subtask.'
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
            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.'
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
                    'message' => 'Comment must have text or an image.'
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
                'data' => $this->loadTask($task)
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Store Comment Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Could not create comment.'
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
            if (!$comment || !$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Comment not found.'
                ], 404);
            }

            $validated = $request->validate([
                'pinned' => 'sometimes|boolean',
            ]);

            $comment->update($validated);

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task)
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Update Comment Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update comment.'
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
            if (!$comment || !$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Comment not found.'
                ], 404);
            }

            $comment->delete();

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task)
            ]);
        } catch (Exception $e) {
            Log::error('Destroy Comment Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete comment.'
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
            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'url' => 'required|string',
                'extension' => 'nullable|string|max:20',
                'size' => 'nullable|integer',
            ]);

            TaskAttachment::create(array_merge($validated, ['task_id' => $task->id]));
            TaskActivity::create(['task_id' => $task->id, 'message' => "Attachment \"{$validated['name']}\" added"]);

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task)
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Store Attachment Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Could not save attachment.'
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
            if (!$attachment || !$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Attachment not found.'
                ], 404);
            }

            $name = $attachment->name;
            $attachment->delete();
            TaskActivity::create(['task_id' => $task->id, 'message' => "Attachment \"{$name}\" removed"]);

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task)
            ]);
        } catch (Exception $e) {
            Log::error('Destroy Attachment Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete attachment.'
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
            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.'
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
                'data' => $this->loadTask($task)
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Add Assignee Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add assignee.'
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
            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.'
                ], 404);
            }

            $assignee = $task->assignees()->find($userId);
            $task->assignees()->detach($userId);
            if ($assignee) {
                TaskActivity::create(['task_id' => $task->id, 'message' => "{$assignee->name} removed from assignees"]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $this->loadTask($task)
            ]);
        } catch (Exception $e) {
            Log::error('Remove Assignee Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove assignee.'
            ], 500);
        }
    }
}
