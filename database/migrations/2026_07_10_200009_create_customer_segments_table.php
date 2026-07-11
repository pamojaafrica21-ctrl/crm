<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('criteria')->nullable();
            $table->timestamps();

            $table->unique(['property_id', 'name']);
        });

        Schema::create('customer_segment_members', function (Blueprint $table) {
            $table->foreignId('customer_segment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();

            $table->unique(['customer_segment_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_segment_members');
        Schema::dropIfExists('customer_segments');
    }
};
