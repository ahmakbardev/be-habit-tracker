<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\HabitController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicNoteController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

/**
 * Public Note Pages (no auth)
 */
Route::get('/public/notes/{token}', [PublicNoteController::class, 'show']);

/**
 * Auth API Routes (Public)
 */
Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
});

Route::prefix('auth')->controller(ForgotPasswordController::class)->group(function () {
    Route::post('/forgot-password', 'sendOtp');
    Route::post('/reset-password', 'resetPassword');
});

/**
 * Protected API Routes (Sanctum)
 */
Route::middleware('auth:sanctum')->group(function () {

    /**
     * Auth (Protected)
     */
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout');
        Route::get('/me', 'me');
    });

    /**
     * Profile Routes
     */
    Route::prefix('profile')->controller(ProfileController::class)->group(function () {
        Route::get('/', 'show');
        Route::patch('/', 'update');
        Route::post('/avatar', 'uploadAvatar');
        Route::patch('/password', 'changePassword');
    });

    /**
     * Notes API Routes
     */
    Route::prefix('notes')->controller(NoteController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/search', 'search');
        Route::get('/{id}', 'show');
        Route::post('/folders', 'storeFolder');
        Route::patch('/folders/{id}', 'updateFolder');
        Route::delete('/folders/{id}', 'destroyFolder');
        Route::post('/workspaces', 'storeWorkspace');
        Route::patch('/workspaces/{id}', 'updateWorkspace');
        Route::delete('/workspaces/{id}', 'destroyWorkspace');
        Route::post('/', 'storeNote');
        Route::patch('/{id}', 'updateNote');
        Route::post('/{id}/duplicate', 'duplicateNote');
        Route::patch('/{id}/publish', 'togglePublish');
        Route::delete('/{id}', 'destroyNote');
        Route::post('/media/upload', [MediaController::class, 'upload']);
    });

    /**
     * Habits API Routes
     */
    Route::prefix('habits')->controller(HabitController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/completions', 'getCompletions');
        Route::get('/stats', 'getStats');
        Route::post('/', 'store');
        Route::patch('/{id}', 'update');
        Route::post('/toggle', 'toggleLog');
        Route::get('/{id}/efficiency', 'getEfficiency');
        Route::delete('/{id}', 'destroy');
    });

    /**
     * Tasks API Routes
     */
    Route::prefix('tasks')->controller(TaskController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/overview/active', 'activeOverview');
        Route::get('/projects/{id}', 'showProject');
        Route::post('/projects', 'storeProject');
        Route::patch('/projects/{id}', 'updateProject');
        Route::delete('/projects/{id}', 'destroyProject');

        Route::post('/', 'storeTask');
        Route::patch('/{id}', 'updateTask');
        Route::delete('/{id}', 'destroyTask');
        Route::put('/reorder', 'reorderTasks');

        Route::post('/{taskId}/subtasks', 'storeSubtask');
        Route::patch('/subtasks/{id}', 'updateSubtask');
        Route::delete('/subtasks/{id}', 'destroySubtask');
        Route::post('/subtasks/{id}/convert', 'convertSubtask');

        Route::post('/{taskId}/comments', 'storeComment');
        Route::patch('/comments/{id}', 'updateComment');
        Route::delete('/comments/{id}', 'destroyComment');

        Route::post('/{taskId}/attachments', 'storeAttachment');
        Route::delete('/attachments/{id}', 'destroyAttachment');

        Route::post('/{taskId}/notes', 'storeTaskNote');
        Route::delete('/notes/{id}', 'destroyTaskNote');

        Route::post('/{taskId}/assignees', 'addAssignee');
        Route::delete('/{taskId}/assignees/{userId}', 'removeAssignee');
    });

    /**
     * Push Subscription Routes
     */
    Route::prefix('push-subscriptions')->controller(PushSubscriptionController::class)->group(function () {
        Route::post('/', 'update');
        Route::delete('/', 'destroy');
    });
});
