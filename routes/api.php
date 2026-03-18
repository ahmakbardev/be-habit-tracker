<?php

use App\Http\Controllers\NoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/**
 * Notes API Routes (Temporary without auth for testing)
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
    Route::delete('/{id}', 'destroyNote');
    Route::post('/media/upload', [\App\Http\Controllers\MediaController::class, 'upload']);
});

/**
 * Habits API Routes
 */
Route::prefix('habits')->controller(App\Http\Controllers\HabitController::class)->group(function () {
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
Route::prefix('tasks')->controller(App\Http\Controllers\TaskController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/folders', 'storeFolder');
    Route::get('/projects/{id}', 'showProject');
    Route::post('/projects', 'storeProject');
    Route::post('/', 'storeTask');
    Route::put('/reorder', 'reorderTasks');
});

/**
 * Push Subscription Routes
 */
Route::prefix('push-subscriptions')->controller(App\Http\Controllers\PushSubscriptionController::class)->group(function () {
    Route::post('/', 'update');
    Route::delete('/', 'destroy');
});



