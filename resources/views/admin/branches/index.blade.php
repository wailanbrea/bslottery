@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-shop me-2" style="color: var(--argon-success);"></i>Sucursales</h1>
            <p class="text-secondary mb-0">Cada sucursal representa una banca o punto de venta.</p>
        </div>
        @if (auth()->user()->hasPermission('branches.create'))
            <a class="btn btn-primary" href="{{ route('admin.branches.create') }}">
                <i class="bi bi-plus-lg me-1"></i>Crear sucursal
            </a>
        @endif
    </div>

    <form class="mb-4" method="GET">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Buscar por código o nombre">
            <button class="btn btn-outline-secondary" type="submit">Buscar</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Empresa</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($branches as $branch)
                        <tr>
                            <td><code>{{ $branch->code }}</code></td>
                            <td class="fw-semibold">{{ $branch->name }}</td>
                            <td>{{ $branch->company->name }}</td>
                            <td><x-status-badge :status="$branch->status" /></td>
                            <td class="text-end">
                                @if (auth()->user()->hasPermission('branches.update'))
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.branches.edit', $branch) }}">
                                        <i class="bi bi-pencil me-1"></i>Editar
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-4">No hay sucursales registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $branches->links() }}</div>
@endsection
