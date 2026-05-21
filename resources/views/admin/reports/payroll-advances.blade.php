@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
    $statusBadge = fn (string $s) => match($s) {
        'PENDING'  => 'warning',
        'APPROVED' => 'info',
        'PAID'     => 'success',
        'REJECTED' => 'danger',
        default    => 'secondary',
    };
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-wallet2 me-2" style="color: var(--argon-warning);"></i>Avances a empleados</h1>
        <p class="text-secondary mb-0">Avances solicitados y pagados en el período seleccionado.</p>
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
                    <th>Fecha solicitud</th>
                    <th>Empleado</th>
                    <th>Sucursal</th>
                    <th class="text-end">Monto</th>
                    <th class="text-center">Estado</th>
                    <th>Pagado</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($advances as $adv)
                    <tr>
                        <td class="text-secondary small">{{ $adv->requested_at?->format('d/m/Y') }}</td>
                        <td class="fw-semibold">{{ $adv->employee?->name ?? '—' }}</td>
                        <td>{{ $adv->branch?->name ?? '—' }}</td>
                        <td class="text-end fw-semibold text-warning">{{ $money($adv->amount) }}</td>
                        <td class="text-center">
                            <x-status-badge :status="$adv->status" />
                        </td>
                        <td class="text-secondary small">{{ $adv->paid_at?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-secondary small">{{ $adv->notes ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">Sin avances en el rango seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $advances->links() }}</div>
@endsection
