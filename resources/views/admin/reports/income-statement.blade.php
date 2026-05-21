@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
    $isNeg = fn ($amount) => bccomp((string) $amount, '0', 2) < 0;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-bar-chart-line me-2" style="color: var(--argon-success);"></i>Estado de resultados</h1>
        <p class="text-secondary mb-0">Ingresos, gastos y utilidad del período seleccionado.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}">
        <i class="bi bi-arrow-left me-1"></i>Reportes
    </a>
</div>

@include('admin.reports.partials.filters')

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <div class="card h-100 border-success">
            <div class="card-body">
                <div class="text-secondary small">Ventas</div>
                <div class="fw-bold text-success fs-6">{{ $money($salesTotal) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card h-100 border-danger">
            <div class="card-body">
                <div class="text-secondary small">Premios pagados</div>
                <div class="fw-bold text-danger fs-6">{{ $money($prizesTotal) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card h-100 border-warning">
            <div class="card-body">
                <div class="text-secondary small">Gastos operativos</div>
                <div class="fw-bold text-warning fs-6">{{ $money($expensesTotal) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card h-100 border-warning">
            <div class="card-body">
                <div class="text-secondary small">Nómina pagada</div>
                <div class="fw-bold text-warning fs-6">{{ $money($payrollTotal) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card h-100 border-info">
            <div class="card-body">
                <div class="text-secondary small">Utilidad bruta</div>
                <div class="fw-bold fs-6 {{ $isNeg($grossProfit) ? 'text-danger' : 'text-info' }}">
                    {{ $money($grossProfit) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card h-100 {{ $isNeg($netProfit) ? 'border-danger' : 'border-success' }}">
            <div class="card-body">
                <div class="text-secondary small">Utilidad neta</div>
                <div class="fw-bold fs-5 {{ $isNeg($netProfit) ? 'text-danger' : 'text-success' }}">
                    {{ $money($netProfit) }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Income statement vertical --}}
<div class="row g-4">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header fw-bold"><i class="bi bi-receipt me-2"></i>Resumen del período</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <tbody>
                        <tr>
                            <td class="text-success fw-semibold">Ingresos por ventas</td>
                            <td class="text-end fw-bold text-success">{{ $money($salesTotal) }}</td>
                        </tr>
                        <tr>
                            <td class="text-danger ps-4 small">— Premios pagados</td>
                            <td class="text-end text-danger small">{{ $money($prizesTotal) }}</td>
                        </tr>
                        <tr class="table-light fw-bold">
                            <td>Utilidad bruta</td>
                            <td class="text-end {{ $isNeg($grossProfit) ? 'text-danger' : 'text-success' }}">{{ $money($grossProfit) }}</td>
                        </tr>
                        <tr>
                            <td class="text-warning ps-4 small">— Gastos operativos</td>
                            <td class="text-end text-warning small">{{ $money($expensesTotal) }}</td>
                        </tr>
                        <tr>
                            <td class="text-warning ps-4 small">— Nómina</td>
                            <td class="text-end text-warning small">{{ $money($payrollTotal) }}</td>
                        </tr>
                        <tr class="table-active fw-bold">
                            <td>Utilidad neta</td>
                            <td class="text-end fs-5 {{ $isNeg($netProfit) ? 'text-danger' : 'text-success' }}">{{ $money($netProfit) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Branch breakdown --}}
    <div class="col-md-7">
        <div class="card">
            <div class="card-header fw-bold"><i class="bi bi-shop me-2"></i>Detalle por sucursal</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Sucursal</th>
                            <th class="text-end">Ventas</th>
                            <th class="text-end">Premios</th>
                            <th class="text-end">Gastos</th>
                            <th class="text-end">Utilidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byBranch as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['name'] }}</td>
                                <td class="text-end text-success">{{ $money($row['sales']) }}</td>
                                <td class="text-end text-danger">{{ $money($row['prizes']) }}</td>
                                <td class="text-end text-warning">{{ $money($row['expenses']) }}</td>
                                <td class="text-end fw-bold {{ $isNeg($row['net']) ? 'text-danger' : 'text-success' }}">
                                    {{ $money($row['net']) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-secondary py-4">Sin datos para los filtros seleccionados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
