<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskColumn;
use App\Models\TaskFolder;
use App\Models\TaskProject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class TaskController extends Controller
{
    /**
     * Get all task folders and projects
     */
    public function index()
    {
        try {
            $user = User::first();
            $folders = TaskFolder::where('user_id', $user->id)
                ->with('projects')
                ->orderBy('order_index')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $folders
            ]);
        } catch (Exception $e) {
            Log::error('Tasks Index Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch task structure.'
            ], 500);
        }
    }

    /**
     * Get project detail with columns and tasks (Kanban)
     */
    public function showProject($id)
    {
        try {
            $project = TaskProject::with(['columns' => function($q) {
                $q->orderBy('order_index');
            }, 'columns.tasks' => function($q) {
                $q->orderBy('order_index');
            }])->find($id);

            if (!$project) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Project not found.'
                ], 404);
            }

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
                    'folder_id' => 'required|uuid|exists:task_folders,id',
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'icon_name' => 'nullable|string|max:50',
                ]);

                $project = TaskProject::create($validated);

                // Create default columns
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
                'due_date' => 'nullable|date',
            ]);

            $task = Task::create($validated);

            return response()->json([
                'status' => 'success',
                'data' => $task
            ], 201);
        } catch (Exception $e) {
            Log::error('Store Task Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Could not create task.'
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

            foreach ($validated['tasks'] as $taskData) {
                Task::where('id', $taskData['id'])->update([
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
     * Store new Folder
     */
    public function storeFolder(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'icon_name' => 'nullable|string|max:50',
            ]);

            $user = User::first();
            $folder = TaskFolder::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'icon_name' => $validated['icon_name'] ?? 'folder',
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $folder
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not create folder.'
            ], 500);
        }
    }
}
