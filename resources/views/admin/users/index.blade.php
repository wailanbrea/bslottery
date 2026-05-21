@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-people me-2" style="color: var(--argon-info);"></i>Usuarios</h1>
            <p class="text-secondary mb-0">Usuarios operativos dentro del alcance de empresa y sucursal.</p>
        </div>
        @if (auth()->user()->hasPermission('users.create'))
            <a class="btn btn-primary" href="{{ route('admin.users.create') }}">
                <i class="bi bi-plus-lg me-1"></i>Crear usuario
            </a>
        @endif
    </div>

    <form class="mb-4" method="GET">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Buscar por nombre, usuario o email">
            <button class="btn btn-outline-secondary" type="submit">Buscar</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Sucursal</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $target)
                        <tr>
                            <td class="fw-semibold">{{ $target->name }}</td>
                            <td><code>{{ $target->username }}</code></td>
                            <td>{{ $target->role?->name ?: '—' }}</td>
                            <td>{{ $target->branch?->name ?: 'Todas' }}</td>
                            <td><x-status-badge :status="$target->status" /></td>
                            <td class="text-end">
                                @if (auth()->user()->hasPermission('users.update'))
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.users.edit', $target) }}">
                                        <i class="bi bi-pencil me-1"></i>Editar
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">No hay usuarios registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $users->links() }}</div>
@endsection
