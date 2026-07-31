<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Swaps the Notes hierarchy labels: the entity that used to be the
 * top-level "Folder" becomes "Workspace", and the entity that used to be
 * the nested "Workspace" becomes "Folder". No rows are dropped or
 * recreated — only table/column names change, so all existing data
 * (folders, workspaces, notes) is preserved as-is.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Swap the two MySQL table names in a single atomic RENAME TABLE
        // statement — MySQL applies a multi-table rename as one operation,
        // so there is no intermediate state where a table is missing.
        DB::statement('RENAME TABLE note_folders TO note_folders_tmp_swap, note_workspaces TO note_folders, note_folders_tmp_swap TO note_workspaces');

        // 2. The table now called `note_folders` (old note_workspaces rows)
        // still has its parent FK column named `folder_id`; it now points
        // at `note_workspaces`, so rename it to `workspace_id`.
        DB::statement('ALTER TABLE note_folders RENAME COLUMN folder_id TO workspace_id');

        // 3. Mongo `notes.workspace_id` now points at what is called
        // `note_folders`, so rename the field to `folder_id`. Some
        // environments (e.g. local dev and production sharing the same
        // Atlas cluster) may have already had this rename applied — check
        // the actual field in use first so this step is safe either way.
        DB::connection('mongodb')->table('notes')->raw(function ($collection) {
            $stillOnOldField = $collection->countDocuments(['workspace_id' => ['$exists' => true]]) > 0;
            if ($stillOnOldField) {
                $collection->updateMany([], ['$rename' => ['workspace_id' => 'folder_id']]);
            }
            return null;
        });
    }

    public function down(): void
    {
        DB::connection('mongodb')->table('notes')->raw(function ($collection) {
            $stillOnNewField = $collection->countDocuments(['folder_id' => ['$exists' => true]]) > 0;
            if ($stillOnNewField) {
                $collection->updateMany([], ['$rename' => ['folder_id' => 'workspace_id']]);
            }
            return null;
        });

        DB::statement('ALTER TABLE note_folders RENAME COLUMN workspace_id TO folder_id');

        DB::statement('RENAME TABLE note_folders TO note_workspaces_tmp_swap, note_workspaces TO note_folders, note_workspaces_tmp_swap TO note_workspaces');
    }
};
