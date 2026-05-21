@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-shop me-2" style="color: var(--argon-success);"></i>Utilidad por sucursal</h1>
        <p class="text-secondary mb-0">Rentabilidad neta por sucursal: ventas − premios − gastos − nómina.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}">
        <i class="bi bi-arrow-left me-1"></i>Reportes
    </a>
</div>

@include('admin.reports.partials.filters')

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Sucursal</th>
                    <th class="text-end text-success">Ventas</th>
                    <th class="text-end text-danger">Premios</th>
                    <th class="text-end text-warning">Gastos op.</th>
                    <th class="text-end text-secondary">Nómina</th>
                    <th class="text-end text-success">Ut. bruta</th>
                    <th class="text-end fw-bold">Ut. neta</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($byBranch as $row)
                    @php $positive = (float) $row['net'] >= 0; @endphp
                    <tr>
                        <td class="fw-semibold">{{ $row['name'] }}</td>
                        <td class="text-end text-success">{{ $money($row['sales']) }}</td>
                        <td class="text-end text-danger">{{ $money($row['prizes']) }}</td>
                        <td class="text-end text-warning">{{ $money($row['expenses']) }}</td>
                        <td class="text-end text-secondary">{{ $money($row['payroll']) }}</td>
                        <td class="text-end">{{ $money($row['gross']) }}</td>
                        <td class="text-end fw-bold {{ $positive ? 'text-success' : 'text-danger' }}">
                            {{ $money($row['net']) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">Sin datos en el rango seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($byBranch->isNotEmpty())
                @php
                    $totSales   = $byBranch->sum('sales');
                    $totPrizes  = $byBranch->sum('prizes');
                    $totExp     = $byBranch->sum('expenses');
                    $totPayroll = $byBranch->sum('payroll');
                    $totNet     = $byBranch->sum('net');
                @endphp
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>TOTAL</td>
                        <td class="text-end text-success">{{ $money($totSales) }}</td>
                        <td class="text-end text-danger">{{ $money($totPrizes) }}</td>
                        <td class="text-end text-warning">{{ $money($totExp) }}</td>
                        <td class="text-end text-secondary">{{ $money($totPayroll) }}</td>
                        <td class="text-end">—</td>
                        <td class="text-end {{ (float) $totNet >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($totNet) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
