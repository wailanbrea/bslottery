@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-receipt me-2" style="color: var(--argon-primary);"></i>Ticket #{{ $ticket->ticket_number }}</h1>
            <p class="text-secondary mb-0">{{ $ticket->sold_at->format('Y-m-d H:i:s') }} — {{ $ticket->branch->name }}</p>
        </div>
        <div class="d-flex gap-2">
            @if (auth()->user()->hasPermission('sales.reprint') && $ticket->isReprintable())
                <button id="reprintTicketBtn" class="btn btn-outline-info" type="button"
                        data-reprint-url="{{ route('admin.tickets.reprint', $ticket, false) }}">
                    <i class="bi bi-printer me-1"></i>Reimprimir
                </button>
            @endif
            <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard?.writeText('{{ $ticket->ticket_number }}')">
                <i class="bi bi-copy me-1"></i>Copiar número
            </button>
            <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard?.writeText('ticket:{{ $ticket->uuid }}')">
                <i class="bi bi-qr-code me-1"></i>Copiar QR
            </button>
            @if (auth()->user()->hasPermission('sales.cancel') && $ticket->isCancellable())
                <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-x-circle me-1"></i>Anular
                </button>
            @endif
            <a class="btn btn-outline-secondary" href="{{ route('admin.tickets.index') }}">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            @php
                $releasedWinners = $ticket->winnerTickets->where('status', 'RELEASED');
                $releasedTotal = $releasedWinners->sum(fn ($winner) => (float) $winner->prize_amount);
            @endphp

            @if ($releasedWinners->isNotEmpty() && auth()->user()->hasPermission('prizes.pay'))
                <div class="card border-success mb-4">
                    <div class="card-body d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <h2 class="h6 mb-1 text-success">Premios liberados pendientes</h2>
                            <p class="mb-0 text-secondary">
                                {{ $releasedWinners->count() }} premio(s), total RD$ {{ number_format($releasedTotal, 2) }}.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.tickets.pay-released-prizes', $ticket) }}" onsubmit="return confirm('Pagar este ticket completo por RD$ {{ number_format($releasedTotal, 2) }}?')">
                            @csrf
                            <button class="btn btn-success" type="submit">
                                <i class="bi bi-cash-coin me-1"></i>Pagar ticket
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header bg-white">
                    <h2 class="h6 mb-0">Jugadas</h2>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Lotería</th>
                                <th>Sorteo</th>
                                <th>Jugada</th>
                                <th>Número</th>
                                <th>Monto</th>
                                <th>Multiplicador</th>
                                <th class="text-end">Premio posible</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ticket->details as $detail)
                                <tr>
                                    <td>{{ $detail->draw->lottery->name }}</td>
                                    <td class="small">{{ $detail->draw->name }}</td>
                                    <td>{{ $detail->betType->name }}</td>
                                    <td><code>{{ $detail->number_value }}</code></td>
                                    <td>RD$ {{ number_format($detail->amount, 2) }}</td>
                                    <td><span class="badge bg-success">{{ $detail->payout_multiplier }}x</span></td>
                                    <td class="text-end fw-semibold text-success">RD$ {{ number_format($detail->possible_prize, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-secondary py-3">Sin detalles.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td colspan="4">Totales</td>
                                <td>RD$ {{ number_format($ticket->total_amount, 2) }}</td>
                                <td></td>
                                <td class="text-end text-success">RD$ {{ number_format($ticket->total_possible_prize, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if ($ticket->printJobs->isNotEmpty())
                <div class="card mt-4">
                    <div class="card-header bg-white">
                        <h2 class="h6 mb-0">Impresiones</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr><th>Tipo</th><th>Estado</th><th>Intentos</th><th>Fecha</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($ticket->printJobs as $job)
                                    <tr>
                                        <td>{{ $job->type }}</td>
                                        <td><x-status-badge :status="$job->status" /></td>
                                        <td>{{ $job->attempts }}</td>
                                        <td class="small">{{ $job->created_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h2 class="h6 mb-3">Información</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-secondary">Estado</dt>
                        <dd class="col-7"><x-status-badge :status="$ticket->status" /></dd>
                        <dt class="col-5 text-secondary">Modo</dt>
                        <dd class="col-7">{{ $ticket->sale_mode }}</dd>
                        <dt class="col-5 text-secondary">Sucursal</dt>
                        <dd class="col-7">{{ $ticket->branch->name }}</dd>
                        <dt class="col-5 text-secondary">Cajero</dt>
                        <dd class="col-7">{{ $ticket->user->username }}</dd>
                        <dt class="col-5 text-secondary">Vendido</dt>
                        <dd class="col-7">{{ $ticket->sold_at->format('Y-m-d H:i:s') }}</dd>
                        <dt class="col-5 text-secondary">Impresiones</dt>
                        <dd class="col-7">{{ $ticket->print_count }}</dd>
                        @if ($ticket->status === 'CANCELLED')
                            <dt class="col-5 text-secondary">Anulado por</dt>
                            <dd class="col-7">{{ $ticket->cancelledBy?->username ?: '—' }}</dd>
                            <dt class="col-5 text-secondary">Motivo</dt>
                            <dd class="col-7">{{ $ticket->cancel_reason }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h2 class="h6 mb-3">Contenido de impresión</h2>
                    <pre class="bg-light border rounded p-3 mb-0 small" style="font-family: monospace; line-height: 1.3; font-size: .75rem;">{{ optional($ticket->printJobs->first())->content ?? 'No generado' }}</pre>
                </div>
            </div>
        </div>
    </div>

    @if ($ticket->isCancellable())
        <div class="modal fade" id="cancelModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('admin.tickets.cancel', $ticket) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Anular ticket #{{ $ticket->ticket_number }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-secondary">Total: <strong>RD$ {{ number_format($ticket->total_amount, 2) }}</strong></p>
                        <label class="form-label">Motivo de anulación *</label>
                        <textarea class="form-control" name="cancel_reason" rows="3" maxlength="500" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar anulación</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
        (function () {
            const reprintBtn = document.getElementById('reprintTicketBtn');
            if (!reprintBtn) {
                return;
            }

            reprintBtn.addEventListener('click', async function () {
                const terminal = window.BSLotteryTerminal?.get?.() || null;
                reprintBtn.disabled = true;

                try {
                    await window.BSQZ?.connectQzTray?.();

                    const response = await fetch(reprintBtn.dataset.reprintUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            terminal_key: terminal?.key || null,
                            terminal_name: terminal?.name || null,
                        }),
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(payload.message || 'No se pudo solicitar la reimpresión.');
                    }
                } catch (error) {
                    alert(error.message || 'Ocurrio un error al reimprimir el ticket.');
                } finally {
                    reprintBtn.disabled = false;
                }
            });
        })();
    </script>
    @endpush
@endsection
