@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1"><i class="bi bi-journal-text me-2" style="color: var(--argon-gray-600);"></i>Auditoría</h1>
        <p class="text-secondary mb-0">Eventos críticos registrados por empresa, sucursal y usuario.</p>
    </div>

    <form class="row g-2 mb-4" method="GET">
        <div class="col-md-5">
            <input class="form-control" name="module" value="{{ $module }}" placeholder="Módulo (ej. Auth, Company)">
        </div>
        <div class="col-md-5">
            <input class="form-control" name="action" value="{{ $action }}" placeholder="Acción (ej. login, created)">
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100" type="submit">
                <i class="bi bi-funnel me-1"></i>Filtrar
            </button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Módulo</th>
                        <th>Acción</th>
                        <th>Usuario</th>
                        <th>Descripción</th>
                        <th>IP</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="small">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td><span class="badge bg-light text-dark">{{ $log->module }}</span></td>
                            <td><code>{{ $log->action }}</code></td>
                            <td>{{ $log->user?->username ?: 'Sistema' }}</td>
                            <td class="small">{{ Str::limit($log->description, 60) }}</td>
                            <td class="small"><code>{{ $log->ip_address ?: '—' }}</code></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.audit.show', $log) }}">
                                    <i class="bi bi-eye me-1"></i>Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No hay registros de auditoría.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $logs->links() }}</div>
@endsection
