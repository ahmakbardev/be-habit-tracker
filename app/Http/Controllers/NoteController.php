<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\NoteFolder;
use App\Models\NoteWorkspace;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class NoteController extends Controller
{
    /**
     * Get all folders, workspaces, and notes for a user (Optimized)
     */
    public function index()
    {
        try {
            $user = User::first();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User context not found.'
                ], 404);
            }

            // 1. Fetch Folders & Workspaces (MySQL)
            $folders = NoteFolder::where('user_id', $user->id)
                ->with(['workspaces'])
                ->orderBy('order_index')
                ->get();

            // 2. Optimization: Collect all workspace IDs to fetch notes in one go (Avoid N+1)
            $workspaceIds = $folders->flatMap(function ($folder) {
                return $folder->workspaces->pluck('id');
            })->toArray();

            // 3. Fetch all Notes from MongoDB in one query
            $allNotes = Note::whereIn('workspace_id', $workspaceIds)
                ->orderBy('order_index')
                ->get()
                ->groupBy('workspace_id');

            // 4. Map notes back to their respective workspaces
            foreach ($folders as $folder) {
                foreach ($folder->workspaces as $workspace) {
                    $workspace->setRelation('notes', $allNotes->get($workspace->id) ?? collect());
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $folders
            ]);
        } catch (Exception $e) {
            Log::error('Notes Index Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch notes structure.'
            ], 500);
        }
    }

    /**
     * Get detail of a single Note
     */
    public function show($id)
    {
        try {
            $note = Note::find($id);

            if (!$note) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Note not found.'
                ], 404);
            }

            $workspace = NoteWorkspace::with('folder')->find($note->workspace_id);
            if ($workspace) {
                $note->setRelation('workspace', $workspace);
            }

            return response()->json([
                'status' => 'success',
                'data' => $note
            ]);
        } catch (Exception $e) {
            Log::error('Show Note Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch note detail.'
            ], 500);
        }
    }

    /**
     * Search Notes (MongoDB)
     */
    public function search(Request $request)
    {
        try {
            $query = $request->query('q');
            if (!$query) {
                return response()->json([
                    'status' => 'success',
                    'data' => []
                ]);
            }

            $user = User::first();
            
            // Get user's workspace IDs to restrict search
            $workspaceIds = NoteWorkspace::whereHas('folder', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->pluck('id')->toArray();

            $notes = Note::whereIn('workspace_id', $workspaceIds)
                ->where(function($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('plain_text_preview', 'LIKE', "%{$query}%");
                })
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $notes
            ]);
        } catch (Exception $e) {
            Log::error('Search Notes Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Search failed.'
            ], 500);
        }
    }

    /**
     * Duplicate a Note (MongoDB)
     */
    public function duplicateNote($id)
    {
        try {
            $note = Note::find($id);
            if (!$note) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Note not found.'
                ], 404);
            }

            // Create a clone
            $newNote = $note->replicate();
            $newNote->title = $note->title . ' (Copy)';
            $newNote->created_at = now();
            $newNote->updated_at = now();
            $newNote->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Note duplicated successfully',
                'data' => $newNote
            ]);
        } catch (Exception $e) {
            Log::error('Duplicate Note Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to duplicate note.'
            ], 500);
        }
    }

    /**
     * Store a new Note (MongoDB)
     */
    public function storeNote(Request $request)
    {
        try {
            $validated = $request->validate([
                'workspace_id' => 'required|uuid|exists:note_workspaces,id',
                'title' => 'required|string|max:255',
                'content' => 'nullable|array',
                'plain_text_preview' => 'nullable|string',
                'highlight' => 'nullable|boolean',
                'order_index' => 'nullable|integer',
            ]);

            $note = Note::create([
                'workspace_id' => $validated['workspace_id'],
                'title' => $validated['title'],
                'content' => $validated['content'] ?? [],
                'plain_text_preview' => $validated['plain_text_preview'] ?? '',
                'highlight' => $validated['highlight'] ?? false,
                'order_index' => $validated['order_index'] ?? 0,
            ]);

            // Ensure ID is stringified for Frontend
            return response()->json([
                'status' => 'success',
                'message' => 'Note created successfully',
                'data' => $note
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Store Note Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Could not save note.'
            ], 500);
        }
    }

    /**
     * Store a new Folder (MySQL)
     */
    public function storeFolder(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'icon_name' => 'nullable|string|max:50',
                'order_index' => 'nullable|integer',
            ]);

            $user = User::first();
            $folder = NoteFolder::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'icon_name' => $validated['icon_name'] ?? 'folder',
                'order_index' => $validated['order_index'] ?? 0,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Folder created successfully',
                'data' => $folder
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not create folder.'
            ], 500);
        }
    }

    /**
     * Store a new Workspace (MySQL)
     */
    public function storeWorkspace(Request $request)
    {
        try {
            $validated = $request->validate([
                'folder_id' => 'required|uuid|exists:note_folders,id',
                'name' => 'required|string|max:255',
                'icon_name' => 'nullable|string|max:50',
                'order_index' => 'nullable|integer',
            ]);

            $workspace = NoteWorkspace::create([
                'folder_id' => $validated['folder_id'],
                'name' => $validated['name'],
                'icon_name' => $validated['icon_name'] ?? 'layout',
                'order_index' => $validated['order_index'] ?? 0,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Workspace created successfully',
                'data' => $workspace
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not create workspace.'
            ], 500);
        }
    }

    /**
     * Update Note content (MongoDB)
     */
    public function updateNote(Request $request, $id)
    {
        try {
            $note = Note::find($id);
            if (!$note) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Note not found.'
                ], 404);
            }

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|array',
                'plain_text_preview' => 'sometimes|string',
                'highlight' => 'sometimes|boolean',
                'order_index' => 'sometimes|integer',
            ]);

            $note->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Note updated successfully',
                'data' => $note
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update note.'
            ], 500);
        }
    }

    /**
     * Update Folder (MySQL)
     */
    public function updateFolder(Request $request, $id)
    {
        try {
            $folder = NoteFolder::find($id);
            if (!$folder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Folder not found.'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'icon_name' => 'sometimes|string|max:50',
                'order_index' => 'sometimes|integer',
            ]);

            $folder->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Folder updated successfully',
                'data' => $folder
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Update Folder Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update folder.'
            ], 500);
        }
    }

    /**
     * Update Workspace (MySQL)
     */
    public function updateWorkspace(Request $request, $id)
    {
        try {
            $workspace = NoteWorkspace::find($id);
            if (!$workspace) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Workspace not found.'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'icon_name' => 'sometimes|string|max:50',
                'order_index' => 'sometimes|integer',
            ]);

            $workspace->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Workspace updated successfully',
                'data' => $workspace
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Update Workspace Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update workspace.'
            ], 500);
        }
    }

    /**
     * Delete a Note (MongoDB)
     */
    public function destroyNote($id)
    {
        try {
            $note = Note::find($id);
            if (!$note) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Note not found.'
                ], 404);
            }

            $note->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Note deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete note.'
            ], 500);
        }
    }

    /**
     * Delete a Folder (MySQL)
     */
    public function destroyFolder($id)
    {
        try {
            $folder = NoteFolder::find($id);
            if (!$folder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Folder not found.'
                ], 404);
            }

            $folder->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Folder deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete folder.'
            ], 500);
        }
    }

    /**
     * Delete a Workspace (MySQL)
     */
    public function destroyWorkspace($id)
    {
        try {
            $workspace = NoteWorkspace::find($id);
            if (!$workspace) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Workspace not found.'
                ], 404);
            }

            $workspace->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Workspace deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete workspace.'
            ], 500);
        }
    }
}
