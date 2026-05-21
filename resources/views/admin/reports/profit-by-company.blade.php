@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-building me-2" style="color: var(--argon-success);"></i>Utilidad por empresa</h1>
        <p class="text-secondary mb-0">Resumen consolidado de rentabilidad de la empresa en el período.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}">
        <i class="bi bi-arrow-left me-1"></i>Reportes
    </a>
</div>

@include('admin.reports.partials.filters')

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Ventas brutas</div>
            <div class="h5 fw-bold text-success mt-1">{{ $money($sales) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Premios pagados</div>
            <div class="h5 fw-bold text-danger mt-1">{{ $money($prizes) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Utilidad bruta</div>
            <div class="h5 fw-bold {{ (float) $grossProfit >= 0 ? 'text-success' : 'text-danger' }} mt-1">{{ $money($grossProfit) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center h-100"><div class="card-body">
            <div class="text-secondary small">Utilidad neta</div>
            <div class="h5 fw-bold {{ (float) $netProfit >= 0 ? 'text-success' : 'text-danger' }} mt-1">{{ $money($netProfit) }}</div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header fw-semibold">Estado de resultados</div>
            <table class="table mb-0">
                <tbody>
                    <tr>
                        <td>Ventas brutas</td>
                        <td class="text-end text-success fw-semibold">{{ $money($sales) }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-secondary">Tickets anulados</td>
                        <td class="text-end text-danger">– {{ $money($cancelledTickets) }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-secondary">Premios pagados</td>
                        <td class="text-end text-danger">– {{ $money($prizes) }}</td>
                    </tr>
                    <tr class="table-light">
                        <td class="fw-semibold">Utilidad bruta</td>
                        <td class="text-end fw-semibold {{ (float) $grossProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($grossProfit) }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-secondary">Gastos operativos</td>
                        <td class="text-end text-danger">– {{ $money($expenses) }}</td>
                    </tr>
                    <tr>
                        <td class="ps-4 text-secondary">Nómina</td>
                        <td class="text-end text-danger">– {{ $money($payroll) }}</td>
                    </tr>
                    <tr class="table-light fw-bold">
                        <td>Utilidad neta</td>
                        <td class="text-end {{ (float) $netProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($netProfit) }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary">Margen neto</td>
                        <td class="text-end {{ (float) $margin >= 0 ? 'text-success' : 'text-danger' }}">{{ $margin }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
