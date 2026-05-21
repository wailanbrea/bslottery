@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-bar-chart-line me-2" style="color: var(--argon-info);"></i>Ingresos vs gastos</h1>
        <p class="text-secondary mb-0">Balance por cuenta contable: créditos de ingresos vs débitos de gastos.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}">
        <i class="bi bi-arrow-left me-1"></i>Reportes
    </a>
</div>

@include('admin.reports.partials.filters')

<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Total ingresos</div>
            <div class="h5 fw-bold text-success mt-1">{{ $money($totalIncome) }}</div>
        </div></div>
    </div>
    <div class="col-4">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Total gastos</div>
            <div class="h5 fw-bold text-danger mt-1">{{ $money($totalExpense) }}</div>
        </div></div>
    </div>
    <div class="col-4">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Resultado neto</div>
            <div class="h5 fw-bold mt-1 {{ (float) $netResult >= 0 ? 'text-success' : 'text-danger' }}">
                {{ $money($netResult) }}
            </div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    @foreach (['INCOME' => ['success', 'Ingresos'], 'EXPENSE' => ['danger', 'Gastos']] as $type => [$color, $label])
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header fw-semibold text-{{ $color }}">{{ $label }}</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Cuenta</th><th class="text-end">Crédito</th><th class="text-end">Débito</th></tr></thead>
                        <tbody>
                            @forelse ($rows->where('type', $type) as $row)
                                <tr>
                                    <td><span class="badge bg-light text-dark border me-1">{{ $row['code'] }}</span>{{ $row['name'] }}</td>
                                    <td class="text-end text-success">{{ $money($row['credit']) }}</td>
                                    <td class="text-end text-danger">{{ $money($row['debit']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-secondary py-3">Sin movimientos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
