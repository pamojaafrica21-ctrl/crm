<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $teamKey = 'organization_id';
        $defaultOrgId = DB::table('organizations')->orderBy('id')->value('id') ?? 1;

        Schema::table('roles', function (Blueprint $table) use ($teamKey) {
            $table->unsignedBigInteger($teamKey)->nullable()->after('id');
            $table->index($teamKey);
        });

        $this->rebuildModelHasRoles($teamKey, $defaultOrgId);
        $this->rebuildModelHasPermissions($teamKey, $defaultOrgId);
    }

    public function down(): void
    {
        $teamKey = 'organization_id';

        Schema::table('roles', function (Blueprint $table) use ($teamKey) {
            $table->dropIndex(['organization_id']);
            $table->dropColumn($teamKey);
        });

        $this->rebuildModelHasRolesWithoutTeams();
        $this->rebuildModelHasPermissionsWithoutTeams();
    }

    private function rebuildModelHasRoles(string $teamKey, int $defaultOrgId): void
    {
        $rows = DB::table('model_has_roles')->get();

        Schema::drop('model_has_roles');

        Schema::create('model_has_roles', function (Blueprint $table) use ($teamKey) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger($teamKey);
            $table->index(['model_id', 'model_type']);
            $table->index($teamKey);
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->primary([$teamKey, 'role_id', 'model_id', 'model_type']);
        });

        foreach ($rows as $row) {
            $orgId = DB::table('users')->where('id', $row->model_id)->value('organization_id') ?? $defaultOrgId;

            DB::table('model_has_roles')->insert([
                'role_id' => $row->role_id,
                'model_type' => $row->model_type,
                'model_id' => $row->model_id,
                $teamKey => $orgId,
            ]);
        }
    }

    private function rebuildModelHasPermissions(string $teamKey, int $defaultOrgId): void
    {
        $rows = DB::table('model_has_permissions')->get();

        Schema::drop('model_has_permissions');

        Schema::create('model_has_permissions', function (Blueprint $table) use ($teamKey) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger($teamKey);
            $table->index(['model_id', 'model_type']);
            $table->index($teamKey);
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            $table->primary([$teamKey, 'permission_id', 'model_id', 'model_type']);
        });

        foreach ($rows as $row) {
            $orgId = DB::table('users')->where('id', $row->model_id)->value('organization_id') ?? $defaultOrgId;

            DB::table('model_has_permissions')->insert([
                'permission_id' => $row->permission_id,
                'model_type' => $row->model_type,
                'model_id' => $row->model_id,
                $teamKey => $orgId,
            ]);
        }
    }

    private function rebuildModelHasRolesWithoutTeams(): void
    {
        $rows = DB::table('model_has_roles')->get();

        Schema::drop('model_has_roles');

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        foreach ($rows as $row) {
            DB::table('model_has_roles')->insert([
                'role_id' => $row->role_id,
                'model_type' => $row->model_type,
                'model_id' => $row->model_id,
            ]);
        }
    }

    private function rebuildModelHasPermissionsWithoutTeams(): void
    {
        $rows = DB::table('model_has_permissions')->get();

        Schema::drop('model_has_permissions');

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        foreach ($rows as $row) {
            DB::table('model_has_permissions')->insert([
                'permission_id' => $row->permission_id,
                'model_type' => $row->model_type,
                'model_id' => $row->model_id,
            ]);
        }
    }
};
