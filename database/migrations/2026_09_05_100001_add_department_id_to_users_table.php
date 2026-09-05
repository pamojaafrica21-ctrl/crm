<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('department')
                ->constrained()
                ->nullOnDelete();
        });

        $rows = DB::table('users')
            ->select('id', 'organization_id', 'department')
            ->whereNotNull('organization_id')
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->get();

        $departmentIds = [];

        foreach ($rows as $row) {
            $key = $row->organization_id.'|'.mb_strtolower(trim($row->department));

            if (! isset($departmentIds[$key])) {
                $departmentIds[$key] = DB::table('departments')->insertGetId([
                    'organization_id' => $row->organization_id,
                    'name' => trim($row->department),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('users')->where('id', $row->id)->update([
                'department_id' => $departmentIds[$key],
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
