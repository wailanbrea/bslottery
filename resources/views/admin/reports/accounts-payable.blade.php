@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-arrow-left-circle me-2" style="color: var(--argon-danger);"></i>Cuentas por pagar</h1>
        <p class="text-secondary mb-0">Premios ganados aún no cobrados por los clientes.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}">
        <i class="bi bi-arrow-left me-1"></i>Reportes
    </a>
</div>

@include('admin.reports.partials.filters')

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Premios pendientes</div>
            <div class="h5 fw-bold text-danger mt-1">{{ number_format($unpaidWinners->count()) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Total por pagar</div>
            <div class="h5 fw-bold text-danger mt-1">{{ $money($totalOwed) }}</div>
        </div></div>
    </div>
</div>

@if ($byBranch->isNotEmpty())
    <h2 class="h6 mb-2">Resumen por sucursal</h2>
    <div class="card mb-4">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Sucursal</th><th class="text-center">Premios</th><th class="text-end text-danger">Total</th></tr></thead>
                <tbody>
                    @foreach ($byBranch as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-center">{{ $row['count'] }}</td>
                            <td class="text-end fw-semibold text-danger">{{ $money($row['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<h2 class="h6 mb-2">Detalle de premios pendientes</h2>
<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Sucursal</th>
                    <th>Número</th>
                    <th>Sorteo</th>
                    <th class="text-end text-danger">Premio</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($unpaidWinners as $winner)
                    <tr>
                        <td class="text-secondary small">{{ $winner->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $winner->branch?->name ?? '—' }}</td>
                        <td class="fw-semibold">{{ $winner->number_value }}</td>
                        <td class="small">{{ $winner->draw?->name ?? '—' }}</td>
                        <td class="text-end fw-bold text-danger">{{ $money($winner->prize_amount) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">Sin premios pendientes en el rango seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
