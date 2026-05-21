@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-arrow-down-up me-2" style="color: var(--argon-info);"></i>Entradas y salidas</h1>
        <p class="text-secondary mb-0">Resumen de entradas (IN) y salidas (OUT) de caja por sucursal.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}">
        <i class="bi bi-arrow-left me-1"></i>Reportes
    </a>
</div>

@include('admin.reports.partials.filters')

<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Total entradas</div>
            <div class="h5 fw-bold text-success mt-1">{{ $money($totalIn) }}</div>
        </div></div>
    </div>
    <div class="col-4">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Total salidas</div>
            <div class="h5 fw-bold text-danger mt-1">{{ $money($totalOut) }}</div>
        </div></div>
    </div>
    <div class="col-4">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Balance neto</div>
            <div class="h5 fw-bold mt-1 {{ (float) $totalBalance >= 0 ? 'text-success' : 'text-danger' }}">
                {{ $money($totalBalance) }}
            </div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Sucursal</th>
                    <th class="text-end text-success">Entradas</th>
                    <th class="text-end text-danger">Salidas</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($byBranch as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['name'] }}</td>
                        <td class="text-end text-success">{{ $money($row['in']) }}</td>
                        <td class="text-end text-danger">{{ $money($row['out']) }}</td>
                        <td class="text-end fw-bold {{ (float) $row['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $money($row['balance']) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-secondary py-4">Sin movimientos en el rango seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($byBranch->isNotEmpty())
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>TOTAL</td>
                        <td class="text-end text-success">{{ $money($totalIn) }}</td>
                        <td class="text-end text-danger">{{ $money($totalOut) }}</td>
                        <td class="text-end {{ (float) $totalBalance >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($totalBalance) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
