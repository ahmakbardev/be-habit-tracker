<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Habit;
use App\Notifications\HabitReminderNotification;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Log;

Schedule::call(function () {
    Log::info('--- Habit Scheduler Start ---');
    Log::info('Default Connection: ' . config('database.default'));
    Log::info('User Connection: ' . (new \App\Models\User)->getConnectionName());

    // Ambil semua habit aktif (belum di-archive)
    $habits = Habit::whereNull('archived_at')->get();
    Log::info('Processing ' . $habits->count() . ' habits.');

    // Rentang waktu pengiriman: Pas di jamnya (toleransi 1 menit)
    $windowStart = now()->subMinute();
    $windowEnd = now()->addMinute();

    foreach ($habits as $habit) {
        $schedules = $habit->schedules ?? [];
        if (!is_array($schedules)) continue;

        foreach ($schedules as $timeStr) {
            if ($timeStr === 'daily') continue;

            try {
                $scheduleTime = Carbon::createFromFormat('H:i', $timeStr);
                Log::info("Checking habit '{$habit->name}' schedule: {$timeStr} against window: {$windowStart->format('H:i')}-{$windowEnd->format('H:i')}");

                if ($scheduleTime->between($windowStart, $windowEnd)) {
                    $user = $habit->user;
                    if ($user) {
                        Log::info("Found matching habit '{$habit->name}' for user ID: {$user->id} at schedule {$timeStr}");
                        $user->notify(new HabitReminderNotification($habit, $timeStr));
                    } else {
                        Log::warning("Habit '{$habit->name}' has no owner (user_id null).");
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
    }
    Log::info('--- Habit Scheduler End ---');
})->everyMinute();
