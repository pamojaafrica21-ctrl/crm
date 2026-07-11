<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('crm_id');
            $table->string('external_system')->default('hms');
            $table->string('external_id');
            $table->json('external_data')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status')->default('synced');
            $table->timestamps();

            $table->unique(['property_id', 'entity_type', 'external_system', 'external_id'], 'ext_map_property_entity_unique');
            $table->index(['entity_type', 'crm_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_mappings');
    }
};
