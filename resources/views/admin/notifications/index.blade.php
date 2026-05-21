@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-bell me-2" style="color: var(--argon-warning);"></i>Notificaciones</h1>
            <p class="text-secondary mb-0">Alertas operativas y financieras del sistema.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.monitoring.index') }}">
            <i class="bi bi-activity me-1"></i>Monitoreo
        </a>
    </div>

    <form method="GET" action="{{ route('admin.notifications.index') }}" class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="status">
                        <option value="">Todos</option>
                        <option value="UNREAD" @selected(request('status') === 'UNREAD')>Sin leer</option>
                        <option value="READ" @selected(request('status') === 'READ')>Leidas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.notifications.index') }}">Limpiar</a>
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
                        <th>Notificacion</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th class="text-end">Accion</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($notifications as $notification)
                        <tr>
                            <td class="small">{{ $notification->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $notification->branch?->name ?: 'General' }}</td>
                            <td>
                                <div class="fw-semibold">{{ $notification->title }}</div>
                                <div class="small text-secondary">{{ $notification->body }}</div>
                                <x-status-badge :status="$notification->severity" />
                            </td>
                            <td>
                                @if ($notification->amount !== null)
                                    RD$ {{ number_format($notification->amount, 2) }}
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td>
                                <x-status-badge :status="$notification->status" />
                                @if ($notification->read_at)
                                    <div class="small text-secondary">{{ $notification->readBy?->name }} · {{ $notification->read_at->format('Y-m-d H:i') }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($notification->status === 'UNREAD')
                                    <form method="POST" action="{{ route('admin.notifications.read', $notification) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success" type="submit">
                                            <i class="bi bi-check-lg me-1"></i>Marcar leida
                                        </button>
                                    </form>
                                @else
                                    <span class="text-secondary small">Sin acciones</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">No hay notificaciones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $notifications->links() }}</div>
@endsection
