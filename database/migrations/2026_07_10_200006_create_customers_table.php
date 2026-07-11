<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('nationality')->nullable();
            $table->string('passport_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('company')->nullable();
            $table->string('vip_level')->nullable();
            $table->text('preferences')->nullable();
            $table->string('source')->default('crm');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['property_id', 'email']);
            $table->index(['property_id', 'phone']);
            $table->index(['property_id', 'last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
