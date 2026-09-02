<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        $orgs = DB::table('organizations')->select('id')->get();

        foreach ($orgs as $org) {
            $ownerId = DB::table('users')
                ->where('organization_id', $org->id)
                ->orderBy('id')
                ->value('id');

            if ($ownerId) {
                DB::table('organizations')->where('id', $org->id)->update(['owner_id' => $ownerId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
        });
    }
};
