<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 7)->default('#3b82f6');
            $table->unsignedInteger('default_duration_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['property_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_types');
    }
};
