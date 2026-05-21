@extends('layouts.app')

@section('content')
@php $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2); @endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-cash-coin me-2" style="color: var(--argon-success);"></i>Premios pagados</h1>
        <p class="text-secondary mb-0">Pagos realizados con caja abierta y usuario pagador.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}"><i class="bi bi-arrow-left me-1"></i>Reportes</a>
</div>

@include('admin.reports.partials.filters')

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Ticket</th>
                    <th>Sucursal</th>
                    <th>Sorteo</th>
                    <th>Número</th>
                    <th class="text-end">Monto</th>
                    <th>Pagado por</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td class="small">{{ $payment->paid_at?->format('d/m/Y H:i') }}</td>
                        <td><code>{{ $payment->ticket?->ticket_number }}</code></td>
                        <td>{{ $payment->branch?->name }}</td>
                        <td class="small">{{ $payment->winnerTicket?->ticketDetail?->draw?->lottery?->name }} - {{ $payment->winnerTicket?->ticketDetail?->draw?->name }}</td>
                        <td><code>{{ $payment->winnerTicket?->number_value }}</code></td>
                        <td class="text-end fw-bold text-success">{{ $money($payment->amount) }}</td>
                        <td>{{ $payment->paidBy?->username }}</td>
                        <td><x-status-badge :status="$payment->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-4 text-secondary">Sin pagos para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $payments->links() }}</div>
@endsection
