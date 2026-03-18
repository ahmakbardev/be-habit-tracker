<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Task;
use App\Notifications\TodoReminderNotification;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    // Cari task yang akan datang dalam 5-11 menit kedepan
    $tasks = Task::where('status', '!=', 'completed')
        ->whereNotNull('due_at')
        ->whereBetween('due_at', [now()->addMinutes(5), now()->addMinutes(11)])
        ->get();

    foreach ($tasks as $task) {
        $user = $task->user; // Menggunakan attribute getUserAttribute()
        if ($user) {
            $user->notify(new TodoReminderNotification($task));
        }
    }
})->everyMinute();
