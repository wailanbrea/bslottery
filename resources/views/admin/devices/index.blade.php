@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1"><i class="bi bi-phone me-2" style="color: var(--argon-warning);"></i>Dispositivos</h1>
        <p class="text-secondary mb-0">Dispositivos web, Android y agentes de impresión registrados.</p>
    </div>

    <form class="row g-2 mb-4" method="GET">
        <div class="col-md-8">
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Buscar por nombre o fingerprint">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">Todos los estados</option>
                @foreach (['PENDING', 'AUTHORIZED', 'BLOCKED', 'REVOKED'] as $item)
                    <option value="{{ $item }}" @selected($status === $item)>{{ $item }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-outline-secondary w-100" type="submit">
                <i class="bi bi-funnel"></i>
            </button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Sucursal</th>
                        <th>Estado</th>
                        <th>Última conexión</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devices as $device)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $device->name }}</div>
                                <div class="small text-secondary">{{ $device->device_fingerprint }}</div>
                            </td>
                            <td>
                                @php
                                    $typeIcon = match($device->device_type) {
                                        'WEB_PC' => 'bi-pc-display',
                                        'ANDROID' => 'bi-phone',
                                        'PRINT_AGENT' => 'bi-printer',
                                        default => 'bi-device-hdd',
                                    };
                                @endphp
                                <i class="bi {{ $typeIcon }} me-1 text-secondary"></i>{{ $device->device_type }}
                            </td>
                            <td>{{ $device->branch?->name ?: 'Todas' }}</td>
                            <td><x-status-badge :status="$device->status" /></td>
                            <td>{{ $device->last_seen_at?->format('Y-m-d H:i') ?: '—' }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    @if (auth()->user()->hasPermission('devices.authorize') && $device->status !== 'AUTHORIZED')
                                        <form method="POST" action="{{ route('admin.devices.authorize', $device) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success" type="submit">
                                                <i class="bi bi-check-lg me-1"></i>Autorizar
                                            </button>
                                        </form>
                                    @endif
                                    @if (auth()->user()->hasPermission('devices.block') && $device->status !== 'BLOCKED')
                                        <form method="POST" action="{{ route('admin.devices.block', $device) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                                <i class="bi bi-slash-circle me-1"></i>Bloquear
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">No hay dispositivos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $devices->links() }}</div>
@endsection
