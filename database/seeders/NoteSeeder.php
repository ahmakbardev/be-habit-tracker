<?php

namespace Database\Seeders;

use App\Models\Note;
use App\Models\NoteFolder;
use App\Models\NoteWorkspace;
use App\Models\User;
use Illuminate\Database\Seeder;

class NoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure we have at least one user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        // 2. Create Note Folders (MySQL)
        $workFolder = NoteFolder::create([
            'user_id' => $user->id,
            'name' => 'Work',
            'icon_name' => 'briefcase',
            'order_index' => 0,
        ]);

        $personalFolder = NoteFolder::create([
            'user_id' => $user->id,
            'name' => 'Personal',
            'icon_name' => 'user',
            'order_index' => 1,
        ]);

        // 3. Create Note Workspaces (MySQL)
        $projectAlpha = NoteWorkspace::create([
            'folder_id' => $workFolder->id,
            'name' => 'Project Alpha',
            'icon_name' => 'layout',
            'order_index' => 0,
        ]);

        $journal = NoteWorkspace::create([
            'folder_id' => $personalFolder->id,
            'name' => 'Daily Journal',
            'icon_name' => 'book',
            'order_index' => 0,
        ]);

        // 4. Create Notes (MongoDB)
        // Note for Project Alpha
        Note::create([
            'workspace_id' => $projectAlpha->id,
            'title' => 'Project Requirements',
            'content' => [
                'type' => 'doc',
                'content' => [
                    ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'This is the requirement document for Project Alpha.']]]
                ]
            ],
            'plain_text_preview' => 'This is the requirement document for Project Alpha.',
            'highlight' => true,
            'order_index' => 0,
        ]);

        // Note for Daily Journal
        Note::create([
            'workspace_id' => $journal->id,
            'title' => 'My First Entry',
            'content' => [
                'type' => 'doc',
                'content' => [
                    ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Today was a great day starting my new app.']]]
                ]
            ],
            'plain_text_preview' => 'Today was a great day starting my new app.',
            'highlight' => false,
            'order_index' => 0,
        ]);
    }
}
