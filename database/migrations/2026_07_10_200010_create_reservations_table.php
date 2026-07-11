<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('confirmation_number')->nullable();
            $table->string('room_type')->nullable();
            $table->string('room_number')->nullable();
            $table->date('check_in');
            $table->date('check_out');
            $table->string('status')->default('confirmed');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->unsignedTinyInteger('guests')->default(1);
            $table->text('special_requests')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'check_in']);
            $table->index(['customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
