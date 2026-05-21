@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-cash-register me-2" style="color: var(--argon-success);"></i>Caja</h1>
            <p class="text-secondary mb-0">Sesiones de caja por sucursal.</p>
        </div>
        <div class="d-flex gap-2">
            @if (auth()->user()?->hasPermission('cash.funding.view'))
                <a class="btn btn-outline-success" href="{{ route('admin.cash.funding.index') }}">
                    <i class="bi bi-truck me-1"></i>Refuerzos
                </a>
            @endif
            <a class="btn btn-primary" href="{{ route('admin.cash.current') }}">
                <i class="bi bi-box-arrow-in-right me-1"></i>Ir a caja actual
            </a>
        </div>
    </div>

    @if (session('cash_close_summary'))
        @php
            $summary = session('cash_close_summary');
            $difference = (float) $summary['difference'];
        @endphp
        <div class="alert {{ $difference < 0 ? 'alert-danger' : ($difference > 0 ? 'alert-warning' : 'alert-success') }}">
            <div class="fw-bold mb-1">
                Cierre de caja #{{ $summary['session_id'] }}:
                {{ $difference < 0 ? 'faltante detectado' : ($difference > 0 ? 'sobrante detectado' : 'cuadre exacto') }}.
            </div>
            <div class="small">
                Esperado: <strong>RD$ {{ number_format((float) $summary['expected_cash'], 2) }}</strong>
                | Contado físico: <strong>RD$ {{ number_format((float) $summary['counted_cash'], 2) }}</strong>
                | Diferencia: <strong>{{ $difference >= 0 ? '+' : '-' }}RD$ {{ number_format(abs($difference), 2) }}</strong>
                @if ((float) $summary['shortage_amount'] > 0)
                    | Faltante: <strong>RD$ {{ number_format((float) $summary['shortage_amount'], 2) }}</strong>
                @endif
                @if ((float) $summary['surplus_amount'] > 0)
                    | Sobrante: <strong>RD$ {{ number_format((float) $summary['surplus_amount'], 2) }}</strong>
                @endif
            </div>
        </div>
    @endif

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
                        <th>Esperado</th>
                        <th>Contado</th>
                        <th>Diferencia</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sessions as $session)
                        @php
                            $difference = $session->counted_cash !== null
                                ? (float) $session->counted_cash - (float) $session->expected_cash
                                : null;
                        @endphp
                        <tr>
                            <td><code>#{{ $session->id }}</code></td>
                            <td>{{ $session->branch?->name ?: '-' }}</td>
                            <td>{{ $session->user?->name ?: '-' }}</td>
                            <td>
                                <div>RD$ {{ number_format($session->opening_amount, 2) }}</div>
                                <small class="text-secondary">{{ $session->opened_at->format('Y-m-d H:i') }}</small>
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
                                    @if ($session->shortage_amount > 0)
                                        <div class="small text-danger">Faltante RD$ {{ number_format($session->shortage_amount, 2) }}</div>
                                    @elseif ($session->surplus_amount > 0)
                                        <div class="small text-warning">Sobrante RD$ {{ number_format($session->surplus_amount, 2) }}</div>
                                    @endif
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td>
                                <x-status-badge :status="$session->status" />
                                @if ($session->reconciliation)
                                    <div class="small text-secondary mt-1">Arqueo: <x-status-badge :status="$session->reconciliation->status" /></div>
                                @endif
                                @if ($session->incidents_count > 0)
                                    <div class="small text-danger mt-1">Incidencias: {{ $session->incidents_count }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($session->status === 'CLOSED' && auth()->user()->hasPermission('cash.confirm'))
                                    <form method="POST" action="{{ route('admin.cash.confirm', $session) }}" class="d-inline" onsubmit="return confirm('¿Confirmar el cierre de esta caja?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success" type="submit">
                                            <i class="bi bi-check-lg me-1"></i>Confirmar
                                        </button>
                                    </form>
                                @endif
                                @if (in_array($session->status, ['CLOSED', 'CONFIRMED'], true) && auth()->user()->hasPermission('cash.reopen'))
                                    <form method="POST" action="{{ route('admin.cash.reopen', $session) }}" class="d-inline" onsubmit="return confirm('¿Reabrir esta caja?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning" type="submit">
                                            <i class="bi bi-arrow-repeat me-1"></i>Reabrir
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-secondary py-4">No hay sesiones de caja registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $sessions->links() }}</div>
@endsection
