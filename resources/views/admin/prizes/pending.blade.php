@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1"><i class="bi bi-cash-coin me-2" style="color: var(--argon-success);"></i>Premios pendientes</h1>
        <p class="text-secondary mb-0">Ganadores con pagos liberados listos para cobro.</p>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Sorteo</th>
                        <th>Jugada</th>
                        <th>Número</th>
                        <th>Posición</th>
                        <th>Jugado</th>
                        <th>Multiplicador</th>
                        <th class="text-end">Premio</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($winners as $winner)
                        <tr>
                            <td><code>{{ $winner->ticket->ticket_number }}</code></td>
                            <td class="small">{{ $winner->ticketDetail->draw->name }} — {{ $winner->ticketDetail->draw->lottery->name }}</td>
                            <td>{{ $winner->betType->name }}</td>
                            <td><code>{{ $winner->number_value }}</code></td>
                            <td>{{ $winner->matched_position ?: '—' }}</td>
                            <td>RD$ {{ number_format($winner->amount_played, 2) }}</td>
                            <td><span class="badge bg-success">{{ $winner->payout_multiplier }}x</span></td>
                            <td class="text-end fw-bold text-success">RD$ {{ number_format($winner->prize_amount, 2) }}</td>
                            <td><x-status-badge :status="$winner->status" /></td>
                            <td class="text-end">
                                @if ($winner->status === 'RELEASED' && auth()->user()->hasPermission('prizes.pay'))
                                    <form method="POST" action="{{ route('admin.prizes.pay', $winner) }}" onsubmit="return confirm('¿Pagar RD$ {{ number_format($winner->prize_amount, 2) }}?')">
                                        @csrf
                                        <button class="btn btn-sm btn-success" type="submit">
                                            <i class="bi bi-cash me-1"></i>Pagar
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-secondary py-4">No hay premios pendientes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $winners->links() }}</div>
@endsection
