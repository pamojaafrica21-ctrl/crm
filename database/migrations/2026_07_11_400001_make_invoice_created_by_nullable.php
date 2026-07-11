<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['recorded_by']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('recorded_by')->nullable()->change();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['recorded_by']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('recorded_by')->nullable(false)->change();
            $table->foreign('recorded_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
