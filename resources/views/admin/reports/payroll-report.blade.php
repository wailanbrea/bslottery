@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
    $statusBadge = fn (string $s) => match($s) {
        'DRAFT'    => 'secondary',
        'APPROVED' => 'warning',
        'PAID'     => 'success',
        default    => 'light',
    };
    $freqLabel = fn (string $f) => match($f) {
        'WEEKLY'    => 'Semanal',
        'BIWEEKLY'  => 'Quincenal',
        'MONTHLY'   => 'Mensual',
        default     => $f,
    };
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-people me-2" style="color: var(--argon-info);"></i>Nómina por período</h1>
        <p class="text-secondary mb-0">Períodos de nómina con totales de bruto, deducciones y neto.</p>
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
                    <th>Frecuencia</th>
                    <th class="text-center">Empleados</th>
                    <th class="text-end">Bruto</th>
                    <th class="text-end">Deducciones</th>
                    <th class="text-end">Neto</th>
                    <th class="text-center">Estado</th>
                    <th>Pagado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($periods as $period)
                    <tr>
                        <td class="fw-semibold">
                            {{ $period->period_start->format('d/m/Y') }} –
                            {{ $period->period_end->format('d/m/Y') }}
                        </td>
                        <td>{{ $freqLabel($period->frequency) }}</td>
                        <td class="text-center">{{ $period->details_count }}</td>
                        <td class="text-end">{{ $money($period->total_gross) }}</td>
                        <td class="text-end text-danger">{{ $money($period->total_deductions) }}</td>
                        <td class="text-end fw-bold text-success">{{ $money($period->total_net) }}</td>
                        <td class="text-center">
                            <x-status-badge :status="$period->status" />
                        </td>
                        <td class="text-secondary small">{{ $period->paid_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>
                            <a href="{{ route('admin.payroll.show', $period) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-secondary py-4">Sin períodos de nómina en el rango seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $periods->links() }}</div>
@endsection
