<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRolePermissionsRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $query = Role::query()->withCount('permissions')->orderByDesc('level')->orderBy('name');

        if (! $request->user()->isSuperAdmin()) {
            $query->where('slug', '!=', 'SUPER_ADMIN');
        }

        return view('admin.roles.index', [
            'roles' => $query->paginate(15),
        ]);
    }

    public function edit(Request $request, Role $role): View
    {
        Gate::authorize('assignPermissions', $role);

        return view('admin.roles.permissions', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::query()->orderBy('module')->orderBy('action')->get()->groupBy('module'),
        ]);
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role, AuditService $audit): RedirectResponse
    {
        Gate::authorize('assignPermissions', $role);

        $old = $role->permissions()->pluck('slug')->all();
        $role->permissions()->sync($request->validated('permission_ids') ?? []);
        $role->load('permissions');

        $audit->record('roles', 'assign_permissions', 'Permisos del rol actualizados.', $role, oldValues: ['permissions' => $old], newValues: [
            'permissions' => $role->permissions->pluck('slug')->all(),
        ]);

        return redirect()->route('admin.roles.index')->with('status', 'Permisos actualizados.');
    }
}
