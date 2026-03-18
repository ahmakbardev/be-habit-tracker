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

Schedule::call(function () {
    // Ambil semua habit aktif (belum di-archive)
    $habits = Habit::whereNull('archived_at')->get();
    
    // Rentang waktu pengiriman (5-11 menit sebelum jadwal)
    $windowStart = now()->addMinutes(5);
    $windowEnd = now()->addMinutes(11);

    foreach ($habits as $habit) {
        $schedules = $habit->schedules ?? [];
        if (!is_array($schedules)) continue;

        foreach ($schedules as $timeStr) {
            if ($timeStr === 'daily') continue;

            try {
                // Parsing jam jadwal ke waktu hari ini
                $scheduleTime = Carbon::parse($timeStr);
                
                // Cek apakah jam jadwal masuk dalam jendela 5-11 menit kedepan
                if ($scheduleTime->between($windowStart, $windowEnd)) {
                    $user = $habit->user;
                    if ($user) {
                        $user->notify(new HabitReminderNotification($habit, $timeStr));
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
    }
})->everyMinute();
