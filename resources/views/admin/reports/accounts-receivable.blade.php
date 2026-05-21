@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-arrow-right-circle me-2" style="color: var(--argon-info);"></i>Cuentas por cobrar</h1>
        <p class="text-secondary mb-0">Saldos pendientes de préstamos activos y avances aprobados sin pagar.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}">
        <i class="bi bi-arrow-left me-1"></i>Reportes
    </a>
</div>

@include('admin.reports.partials.filters')

<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Saldo préstamos</div>
            <div class="h5 fw-bold text-danger mt-1">{{ $money($totalLoans) }}</div>
        </div></div>
    </div>
    <div class="col-4">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Avances aprobados</div>
            <div class="h5 fw-bold text-warning mt-1">{{ $money($totalAdvances) }}</div>
        </div></div>
    </div>
    <div class="col-4">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Total por cobrar</div>
            <div class="h5 fw-bold text-info mt-1">{{ $money($grandTotal) }}</div>
        </div></div>
    </div>
</div>

@if ($loans->isNotEmpty())
    <h2 class="h6 mb-2 text-danger"><i class="bi bi-bank me-1"></i>Préstamos activos</h2>
    <div class="card mb-4">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Sucursal</th>
                        <th class="text-end">Principal</th>
                        <th class="text-end text-danger">Saldo</th>
                        <th class="text-end">Cuota</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($loans as $loan)
                        <tr>
                            <td class="fw-semibold">{{ $loan->employee?->name ?? '—' }}</td>
                            <td>{{ $loan->branch?->name ?? '—' }}</td>
                            <td class="text-end">{{ $money($loan->principal) }}</td>
                            <td class="text-end fw-bold text-danger">{{ $money($loan->balance) }}</td>
                            <td class="text-end">{{ $money($loan->installment) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if ($advances->isNotEmpty())
    <h2 class="h6 mb-2 text-warning"><i class="bi bi-wallet2 me-1"></i>Avances pendientes de cobro</h2>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Sucursal</th>
                        <th>Fecha solicitud</th>
                        <th class="text-end text-warning">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($advances as $adv)
                        <tr>
                            <td class="fw-semibold">{{ $adv->employee?->name ?? '—' }}</td>
                            <td>{{ $adv->branch?->name ?? '—' }}</td>
                            <td class="text-secondary small">{{ $adv->requested_at?->format('d/m/Y') }}</td>
                            <td class="text-end fw-semibold text-warning">{{ $money($adv->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if ($loans->isEmpty() && $advances->isEmpty())
    <div class="card">
        <div class="card-body text-center text-secondary py-5">Sin cuentas por cobrar.</div>
    </div>
@endif
@endsection
