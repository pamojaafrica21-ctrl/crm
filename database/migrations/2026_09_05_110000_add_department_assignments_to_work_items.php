<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_target', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['target_id', 'department_id']);
        });

        Schema::create('task_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['task_id', 'user_id']);
        });

        Schema::create('department_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['task_id', 'department_id']);
        });

        Schema::create('appointment_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['appointment_id', 'department_id']);
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->json('target_department_ids')->nullable()->after('target_roles');
        });

        // Preserve existing single assignees as multi-assignee rows.
        if (Schema::hasTable('tasks')) {
            $tasks = \Illuminate\Support\Facades\DB::table('tasks')
                ->whereNotNull('assigned_to')
                ->select('id', 'assigned_to', 'created_at', 'updated_at')
                ->get();

            foreach ($tasks as $task) {
                \Illuminate\Support\Facades\DB::table('task_user')->insertOrIgnore([
                    'task_id' => $task->id,
                    'user_id' => $task->assigned_to,
                    'created_at' => $task->created_at ?? now(),
                    'updated_at' => $task->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('target_department_ids');
        });

        Schema::dropIfExists('appointment_department');
        Schema::dropIfExists('department_task');
        Schema::dropIfExists('task_user');
        Schema::dropIfExists('department_target');
    }
};
