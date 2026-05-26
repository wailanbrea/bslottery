@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-cash-stack me-2" style="color: var(--argon-success);"></i>Caja #{{ $session->id }}</h1>
            <p class="text-secondary mb-0">
                {{ $session->branch?->name ?: 'Sin sucursal' }} - {{ $session->user?->name ?: 'Sin cajero' }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.cash.index') }}">
                <i class="bi bi-arrow-left me-1"></i>Volver a cajas
            </a>
            @if ($session->status === 'CLOSED' && auth()->user()->hasPermission('cash.confirm'))
                <form method="POST" action="{{ route('admin.cash.confirm', $session) }}" onsubmit="return confirm('Confirmar el cierre de esta caja?')">
                    @csrf
                    <button class="btn btn-outline-success" type="submit">
                        <i class="bi bi-check-lg me-1"></i>Confirmar cierre
                    </button>
                </form>
            @endif
            @if (in_array($session->status, ['CLOSED', 'CONFIRMED'], true) && auth()->user()->hasPermission('cash.reopen'))
                <form method="POST" action="{{ route('admin.cash.reopen', $session) }}" onsubmit="return confirm('Reabrir esta caja?')">
                    @csrf
                    <button class="btn btn-outline-warning" type="submit">
                        <i class="bi bi-arrow-repeat me-1"></i>Reabrir
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <p class="text-secondary small mb-1 text-uppercase">Apertura</p>
                    <h2 class="h5 mb-0">RD$ {{ number_format($session->opening_amount, 2) }}</h2>
                    <div class="small text-secondary mt-2">{{ $session->opened_at->format('Y-m-d H:i:s') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <p class="text-secondary small mb-1 text-uppercase">Ventas</p>
                    <h2 class="h5 mb-0 text-success">RD$ {{ number_format($session->sales_total, 2) }}</h2>
                    <div class="small text-secondary mt-2">Tickets: {{ $tickets->total() }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <p class="text-secondary small mb-1 text-uppercase">Premios / Gastos</p>
                    <h2 class="h5 mb-0 text-danger">RD$ {{ number_format($session->prizes_paid_total + $session->expenses_total, 2) }}</h2>
                    <div class="small text-secondary mt-2">
                        Premios: RD$ {{ number_format($session->prizes_paid_total, 2) }} | Gastos: RD$ {{ number_format($session->expenses_total, 2) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-primary">
                <div class="card-body">
                    <p class="text-secondary small mb-1 text-uppercase">Efectivo esperado</p>
                    <h2 class="h5 mb-0 {{ $session->expected_cash >= 0 ? 'text-primary' : 'text-danger' }}">RD$ {{ number_format($session->expected_cash, 2) }}</h2>
                    <div class="small text-secondary mt-2">Estado: <x-status-badge :status="$session->status" /></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 small">
                <div class="col-md-3">
                    <div class="text-secondary text-uppercase mb-1">Cajero</div>
                    <div class="fw-semibold">{{ $session->user?->name ?: '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary text-uppercase mb-1">Abierta por</div>
                    <div class="fw-semibold">{{ $session->openedBy?->name ?: '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary text-uppercase mb-1">Cerrada por</div>
                    <div class="fw-semibold">{{ $session->closedBy?->name ?: '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary text-uppercase mb-1">Confirmada por</div>
                    <div class="fw-semibold">{{ $session->confirmedBy?->name ?: '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary text-uppercase mb-1">Entradas</div>
                    <div class="fw-semibold">RD$ {{ number_format($session->cash_in_total, 2) }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary text-uppercase mb-1">Salidas</div>
                    <div class="fw-semibold">RD$ {{ number_format($session->cash_out_total, 2) }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary text-uppercase mb-1">Anulaciones</div>
                    <div class="fw-semibold">RD$ {{ number_format($session->cancellations_total, 2) }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary text-uppercase mb-1">Notas</div>
                    <div class="fw-semibold">{{ $session->notes ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white">
            <h2 class="h6 mb-0">Movimientos de caja</h2>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Tipo</th>
                        <th>Direccion</th>
                        <th>Metodo</th>
                        <th>Monto</th>
                        <th>Descripcion</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        <tr>
                            <td class="small">{{ $movement->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $movement->user?->name ?: '-' }}</td>
                            <td><span class="badge bg-secondary">{{ $movement->type }}</span></td>
                            <td>
                                <span class="badge {{ $movement->direction === 'IN' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $movement->direction }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $movement->payment_method === 'BANK_TRANSFER' ? 'bg-info' : 'bg-secondary' }}">
                                    {{ $movement->payment_method }}
                                </span>
                            </td>
                            <td class="fw-semibold {{ $movement->direction === 'IN' ? 'text-success' : 'text-danger' }}">
                                RD$ {{ number_format($movement->amount, 2) }}
                            </td>
                            <td class="small">{{ $movement->description }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-3">Sin movimientos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body border-top">
            {{ $movements->links() }}
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <h2 class="h6 mb-0">Tickets vendidos en esta caja</h2>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Ticket</th>
                        <th>Vendido por</th>
                        <th>Modo</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th>Impresiones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td class="small">{{ $ticket->sold_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
                            <td><code>{{ $ticket->ticket_number }}</code></td>
                            <td>{{ $ticket->user?->name ?: '-' }}</td>
                            <td><span class="badge bg-secondary">{{ $ticket->sale_mode }}</span></td>
                            <td class="fw-semibold">RD$ {{ number_format($ticket->total_amount, 2) }}</td>
                            <td><x-status-badge :status="$ticket->status" /></td>
                            <td>{{ $ticket->print_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-3">Sin tickets registrados en esta caja.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body border-top">
            {{ $tickets->links() }}
        </div>
    </div>
@endsection
