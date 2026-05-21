@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1"><i class="bi bi-shield-check me-2" style="color: var(--argon-primary);"></i>Roles</h1>
        <p class="text-secondary mb-0">Roles iniciales y cantidad de permisos asignados.</p>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Rol</th>
                        <th>Slug</th>
                        <th>Nivel</th>
                        <th>Permisos</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td class="fw-semibold">{{ $role->name }}</td>
                            <td><code>{{ $role->slug }}</code></td>
                            <td>{{ $role->level }}</td>
                            <td><span class="badge bg-info">{{ $role->permissions_count }}</span></td>
                            <td>
                                <x-status-badge :status="$role->status" />
                            </td>
                            <td class="text-end">
                                @if (auth()->user()->hasPermission('roles.assign_permissions'))
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.roles.permissions.edit', $role) }}">
                                        <i class="bi bi-key me-1"></i>Permisos
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $roles->links() }}</div>
@endsection
