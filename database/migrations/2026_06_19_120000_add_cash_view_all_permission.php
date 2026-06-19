<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permiso dedicado para la vista company-wide de caja ("Cajas por Sucursal").
     * Se concede a los roles que hoy ya tienen alcance global de caja (poseen cash.view Y
     * branches.view), para que los admins existentes conserven la vista sin re-sembrar roles.
     */
    public function up(): void
    {
        $now = now();

        $permissionId = DB::table('permissions')->where('slug', 'cash.view_all')->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'module' => 'cash',
                'action' => 'view_all',
                'name' => 'Cash View All',
                'slug' => 'cash.view_all',
                'description' => 'Ver las cajas de todas las sucursales (vista Cajas por Sucursal).',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $cashViewId = DB::table('permissions')->where('slug', 'cash.view')->value('id');
        $branchesViewId = DB::table('permissions')->where('slug', 'branches.view')->value('id');

        if ($cashViewId && $branchesViewId) {
            $rolesWithCashView = DB::table('role_permissions')->where('permission_id', $cashViewId)->pluck('role_id');
            $rolesWithBranchesView = DB::table('role_permissions')->where('permission_id', $branchesViewId)->pluck('role_id');

            foreach ($rolesWithCashView->intersect($rolesWithBranchesView) as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now],
                );
            }
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('slug', 'cash.view_all')->value('id');

        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
