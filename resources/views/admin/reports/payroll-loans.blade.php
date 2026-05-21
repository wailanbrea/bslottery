@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
    $statusBadge = fn (string $s) => match($s) {
        'ACTIVE'   => 'info',
        'PAID_OFF' => 'success',
        'FROZEN'   => 'warning',
        default    => 'secondary',
    };
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-bank me-2" style="color: var(--argon-info);"></i>Préstamos a empleados</h1>
        <p class="text-secondary mb-0">Préstamos activos y saldados por empleado.</p>
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
                    <th>Inicio</th>
                    <th>Empleado</th>
                    <th>Sucursal</th>
                    <th class="text-end">Principal</th>
                    <th class="text-end">Saldo</th>
                    <th class="text-end">Cuota</th>
                    <th class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($loans as $loan)
                    @php
                        $pctPaid = $loan->principal > 0
                            ? round(100 * (1 - $loan->balance / $loan->principal))
                            : 100;
                    @endphp
                    <tr>
                        <td class="text-secondary small">{{ $loan->started_at?->format('d/m/Y') }}</td>
                        <td class="fw-semibold">{{ $loan->employee?->name ?? '—' }}</td>
                        <td>{{ $loan->branch?->name ?? '—' }}</td>
                        <td class="text-end">{{ $money($loan->principal) }}</td>
                        <td class="text-end fw-semibold text-danger">{{ $money($loan->balance) }}</td>
                        <td class="text-end">{{ $money($loan->installment) }}</td>
                        <td class="text-center">
                            <x-status-badge :status="$loan->status" />
                            <div class="progress mt-1" style="height:4px; min-width:60px">
                                <div class="progress-bar bg-success" style="width:{{ $pctPaid }}%"></div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">Sin préstamos.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $loans->links() }}</div>
@endsection
