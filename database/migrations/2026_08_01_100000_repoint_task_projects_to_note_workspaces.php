<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tasks now live directly under the same global Workspace used by Notes
 * (note_workspaces), instead of their own separate task_folders concept.
 * No frontend ever wrote real data into task_projects (it was 100%
 * client-side local state), so this is a straight repoint, not a data
 * migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_projects', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
            $table->dropColumn('folder_id');
        });

        Schema::table('task_projects', function (Blueprint $table) {
            $table->foreignUuid('workspace_id')->after('id')->constrained('note_workspaces')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('task_projects', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropColumn('workspace_id');
        });

        Schema::table('task_projects', function (Blueprint $table) {
            $table->foreignUuid('folder_id')->after('id')->constrained('task_folders')->onDelete('cascade');
        });
    }
};
