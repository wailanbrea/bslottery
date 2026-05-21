@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-cash-register me-2" style="color: var(--argon-success);"></i>Caja actual</h1>
            <p class="text-secondary mb-0">{{ $session->branch->name }} — {{ $session->user->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.cash.movement') }}">
                <i class="bi bi-plus-slash-minus me-1"></i>Movimiento
            </a>
            @if (auth()->user()->hasPermission('cash.transfers.view'))
                <a class="btn btn-outline-info" href="{{ route('admin.cash.transfers.index') }}">
                    <i class="bi bi-bank me-1"></i>Transferencias
                </a>
            @endif
            @if (auth()->user()->hasPermission('cash.funding.view'))
                <a class="btn btn-outline-success" href="{{ route('admin.cash.funding.index') }}">
                    <i class="bi bi-truck me-1"></i>Refuerzos
                </a>
            @endif
            @if (auth()->user()->hasPermission('cash.incidents.view'))
                <a class="btn btn-outline-warning" href="{{ route('admin.cash.incidents.index') }}">
                    <i class="bi bi-exclamation-triangle me-1"></i>Incidencias
                </a>
            @endif
            <a class="btn btn-outline-danger" href="{{ route('admin.cash.close') }}">
                <i class="bi bi-lock me-1"></i>Cerrar caja
            </a>
        </div>
    </div>

    @php $session->recalculateExpectedCash(); @endphp

    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <p class="text-secondary small mb-1 text-uppercase">Apertura</p>
                    <h2 class="h5 mb-0">RD$ {{ number_format($session->opening_amount, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <p class="text-secondary small mb-1 text-uppercase">Ventas</p>
                    <h2 class="h5 mb-0 text-success">RD$ {{ number_format($session->sales_total, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <p class="text-secondary small mb-1 text-uppercase">Premios pagados</p>
                    <h2 class="h5 mb-0 text-danger">RD$ {{ number_format($session->prizes_paid_total, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <p class="text-secondary small mb-1 text-uppercase">Gastos</p>
                    <h2 class="h5 mb-0 text-danger">RD$ {{ number_format($session->expenses_total, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <p class="text-secondary small mb-1 text-uppercase">Entradas</p>
                    <h2 class="h5 mb-0 text-info">RD$ {{ number_format($session->cash_in_total, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <p class="text-secondary small mb-1 text-uppercase">Salidas</p>
                    <h2 class="h5 mb-0 text-warning">RD$ {{ number_format($session->cash_out_total, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <p class="text-secondary small mb-1 text-uppercase">Anulaciones</p>
                    <h2 class="h5 mb-0 text-secondary">RD$ {{ number_format($session->cancellations_total, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-primary">
                <div class="card-body">
                    <p class="text-secondary small mb-1 text-uppercase">Efectivo esperado</p>
                    <h2 class="h5 mb-0 {{ $session->expected_cash >= 0 ? 'text-primary' : 'text-danger' }}">RD$ {{ number_format($session->expected_cash, 2) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Últimos movimientos</h2>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.cash.index') }}">
                <i class="bi bi-clock-history me-1"></i>Historial
            </a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Dirección</th>
                        <th>Metodo</th>
                        <th>Monto</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($session->movements as $movement)
                        <tr>
                            <td class="small">{{ $movement->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                @php
                                    $typeBadge = match($movement->type) {
                                        'SALE' => 'bg-success',
                                        'CANCELLATION' => 'bg-secondary',
                                        'PRIZE_PAYMENT' => 'bg-danger',
                                        'CASH_IN' => 'bg-info',
                                        'CASH_OUT' => 'bg-warning',
                                        'EXPENSE' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $typeBadge }}">{{ $movement->type }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $movement->direction === 'IN' ? 'bg-success' : 'bg-danger' }}">{{ $movement->direction }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $movement->payment_method === 'BANK_TRANSFER' ? 'bg-info' : 'bg-secondary' }}">{{ $movement->payment_method }}</span>
                            </td>
                            <td class="fw-semibold {{ $movement->direction === 'IN' ? 'text-success' : 'text-danger' }}">RD$ {{ number_format($movement->amount, 2) }}</td>
                            <td class="small">{{ $movement->description }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-3">Sin movimientos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
