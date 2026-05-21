@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1"><i class="bi bi-clock-history me-2" style="color: var(--argon-info);"></i>Historial de premios</h1>
        <p class="text-secondary mb-0">Premios pagados.</p>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Ticket</th>
                        <th>Número</th>
                        <th>Sucursal</th>
                        <th class="text-end">Monto</th>
                        <th>Pagado por</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="small">{{ $payment->paid_at->format('Y-m-d H:i') }}</td>
                            <td><code>{{ $payment->ticket->ticket_number }}</code></td>
                            <td><code>{{ $payment->winnerTicket->number_value }}</code></td>
                            <td>{{ $payment->branch?->name ?: '—' }}</td>
                            <td class="text-end fw-semibold text-success">RD$ {{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->paidBy?->username ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">No hay pagos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $payments->links() }}</div>
@endsection
