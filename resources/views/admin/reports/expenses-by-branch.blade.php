@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-receipt me-2" style="color: var(--argon-danger);"></i>Gastos por sucursal</h1>
        <p class="text-secondary mb-0">Salidas de caja operativas por sucursal (excluye nómina y premios).</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}">
        <i class="bi bi-arrow-left me-1"></i>Reportes
    </a>
</div>

@include('admin.reports.partials.filters')

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Total gastos</div>
            <div class="h5 fw-bold text-danger mt-1">{{ $money($grandTotal) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Sucursales con gastos</div>
            <div class="h5 mt-1">{{ $byBranch->count() }}</div>
        </div></div>
    </div>
</div>

@forelse ($byBranch as $row)
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">{{ $row['name'] }}</span>
            <span class="fw-bold text-danger">{{ $money($row['total']) }}</span>
        </div>
        @if ($row['breakdown']->isNotEmpty())
            <div class="card-body py-2">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Categoría</th><th class="text-end">Subtotal</th></tr></thead>
                        <tbody>
                            @foreach ($row['breakdown'] as $type => $subtotal)
                                <tr>
                                    <td><span class="badge bg-light text-dark border">{{ $type }}</span></td>
                                    <td class="text-end text-danger">{{ $money($subtotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@empty
    <div class="card">
        <div class="card-body text-center text-secondary py-5">Sin gastos en el rango seleccionado.</div>
    </div>
@endforelse
@endsection
