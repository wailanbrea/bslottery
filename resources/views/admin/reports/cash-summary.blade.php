@extends('layouts.app')

@section('content')
@php $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2); @endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-journal-check me-2" style="color: var(--argon-warning);"></i>Cuadre de caja</h1>
        <p class="text-secondary mb-0">Sesiones de caja filtradas por fecha, sucursal, cajero y estado.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}"><i class="bi bi-arrow-left me-1"></i>Reportes</a>
</div>

@include('admin.reports.partials.filters')

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sucursal</th>
                    <th>Cajero</th>
                    <th>Apertura</th>
                    <th>Cierre</th>
                    <th class="text-end">Ventas</th>
                    <th class="text-end">Premios</th>
                    <th class="text-end">Esperado</th>
                    <th class="text-end">Contado</th>
                    <th class="text-end">Diferencia</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sessions as $session)
                    @php $diff = (float) ($session->counted_cash ?? 0) - (float) $session->expected_cash; @endphp
                    <tr>
                        <td><code>#{{ $session->id }}</code></td>
                        <td>{{ $session->branch?->name }}</td>
                        <td>{{ $session->user?->username }}</td>
                        <td class="small">{{ $session->opened_at?->format('d/m/Y H:i') }}</td>
                        <td class="small">{{ $session->closed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="text-end text-success">{{ $money($session->sales_total) }}</td>
                        <td class="text-end text-danger">{{ $money($session->prizes_paid_total) }}</td>
                        <td class="text-end fw-bold">{{ $money($session->expected_cash) }}</td>
                        <td class="text-end">{{ $money($session->counted_cash ?? 0) }}</td>
                        <td class="text-end fw-bold {{ $diff >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $diff >= 0 ? '+' : '-' }}{{ $money(abs($diff)) }}
                        </td>
                        <td><x-status-badge :status="$session->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-secondary py-4">Sin sesiones para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $sessions->links() }}</div>
@endsection
