<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $orgId = DB::table('organizations')->insertGetId([
            'name' => 'Demo Organisation',
            'slug' => 'demo-organisation',
            'email' => 'admin@crm.test',
            'phone' => null,
            'address' => null,
            'status' => 'active',
            'trial_ends_at' => null,
            'subscription_plan_id' => null,
            'settings' => json_encode([
                'timezone' => 'Africa/Nairobi',
                'currency' => 'USD',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('properties')->whereNull('organization_id')->update(['organization_id' => $orgId]);
        DB::table('users')->whereNull('organization_id')->where('is_super_admin', false)->update(['organization_id' => $orgId]);
    }

    public function down(): void
    {
        DB::table('users')->update(['organization_id' => null]);
        DB::table('properties')->update(['organization_id' => null]);
        DB::table('organizations')->whereIn('slug', ['demo-organisation', 'montana-resort'])->delete();
    }
};
