@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-water me-2" style="color: var(--argon-info);"></i>Flujo de efectivo</h1>
        <p class="text-secondary mb-0">Movimientos de caja agrupados por día según sesiones de caja.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}">
        <i class="bi bi-arrow-left me-1"></i>Reportes
    </a>
</div>

@include('admin.reports.partials.filters')

<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card text-center h-100"><div class="card-body p-2">
            <div class="text-secondary small">Apertura total</div>
            <div class="fw-semibold mt-1">{{ $money($totals['opening']) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center h-100"><div class="card-body p-2">
            <div class="text-secondary small">Ventas</div>
            <div class="fw-semibold text-success mt-1">{{ $money($totals['sales']) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center h-100"><div class="card-body p-2">
            <div class="text-secondary small">Entradas</div>
            <div class="fw-semibold text-success mt-1">{{ $money($totals['cash_in']) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center h-100"><div class="card-body p-2">
            <div class="text-secondary small">Premios</div>
            <div class="fw-semibold text-danger mt-1">{{ $money($totals['prizes_paid']) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center h-100"><div class="card-body p-2">
            <div class="text-secondary small">Salidas</div>
            <div class="fw-semibold text-danger mt-1">{{ $money($totals['cash_out']) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center h-100"><div class="card-body p-2">
            <div class="text-secondary small">Gastos</div>
            <div class="fw-semibold text-warning mt-1">{{ $money($totals['expenses']) }}</div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th class="text-center">Sesiones</th>
                    <th class="text-end">Apertura</th>
                    <th class="text-end text-success">Ventas</th>
                    <th class="text-end text-success">Entradas</th>
                    <th class="text-end text-danger">Premios</th>
                    <th class="text-end text-danger">Salidas</th>
                    <th class="text-end text-warning">Gastos</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($byDay as $row)
                    @php
                        $net = $row['sales'] + $row['cash_in'] - $row['prizes_paid'] - $row['cash_out'] - $row['expenses'];
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                        <td class="text-center">{{ $row['sessions_count'] }}</td>
                        <td class="text-end text-secondary">{{ $money($row['opening']) }}</td>
                        <td class="text-end text-success">{{ $money($row['sales']) }}</td>
                        <td class="text-end text-success">{{ $money($row['cash_in']) }}</td>
                        <td class="text-end text-danger">{{ $money($row['prizes_paid']) }}</td>
                        <td class="text-end text-danger">{{ $money($row['cash_out']) }}</td>
                        <td class="text-end text-warning">{{ $money($row['expenses']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-secondary py-4">Sin sesiones de caja en el rango seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($byDay->isNotEmpty())
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>TOTAL</td>
                        <td></td>
                        <td class="text-end text-secondary">{{ $money($totals['opening']) }}</td>
                        <td class="text-end text-success">{{ $money($totals['sales']) }}</td>
                        <td class="text-end text-success">{{ $money($totals['cash_in']) }}</td>
                        <td class="text-end text-danger">{{ $money($totals['prizes_paid']) }}</td>
                        <td class="text-end text-danger">{{ $money($totals['cash_out']) }}</td>
                        <td class="text-end text-warning">{{ $money($totals['expenses']) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
