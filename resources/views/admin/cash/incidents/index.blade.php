@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-exclamation-triangle me-2" style="color: var(--argon-warning);"></i>Incidencias de caja</h1>
            <p class="text-secondary mb-0">Faltantes, sobrantes, reaperturas y eventos operativos auditables.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.cash.index') }}">
            <i class="bi bi-clock-history me-1"></i>Historial de caja
        </a>
    </div>

    <form class="card mb-3" method="GET" action="{{ route('admin.cash.incidents.index') }}">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="status">
                        <option value="">Todos</option>
                        @foreach (['OPEN' => 'Abierta', 'RESOLVED' => 'Resuelta', 'DISMISSED' => 'Desestimada'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" name="type">
                        <option value="">Todos</option>
                        @foreach (['CASH_SHORTAGE' => 'Faltante', 'CASH_SURPLUS' => 'Sobrante', 'CASH_REOPENED' => 'Reapertura'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.cash.incidents.index') }}">Limpiar</a>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Sucursal</th>
                        <th>Incidencia</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th>Cajero responsable</th>
                        <th>Caja</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($incidents as $incident)
                        <tr>
                            <td class="small">{{ $incident->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $incident->branch?->name ?: '-' }}</td>
                            <td>
                                <div class="fw-semibold">{{ $incident->title }}</div>
                                <div class="small text-secondary">{{ $incident->description }}</div>
                                <x-status-badge :status="$incident->severity" />
                            </td>
                            <td>
                                @if ($incident->amount !== null)
                                    <span class="fw-semibold {{ $incident->type === 'CASH_SHORTAGE' ? 'text-danger' : '' }}">
                                        RD$ {{ number_format($incident->amount, 2) }}
                                    </span>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td><x-status-badge :status="$incident->status" /></td>
                            <td class="small">
                                <div class="fw-semibold">{{ $incident->user?->name ?: '—' }}</div>
                                @if ($incident->user?->username)
                                    <div class="text-secondary">{{ '@'.$incident->user->username }}</div>
                                @endif
                                @if ($incident->resolved_at)
                                    <div class="mt-1 text-secondary">
                                        Resuelta por {{ $incident->resolvedBy?->name }}<br>
                                        <small>{{ $incident->resolved_at->format('Y-m-d H:i') }}</small>
                                    </div>
                                @endif
                            </td>
                            <td class="small">
                                @if ($incident->cash_session_id)
                                    <span><i class="bi bi-cash-stack me-1"></i>#{{ $incident->cash_session_id }}</span>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td class="text-end" style="min-width: 340px;">
                                @if ($incident->status === 'OPEN' && auth()->user()->hasPermission('cash.incidents.resolve'))
                                    @if ($incident->type === 'CASH_SHORTAGE')
                                        <div class="small text-warning mb-1">
                                            <i class="bi bi-info-circle"></i> Resolver = descontar al cajero en la próxima nómina. Desestimar = no aplica descuento.
                                        </div>
                                    @endif
                                    <form method="POST" action="{{ route('admin.cash.incidents.resolve', $incident) }}" class="mb-1">
                                        @csrf
                                        <div class="input-group input-group-sm">
                                            <input class="form-control" name="resolution_notes" required minlength="5" maxlength="1000" placeholder="Nota de resolución">
                                            <button class="btn btn-outline-success" type="submit">
                                                Resolver
                                            </button>
                                        </div>
                                    </form>
                                    <form method="POST" action="{{ route('admin.cash.incidents.dismiss', $incident) }}">
                                        @csrf
                                        <div class="input-group input-group-sm">
                                            <input class="form-control" name="dismiss_reason" required minlength="5" maxlength="1000" placeholder="Motivo para desestimar (no imputable al cajero)">
                                            <button class="btn btn-outline-secondary" type="submit">
                                                Desestimar
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <span class="text-secondary small">{{ $incident->resolution_notes ?: 'Sin acciones' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-secondary py-4">No hay incidencias registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $incidents->links() }}</div>
@endsection
