<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('tagline')->nullable()->after('address');
            $table->text('about')->nullable()->after('tagline');
            $table->string('phone')->nullable()->after('about');
            $table->string('email')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');
            $table->time('check_in_time')->nullable()->after('website');
            $table->time('check_out_time')->nullable()->after('check_in_time');
            $table->decimal('latitude', 10, 7)->nullable()->after('check_out_time');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('hero_image')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'tagline',
                'about',
                'phone',
                'email',
                'website',
                'check_in_time',
                'check_out_time',
                'latitude',
                'longitude',
                'hero_image',
            ]);
        });
    }
};
