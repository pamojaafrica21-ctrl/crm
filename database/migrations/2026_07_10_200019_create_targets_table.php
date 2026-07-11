<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('metric');
            $table->string('period');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('target_value', 14, 2);
            $table->string('currency', 3)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('target_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('assigned_value', 14, 2);
            $table->decimal('actual_value', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['target_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('target_assignments');
        Schema::dropIfExists('targets');
    }
};
