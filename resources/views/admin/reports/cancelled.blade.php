@extends('layouts.app')

@section('content')
@php $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2); @endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-x-circle me-2" style="color: var(--argon-danger);"></i>Tickets anulados</h1>
        <p class="text-secondary mb-0">Anulaciones auditables por rango, sucursal y cajero.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}"><i class="bi bi-arrow-left me-1"></i>Reportes</a>
</div>

@include('admin.reports.partials.filters')

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Sucursal</th>
                    <th>Cajero</th>
                    <th class="text-end">Monto</th>
                    <th>Anulado por</th>
                    <th>Motivo</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    <tr>
                        <td><code>{{ $ticket->ticket_number }}</code></td>
                        <td>{{ $ticket->branch?->name }}</td>
                        <td>{{ $ticket->user?->username }}</td>
                        <td class="text-end text-danger fw-bold">{{ $money($ticket->total_amount) }}</td>
                        <td>{{ $ticket->cancelledBy?->username }}</td>
                        <td class="small">{{ Str::limit($ticket->cancel_reason, 60) }}</td>
                        <td class="small">{{ $ticket->cancelled_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4 text-secondary">Sin anulaciones para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $tickets->links() }}</div>
@endsection
