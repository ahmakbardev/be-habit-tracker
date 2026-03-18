<?php

namespace Database\Seeders;

use App\Models\Habit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class HabitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        if (!$user) return;

        $habits = [
            [
                'user_id' => $user->id,
                'name' => 'Minum Air',
                'icon_type' => 'droplet',
                'color' => '#3b82f6',
                'schedules' => ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'],
                'goal' => 8,
            ],
            [
                'user_id' => $user->id,
                'name' => 'Olahraga',
                'icon_type' => 'activity',
                'color' => '#ef4444',
                'schedules' => ['07:00'],
                'goal' => 1,
            ],
            [
                'user_id' => $user->id,
                'name' => 'Membaca Buku',
                'icon_type' => 'book',
                'color' => '#10b981',
                'schedules' => ['21:00'],
                'goal' => 1,
            ],
            [
                'user_id' => $user->id,
                'name' => 'No Alcohol',
                'icon_type' => 'ban',
                'color' => '#f59e0b',
                'schedules' => ['daily'],
                'goal' => 1,
            ],
        ];

        foreach ($habits as $habit) {
            Habit::create($habit);
        }
    }
}
