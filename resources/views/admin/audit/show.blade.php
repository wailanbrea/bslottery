@extends('layouts.app')

@section('content')
    @php
        $auditableName = $log->auditable_type ? class_basename($log->auditable_type) : '—';
        $oldValues = json_encode($log->old_values ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $newValues = json_encode($log->new_values ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @endphp

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-journal-text me-2" style="color: var(--argon-gray-600);"></i>Detalle de auditoría</h1>
            <p class="text-secondary mb-0">Evento #{{ $log->id }} — {{ $log->created_at?->format('Y-m-d H:i:s') }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.audit.index') }}">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-secondary small text-uppercase">Módulo</div>
                    <div class="fw-semibold">{{ $log->module }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small text-uppercase">Acción</div>
                    <div class="fw-semibold"><code>{{ $log->action }}</code></div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small text-uppercase">Empresa</div>
                    <div class="fw-semibold">{{ $log->company?->name ?: '—' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small text-uppercase">Sucursal</div>
                    <div class="fw-semibold">{{ $log->branch?->name ?: '—' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small text-uppercase">Usuario</div>
                    <div class="fw-semibold">{{ $log->user?->username ?: 'Sistema' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small text-uppercase">Dispositivo</div>
                    <div class="fw-semibold">{{ $log->device?->name ?: '—' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small text-uppercase">IP</div>
                    <div class="fw-semibold"><code>{{ $log->ip_address ?: '—' }}</code></div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small text-uppercase">Entidad</div>
                    <div class="fw-semibold">{{ $auditableName }} #{{ $log->auditable_id ?: '—' }}</div>
                </div>
                <div class="col-12">
                    <div class="text-secondary small text-uppercase">Descripción</div>
                    <div>{{ $log->description ?: '—' }}</div>
                </div>
                <div class="col-12">
                    <div class="text-secondary small text-uppercase">User agent</div>
                    <div class="text-break small">{{ $log->user_agent ?: '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0 text-uppercase text-secondary">Valores anteriores</h2>
                </div>
                <div class="card-body">
                    <pre class="bg-light border rounded p-3 mb-0 small text-break"><code>{{ $oldValues }}</code></pre>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0 text-uppercase text-secondary">Valores nuevos</h2>
                </div>
                <div class="card-body">
                    <pre class="bg-light border rounded p-3 mb-0 small text-break"><code>{{ $newValues }}</code></pre>
                </div>
            </div>
        </div>
    </div>
@endsection
