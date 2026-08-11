<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_subtasks', function (Blueprint $table) {
            $table->enum('priority', ['low', 'medium', 'high'])->nullable()->after('completed');
            $table->timestamp('due_date')->nullable()->after('priority');
            $table->foreignId('assignee_id')->nullable()->after('due_date')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('task_subtasks', function (Blueprint $table) {
            $table->dropForeign(['assignee_id']);
            $table->dropColumn(['priority', 'due_date', 'assignee_id']);
        });
    }
};
