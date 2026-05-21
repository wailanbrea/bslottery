@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-exclamation-triangle me-2" style="color: var(--argon-danger);"></i>Faltantes descontados</h1>
        <p class="text-secondary mb-0">Faltantes de caja que fueron descontados del salario neto del empleado.</p>
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
                    <th class="text-end text-danger">Faltante descontado</th>
                    <th class="text-end">Neto pagado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shortages as $detail)
                    <tr>
                        <td class="text-secondary small">
                            @if ($detail->period)
                                {{ $detail->period->period_start->format('d/m/Y') }} –
                                {{ $detail->period->period_end->format('d/m/Y') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="fw-semibold">{{ $detail->employee?->name ?? '—' }}</td>
                        <td>{{ $detail->branch?->name ?? '—' }}</td>
                        <td class="text-end fw-bold text-danger">{{ $money($detail->cash_shortage) }}</td>
                        <td class="text-end text-success">{{ $money($detail->net_pay) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">Sin faltantes descontados en el rango seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $shortages->links() }}</div>
@endsection
