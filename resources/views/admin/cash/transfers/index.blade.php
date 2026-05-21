@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-bank me-2" style="color: var(--argon-info);"></i>Transferencias</h1>
            <p class="text-secondary mb-0">Control de transferencias bancarias asociadas a caja.</p>
        </div>
        @if (auth()->user()->hasPermission('cash.transfers.create'))
            <a class="btn btn-primary" href="{{ route('admin.cash.transfers.create') }}">
                <i class="bi bi-plus-lg me-1"></i>Nueva transferencia
            </a>
        @endif
    </div>

    <form class="card mb-3" method="GET" action="{{ route('admin.cash.transfers.index') }}">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="status">
                        <option value="">Todos</option>
                        @foreach (['PENDING' => 'Pendiente', 'CONFIRMED' => 'Confirmada', 'REJECTED' => 'Rechazada'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" name="movement_type">
                        <option value="">Todos</option>
                        @foreach ($movementTypes as $value => $definition)
                            <option value="{{ $value }}" @selected(request('movement_type') === $value)>{{ $definition['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.cash.transfers.index') }}">Limpiar</a>
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
                        <th>Tipo</th>
                        <th>Banco / referencia</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th>Verificacion</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transfers as $transfer)
                        <tr>
                            <td class="small">{{ $transfer->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $transfer->branch?->name ?: '-' }}</td>
                            <td>
                                <div class="fw-semibold">{{ $movementTypes[$transfer->movement_type]['label'] ?? $transfer->movement_type }}</div>
                                <small class="text-secondary">{{ $transfer->direction }}</small>
                            </td>
                            <td>
                                <div>{{ $transfer->bank_name }}</div>
                                <code>{{ $transfer->reference }}</code>
                            </td>
                            <td class="fw-semibold {{ $transfer->direction === 'IN' ? 'text-success' : 'text-danger' }}">
                                RD$ {{ number_format($transfer->amount, 2) }}
                            </td>
                            <td><x-status-badge :status="$transfer->status" /></td>
                            <td class="small">
                                @if ($transfer->verified_at)
                                    {{ $transfer->verified_at->format('Y-m-d H:i') }}
                                    <div class="text-secondary">{{ $transfer->verifiedBy?->name }}</div>
                                @else
                                    <span class="text-secondary">Pendiente</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($transfer->status === 'PENDING' && auth()->user()->hasPermission('cash.transfers.verify'))
                                    <form class="d-inline" method="POST" action="{{ route('admin.cash.transfers.confirm', $transfer) }}" onsubmit="return confirm('Confirmar esta transferencia?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success" type="submit">
                                            <i class="bi bi-check-lg me-1"></i>Confirmar
                                        </button>
                                    </form>
                                    <form class="d-inline" method="POST" action="{{ route('admin.cash.transfers.reject', $transfer) }}" onsubmit="return confirm('Rechazar esta transferencia?')">
                                        @csrf
                                        <input type="hidden" name="notes" value="Rechazada desde listado.">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            <i class="bi bi-x-lg me-1"></i>Rechazar
                                        </button>
                                    </form>
                                @else
                                    <span class="text-secondary small">Sin acciones</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-secondary py-4">No hay transferencias registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $transfers->links() }}</div>
@endsection
