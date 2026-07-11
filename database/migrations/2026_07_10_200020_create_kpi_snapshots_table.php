<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('metric_key');
            $table->date('period_date');
            $table->string('period_type')->default('daily');
            $table->decimal('value', 14, 2)->default(0);
            $table->json('dimensions')->nullable();
            $table->timestamps();

            $table->unique(['property_id', 'metric_key', 'period_date', 'period_type'], 'kpi_snapshots_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_snapshots');
    }
};
