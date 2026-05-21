@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-graph-up me-2" style="color: var(--argon-info);"></i>Reportes operativos</h1>
        <p class="text-secondary mb-0">Ventas, premios y caja con filtros por empresa, sucursal y fecha.</p>
    </div>
</div>

@include('admin.reports.partials.filters')

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-secondary small">Tickets vendidos</div>
            <div class="h4 mb-1">{{ number_format($summary['tickets_count']) }}</div>
            <div class="fw-bold text-success">{{ $money($summary['sales_total']) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-secondary small">Tickets anulados</div>
            <div class="h4 mb-1">{{ number_format($summary['cancelled_count']) }}</div>
            <div class="fw-bold text-danger">{{ $money($summary['cancelled_total']) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-secondary small">Premios generados</div>
            <div class="h4 mb-1">{{ number_format($summary['winners_count']) }}</div>
            <div class="fw-bold text-warning">{{ $money($summary['winners_total']) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-secondary small">Premios pagados</div>
            <div class="h4 mb-1">{{ $money($summary['paid_prizes_total']) }}</div>
            <div class="text-secondary small">Cajas: {{ number_format($summary['cash_sessions_count']) }}</div>
        </div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3"><i class="bi bi-cart-check me-2 text-success"></i>Ventas</h2>
                <div class="d-grid gap-2">
                    <a class="btn btn-outline-primary btn-sm text-start" href="{{ route('admin.reports.sales-by-day', request()->query()) }}"><i class="bi bi-calendar3 me-1"></i> Ventas por día</a>
                    <a class="btn btn-outline-primary btn-sm text-start" href="{{ route('admin.reports.sales-by-branch', request()->query()) }}"><i class="bi bi-shop me-1"></i> Ventas por sucursal</a>
                    <a class="btn btn-outline-primary btn-sm text-start" href="{{ route('admin.reports.sales-by-lottery', request()->query()) }}"><i class="bi bi-ticket-perforated me-1"></i> Ventas por lotería/sorteo</a>
                    <a class="btn btn-outline-primary btn-sm text-start" href="{{ route('admin.reports.top-numbers', request()->query()) }}"><i class="bi bi-bar-chart me-1"></i> Números más jugados</a>
                    <a class="btn btn-outline-warning btn-sm text-start" href="{{ route('admin.reports.numbers-near-limit', request()->query()) }}"><i class="bi bi-exclamation-triangle me-1"></i> Números cerca del límite</a>
                    <a class="btn btn-outline-danger btn-sm text-start" href="{{ route('admin.reports.cancelled', request()->query()) }}"><i class="bi bi-x-circle me-1"></i> Tickets anulados</a>
                    <a class="btn btn-outline-secondary btn-sm text-start" href="{{ route('admin.reports.reprinted', request()->query()) }}"><i class="bi bi-printer me-1"></i> Tickets reimpresos</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3"><i class="bi bi-cash-register me-2 text-warning"></i>Caja</h2>
                <div class="d-grid gap-2">
                    <a class="btn btn-outline-primary btn-sm text-start" href="{{ route('admin.reports.cash-summary', request()->query()) }}"><i class="bi bi-journal-check me-1"></i> Cuadre de caja</a>
                    <a class="btn btn-outline-primary btn-sm text-start" href="{{ route('admin.reports.cash-movements', request()->query()) }}"><i class="bi bi-arrow-left-right me-1"></i> Movimientos de caja</a>
                    <a class="btn btn-outline-primary btn-sm text-start" href="{{ route('admin.reports.cash-in-out', request()->query()) }}"><i class="bi bi-arrow-down-up me-1"></i> Entradas y salidas</a>
                    <a class="btn btn-outline-danger btn-sm text-start" href="{{ route('admin.reports.expenses-by-branch', request()->query()) }}"><i class="bi bi-receipt me-1"></i> Gastos por sucursal</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3"><i class="bi bi-trophy me-2 text-info"></i>Premios</h2>
                <div class="d-grid gap-2">
                    <a class="btn btn-outline-primary btn-sm text-start" href="{{ route('admin.reports.winners', request()->query()) }}"><i class="bi bi-star me-1"></i> Ganadores</a>
                    <a class="btn btn-outline-primary btn-sm text-start" href="{{ route('admin.reports.prizes-paid', request()->query()) }}"><i class="bi bi-cash-coin me-1"></i> Premios pagados</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3"><i class="bi bi-bar-chart-line me-2 text-success"></i>Contabilidad</h2>
                <div class="d-grid gap-2">
                    <a class="btn btn-outline-success btn-sm text-start" href="{{ route('admin.reports.income-statement', request()->query()) }}"><i class="bi bi-graph-up me-1"></i> Estado de resultados</a>
                    <a class="btn btn-outline-success btn-sm text-start" href="{{ route('admin.reports.income-vs-expenses', request()->query()) }}"><i class="bi bi-bar-chart-line me-1"></i> Ingresos vs gastos</a>
                    <a class="btn btn-outline-success btn-sm text-start" href="{{ route('admin.reports.profit-by-branch', request()->query()) }}"><i class="bi bi-shop me-1"></i> Utilidad por sucursal</a>
                    <a class="btn btn-outline-success btn-sm text-start" href="{{ route('admin.reports.profit-by-company', request()->query()) }}"><i class="bi bi-building me-1"></i> Utilidad por empresa</a>
                    <a class="btn btn-outline-success btn-sm text-start" href="{{ route('admin.reports.accounts-receivable', request()->query()) }}"><i class="bi bi-arrow-right-circle me-1"></i> Cuentas por cobrar</a>
                    <a class="btn btn-outline-success btn-sm text-start" href="{{ route('admin.reports.accounts-payable', request()->query()) }}"><i class="bi bi-arrow-left-circle me-1"></i> Cuentas por pagar</a>
                    <a class="btn btn-outline-success btn-sm text-start" href="{{ route('admin.reports.cash-flow', request()->query()) }}"><i class="bi bi-water me-1"></i> Flujo de efectivo</a>
                    <a class="btn btn-outline-success btn-sm text-start" href="{{ route('admin.accounting.journal', request()->query()) }}"><i class="bi bi-journal-text me-1"></i> Diario contable</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 mb-3"><i class="bi bi-people me-2 text-secondary"></i>Nómina</h2>
                <div class="d-grid gap-2">
                    <a class="btn btn-outline-secondary btn-sm text-start" href="{{ route('admin.reports.payroll', request()->query()) }}"><i class="bi bi-list-check me-1"></i> Nómina por período</a>
                    <a class="btn btn-outline-secondary btn-sm text-start" href="{{ route('admin.reports.payroll-payments', request()->query()) }}"><i class="bi bi-cash-stack me-1"></i> Pagos a empleados</a>
                    <a class="btn btn-outline-secondary btn-sm text-start" href="{{ route('admin.reports.payroll-advances', request()->query()) }}"><i class="bi bi-wallet2 me-1"></i> Avances</a>
                    <a class="btn btn-outline-secondary btn-sm text-start" href="{{ route('admin.reports.payroll-loans', request()->query()) }}"><i class="bi bi-bank me-1"></i> Préstamos</a>
                    <a class="btn btn-outline-secondary btn-sm text-start" href="{{ route('admin.reports.payroll-deductions', request()->query()) }}"><i class="bi bi-dash-circle me-1"></i> Descuentos</a>
                    <a class="btn btn-outline-secondary btn-sm text-start" href="{{ route('admin.reports.payroll-commissions', request()->query()) }}"><i class="bi bi-percent me-1"></i> Comisiones</a>
                    <a class="btn btn-outline-secondary btn-sm text-start" href="{{ route('admin.reports.payroll-shortages', request()->query()) }}"><i class="bi bi-exclamation-triangle me-1"></i> Faltantes descontados</a>
                    <a class="btn btn-outline-secondary btn-sm text-start" href="{{ route('admin.payroll.index') }}"><i class="bi bi-gear me-1"></i> Gestión de nómina</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
