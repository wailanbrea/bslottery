@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-shop me-2" style="color: var(--argon-success);"></i>Cajas por Sucursal</h1>
            <p class="text-secondary mb-0">Todas las cajas de todas las bancas, agrupadas por sucursal. Entra al detalle para ver los movimientos de cada caja.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.cash.index') }}">
                <i class="bi bi-clock-history me-1"></i>Historial completo
            </a>
        </div>
    </div>

    @forelse ($branchSummaries as $summary)
        <div class="card mb-4">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h2 class="h6 mb-0">
                        <i class="bi bi-geo-alt me-1"></i>{{ $summary->branch->name }}
                    </h2>
                    <small class="text-secondary">
                        @if ($summary->open_count > 0)
                            {{ $summary->open_count }} caja(s) activa(s)
                            · Esperado en caja: <strong>RD$ {{ number_format($summary->open_expected_total, 2) }}</strong>
                            · Ventas: <strong>RD$ {{ number_format($summary->open_sales_total, 2) }}</strong>
                        @else
                            Sin cajas abiertas en este momento.
                        @endif
                    </small>
                </div>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.cash.index', ['branch_id' => $summary->branch->id]) }}">
                    <i class="bi bi-list-ul me-1"></i>Ver historial de esta sucursal
                </a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cajero</th>
                            <th>Apertura</th>
                            <th>Cierre</th>
                            <th>Esperado</th>
                            <th>Contado</th>
                            <th>Diferencia</th>
                            <th>Actividad</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summary->sessions as $session)
                            @php
                                $difference = $session->counted_cash !== null
                                    ? (float) $session->counted_cash - (float) $session->expected_cash
                                    : null;
                                $isActive = in_array($session->status, ['OPEN', 'REOPENED'], true);
                            @endphp
                            <tr class="{{ $isActive ? 'table-success' : '' }}">
                                <td><code>#{{ $session->id }}</code></td>
                                <td>{{ $session->user?->name ?: '-' }}</td>
                                <td>
                                    <div>RD$ {{ number_format($session->opening_amount, 2) }}</div>
                                    <small class="text-secondary">{{ $session->opened_at?->format('Y-m-d H:i') }}</small>
                                </td>
                                <td>
                                    @if ($session->closed_at)
                                        <small class="text-secondary">{{ $session->closed_at->format('Y-m-d H:i') }}</small>
                                    @else
                                        <span class="text-secondary">-</span>
                                    @endif
                                </td>
                                <td>RD$ {{ number_format($session->expected_cash, 2) }}</td>
                                <td>
                                    @if ($session->counted_cash !== null)
                                        RD$ {{ number_format($session->counted_cash, 2) }}
                                    @else
                                        <span class="text-secondary">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($difference !== null)
                                        <span class="fw-bold {{ $difference < 0 ? 'text-danger' : ($difference > 0 ? 'text-warning' : 'text-success') }}">
                                            {{ $difference >= 0 ? '+' : '-' }}RD$ {{ number_format(abs($difference), 2) }}
                                        </span>
                                    @else
                                        <span class="text-secondary">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small">Ventas: <strong>RD$ {{ number_format($session->sales_total, 2) }}</strong></div>
                                    <div class="small text-secondary">Tickets: {{ $session->tickets_count }} | Movs.: {{ $session->movements_count }}</div>
                                    @if ($session->incidents_count > 0)
                                        <div class="small text-danger">Incidencias: {{ $session->incidents_count }}</div>
                                    @endif
                                </td>
                                <td><x-status-badge :status="$session->status" /></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.cash.show', $session) }}">
                                        <i class="bi bi-eye me-1"></i>Movimientos
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-secondary py-3">Esta sucursal no tiene cajas registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($summary->sessions->count() >= 20)
                <div class="card-footer text-center small text-secondary">
                    Mostrando las 20 cajas mas recientes.
                    <a href="{{ route('admin.cash.index', ['branch_id' => $summary->branch->id]) }}">Ver todas</a>.
                </div>
            @endif
        </div>
    @empty
        <div class="card"><div class="card-body text-center text-secondary py-4">No hay sucursales registradas.</div></div>
    @endforelse
@endsection
