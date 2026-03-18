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

    // Ambil semua habit aktif (belum di-archive)
    $habits = Habit::whereNull('archived_at')->get();
    Log::info('Processing ' . $habits->count() . ' habits.');

    // Rentang waktu pengiriman (5-11 menit sebelum jadwal)
    $windowStart = now()->addMinutes(5);
    $windowEnd = now()->addMinutes(11);

    foreach ($habits as $habit) {
        $schedules = $habit->schedules ?? [];
        if (!is_array($schedules)) continue;

        foreach ($schedules as $timeStr) {
            if ($timeStr === 'daily') continue;

            try {
                $scheduleTime = Carbon::createFromFormat('H:i', $timeStr);

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
