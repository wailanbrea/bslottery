<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Ticket {{ $ticket->ticket_number ?: substr($ticket->uuid, 0, 8) }} — {{ $company?->name ?? 'BSLottery' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f5f7; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .ticket-card { max-width: 480px; margin: 16px auto; }
        .play-row { font-family: 'Menlo', 'Consolas', monospace; }
        .number-pill { background: #0d6efd; color: #fff; padding: 2px 10px; border-radius: 12px; font-weight: 600; }
        .number-pill.winner { background: #198754; }
        .winning-number { display: inline-block; min-width: 36px; text-align: center; background: #ffc107; color: #212529; font-weight: bold; padding: 4px 8px; border-radius: 6px; margin: 0 2px; }
        .status-badge { font-size: 0.85rem; }
        @media (max-width: 480px) {
            .ticket-card { margin: 0; box-shadow: none !important; border-radius: 0 !important; }
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="card ticket-card shadow-sm">
            <div class="card-body p-4">
                {{-- Header --}}
                <div class="text-center mb-3">
                    <div class="text-muted small text-uppercase mb-1">{{ $company?->legal_name ?? $company?->name ?? 'Banca' }}</div>
                    <h4 class="mb-0 fw-bold">{{ $company?->name ?? 'BSLottery' }}</h4>
                    @if ($branch)
                        <div class="text-muted small mt-1">
                            <i class="bi bi-geo-alt"></i> {{ $branch->name }}
                            @if ($branch->phone) · <i class="bi bi-telephone"></i> {{ $branch->phone }} @endif
                        </div>
                    @endif
                </div>

                {{-- Status badge --}}
                <div class="text-center mb-3">
                    @php
                        $statusClass = match($ticket->status) {
                            'CANCELLED' => 'bg-danger',
                            'WINNER' => 'bg-success',
                            'PARTIALLY_PAID', 'PAID' => 'bg-primary',
                            'LOSER' => 'bg-secondary',
                            default => 'bg-info',
                        };
                        $statusLabel = match($ticket->status) {
                            'ACTIVE' => 'Activo',
                            'CANCELLED' => 'ANULADO',
                            'WINNER' => 'GANADOR',
                            'LOSER' => 'No premiado',
                            'PARTIALLY_PAID' => 'Parcialmente pagado',
                            'PAID' => 'Pagado',
                            default => $ticket->status,
                        };
                    @endphp
                    <span class="badge {{ $statusClass }} status-badge">{{ $statusLabel }}</span>
                </div>

                {{-- Ticket info --}}
                <div class="mb-3 small">
                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span class="text-muted">Ticket</span>
                        <span class="fw-medium">{{ $ticket->ticket_number ?: '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span class="text-muted">Fecha</span>
                        <span>{{ $ticket->sold_at?->format('d/m/Y h:i A') ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span class="text-muted">ID</span>
                        <span class="text-muted" style="font-family: monospace; font-size: 0.75rem;">{{ substr($ticket->uuid, 0, 13) }}</span>
                    </div>
                </div>

                {{-- Cancelled notice --}}
                @if ($ticket->status === 'CANCELLED')
                    <div class="alert alert-danger p-2 small text-center mb-3">
                        <i class="bi bi-x-circle"></i> Este ticket fue anulado
                        @if ($ticket->cancel_reason) — {{ $ticket->cancel_reason }} @endif
                    </div>
                @endif

                {{-- Jugadas agrupadas por sorteo --}}
                @foreach ($groupedDetails as $group)
                    <div class="mb-3 p-3 border rounded">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-bold">{{ $group['lottery']?->name ?? 'Lotería' }}</div>
                                <div class="small text-muted">
                                    {{ $group['draw']?->name ?? '' }}
                                    @if ($group['draw']?->draw_date) · {{ $group['draw']->draw_date->format('d/m/Y') }} @endif
                                </div>
                            </div>
                            @if ($group['resultado'])
                                <div class="text-end">
                                    <div class="small text-muted">Resultado</div>
                                    <div>
                                        @if ($group['resultado']['primero'])
                                            <span class="winning-number">{{ $group['resultado']['primero'] }}</span>
                                        @endif
                                        @if ($group['resultado']['segundo'])
                                            <span class="winning-number">{{ $group['resultado']['segundo'] }}</span>
                                        @endif
                                        @if ($group['resultado']['tercero'])
                                            <span class="winning-number">{{ $group['resultado']['tercero'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                        <table class="table table-sm mb-0 play-row small">
                            <tbody>
                                @foreach ($group['jugadas'] as $jugada)
                                    <tr>
                                        <td><span class="number-pill">{{ $jugada['numero'] }}</span></td>
                                        <td class="text-muted">{{ $jugada['tipo'] }}</td>
                                        <td class="text-end">RD$ {{ number_format((float)$jugada['monto'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

                {{-- Total y ganancias --}}
                <div class="border-top pt-2 mt-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Total apostado</span>
                        <span class="fw-bold">RD$ {{ number_format((float)$ticket->total_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Premio posible</span>
                        <span>RD$ {{ number_format((float)$ticket->total_possible_prize, 2) }}</span>
                    </div>
                    @if ($hayGanadores)
                        <div class="d-flex justify-content-between mt-2 p-2 bg-success-subtle rounded">
                            <span class="fw-bold text-success-emphasis">
                                <i class="bi bi-trophy"></i> Ganado
                            </span>
                            <span class="fw-bold text-success-emphasis fs-5">RD$ {{ number_format((float)$totalGanado, 2) }}</span>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="text-center text-muted small mt-4">
                    <div>Conserve este ticket — Verifíquelo antes de retirarse</div>
                    <div class="mt-2">Reclame su premio en la sucursal donde lo compró</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
