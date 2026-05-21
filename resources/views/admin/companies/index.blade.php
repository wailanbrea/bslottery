@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-building me-2" style="color: var(--argon-primary);"></i>Empresas</h1>
            <p class="text-secondary mb-0">Administración de empresas dentro del alcance permitido.</p>
        </div>
        @if (auth()->user()->hasPermission('companies.create'))
            <a class="btn btn-primary" href="{{ route('admin.companies.create') }}">
                <i class="bi bi-plus-lg me-1"></i>Crear empresa
            </a>
        @endif
    </div>

    <form class="mb-4" method="GET">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Buscar por nombre, RNC o código">
            <button class="btn btn-outline-secondary" type="submit">Buscar</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>RNC</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($companies as $company)
                        <tr>
                            <td class="fw-semibold">{{ $company->name }}</td>
                            <td>{{ $company->rnc ?: '—' }}</td>
                            <td><x-status-badge :status="$company->status" /></td>
                            <td class="text-end">
                                @if (auth()->user()->hasPermission('companies.update'))
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.companies.edit', $company) }}">
                                        <i class="bi bi-pencil me-1"></i>Editar
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-secondary py-4">No hay empresas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $companies->links() }}</div>
@endsection
