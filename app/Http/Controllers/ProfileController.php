<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use App\Models\Note;
use App\Models\NoteFolder;
use App\Models\TaskFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Exception;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        try {
            $user = $request->user();

            $folderIds = NoteFolder::whereHas('workspace', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->pluck('id')->toArray();

            $noteCount  = Note::whereIn('folder_id', $folderIds)->count();
            $habitCount = Habit::where('user_id', $user->id)->count();
            $taskCount  = \App\Models\Task::whereHas('project.folder', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->count();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'username'   => $user->username,
                    'email'      => $user->email,
                    'bio'        => $user->bio,
                    'avatar_url' => $user->avatar_url,
                    'member_since' => $user->created_at,
                    'stats' => [
                        'notes'  => $noteCount,
                        'habits' => $habitCount,
                        'tasks'  => $taskCount,
                    ],
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Profile Show Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load profile.',
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'name'     => 'sometimes|string|max:255',
                'username' => 'sometimes|string|max:50|alpha_dash|unique:users,username,' . $user->id,
                'bio'      => 'sometimes|nullable|string|max:500',
            ]);

            $user->update($validated);

            return response()->json([
                'status'  => 'success',
                'message' => 'Profile updated.',
                'data'    => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'username'   => $user->username,
                    'email'      => $user->email,
                    'bio'        => $user->bio,
                    'avatar_url' => $user->avatar_url,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Profile Update Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update profile.',
            ], 500);
        }
    }

    public function uploadAvatar(Request $request)
    {
        try {
            $request->validate([
                'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            $user = $request->user();

            if ($user->avatar) {
                Storage::delete($user->avatar);
            }

            $path = $request->file('avatar')->store("avatars/{$user->id}", 'public');
            $user->update(['avatar' => $path]);

            return response()->json([
                'status'     => 'success',
                'message'    => 'Avatar updated.',
                'avatar_url' => $user->avatar_url,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Avatar Upload Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to upload avatar.',
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'password'         => 'required|string|min:6|confirmed',
            ]);

            $user = $request->user();

            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Current password is incorrect.',
                ], 422);
            }

            $user->update(['password' => Hash::make($validated['password'])]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Password changed successfully.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Change Password Error: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to change password.',
            ], 500);
        }
    }
}
