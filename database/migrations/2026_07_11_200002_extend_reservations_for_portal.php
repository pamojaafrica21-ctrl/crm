<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('room_type_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->after('room_type_id')->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->after('room_id')->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('adults')->default(1)->after('guests');
            $table->unsignedTinyInteger('children')->default(0)->after('adults');
            $table->string('source')->default('hms')->after('special_requests');
            $table->string('group_code')->nullable()->after('source');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('total_amount');
            $table->decimal('extras_amount', 12, 2)->default(0)->after('tax_amount');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('extras_amount');
            $table->string('coupon_code')->nullable()->after('discount_amount');

            $table->index(['property_id', 'group_code']);
            $table->index(['property_id', 'status', 'check_in', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_type_id');
            $table->dropConstrainedForeignId('room_id');
            $table->dropConstrainedForeignId('invoice_id');
            $table->dropColumn([
                'adults',
                'children',
                'source',
                'group_code',
                'tax_amount',
                'extras_amount',
                'discount_amount',
                'coupon_code',
            ]);
        });
    }
};
