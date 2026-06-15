<?php

namespace Database\Seeders;

use App\Models\Habit;
use App\Models\HabitCompletion;
use App\Models\NoteFolder;
use App\Models\TaskFolder;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AssignDataToUserSeeder extends Seeder
{
    /**
     * Create the primary user (ahmakbar.dev@gmail.com) and assign all
     * existing notes/habits/tasks data to that user.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'ahmakbar.dev@gmail.com'],
            [
                'name' => 'Ahmad Akbar',
                'password' => Hash::make('password'),
            ]
        );

        $this->command->info("Primary user: {$user->email} (ID: {$user->id})");

        // MySQL: assign note folders & task folders (workspaces/projects/columns/tasks
        // inherit ownership through folders, so no direct user_id field on them).
        $noteFolders = NoteFolder::query()->update(['user_id' => $user->id]);
        $this->command->info("Note folders updated: {$noteFolders}");

        $taskFolders = TaskFolder::query()->update(['user_id' => $user->id]);
        $this->command->info("Task folders updated: {$taskFolders}");

        // MongoDB: habits & habit completions.
        $habits = Habit::query()->update(['user_id' => $user->id]);
        $this->command->info("Habits updated: {$habits}");

        $completions = HabitCompletion::query()->update(['user_id' => $user->id]);
        $this->command->info("Habit completions updated: {$completions}");

        $this->command->info('All existing data has been assigned to ' . $user->email);
    }
}
