@extends('layouts.app')

@section('content')
@php $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2); @endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-star me-2" style="color: var(--argon-warning);"></i>Ganadores</h1>
        <p class="text-secondary mb-0">Premios generados por resultado confirmado.</p>
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
                    <th>Tipo</th>
                    <th>Número</th>
                    <th class="text-end">Jugado</th>
                    <th class="text-end">Multiplicador</th>
                    <th class="text-end">Premio</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($winners as $winner)
                    <tr>
                        <td class="small">{{ $winner->created_at?->format('d/m/Y H:i') }}</td>
                        <td><code>{{ $winner->ticket?->ticket_number }}</code></td>
                        <td>{{ $winner->branch?->name }}</td>
                        <td class="small">{{ $winner->ticketDetail?->draw?->lottery?->name }} - {{ $winner->ticketDetail?->draw?->name }}</td>
                        <td>{{ $winner->ticketDetail?->betType?->name }}</td>
                        <td><code>{{ $winner->number_value }}</code></td>
                        <td class="text-end">{{ $money($winner->amount_played) }}</td>
                        <td class="text-end">{{ number_format((float) $winner->payout_multiplier, 2) }}x</td>
                        <td class="text-end fw-bold text-success">{{ $money($winner->prize_amount) }}</td>
                        <td><x-status-badge :status="$winner->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center py-4 text-secondary">Sin ganadores para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $winners->links() }}</div>
@endsection
