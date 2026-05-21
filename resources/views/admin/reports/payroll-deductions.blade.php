@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-dash-circle me-2" style="color: var(--argon-danger);"></i>Descuentos en nómina</h1>
        <p class="text-secondary mb-0">Desglose de deducciones por avances, préstamos, faltantes y otros por empleado.</p>
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
                    <th>Período</th>
                    <th>Empleado</th>
                    <th>Sucursal</th>
                    <th class="text-end">Avance</th>
                    <th class="text-end">Préstamo</th>
                    <th class="text-end">Faltante</th>
                    <th class="text-end">Otros</th>
                    <th class="text-end fw-bold text-danger">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($deductions as $detail)
                    <tr>
                        <td class="text-secondary small">
                            {{ $detail->period?->period_start?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="fw-semibold">{{ $detail->employee?->name ?? '—' }}</td>
                        <td>{{ $detail->branch?->name ?? '—' }}</td>
                        <td class="text-end">{{ $money($detail->advance_deduction) }}</td>
                        <td class="text-end">{{ $money($detail->loan_deduction) }}</td>
                        <td class="text-end">{{ $money($detail->cash_shortage) }}</td>
                        <td class="text-end">{{ $money($detail->other_deductions) }}</td>
                        <td class="text-end fw-bold text-danger">{{ $money($detail->total_deductions) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-secondary py-4">Sin descuentos en el rango seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $deductions->links() }}</div>
@endsection
