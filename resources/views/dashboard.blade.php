@extends('layouts.app')

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Dashboard</h1>
        <p class="text-secondary mb-0">
            <i class="bi bi-calendar3 me-1"></i>{{ now()->locale('es')->isoFormat('dddd, D [de] MMMM YYYY') }}
            &mdash; {{ $user->company?->name ?? 'Plataforma' }}
            @if($branchId)
                &mdash; <i class="bi bi-shop me-1"></i>{{ $recentTickets->first()?->branch?->name ?? 'Sucursal' }}
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        @if($activeCash)
            <span class="badge bg-success fs-6 px-3 py-2">
                <i class="bi bi-cash-register me-1"></i>Caja abierta
            </span>
        @else
            @can('cash.open')
            <a href="{{ route('admin.cash.index') }}" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-cash-register me-1"></i>Abrir caja
            </a>
            @endcan
        @endif
        @if($unreadAlerts > 0)
            <span class="badge bg-danger fs-6 px-3 py-2">
                <i class="bi bi-bell-fill me-1"></i>{{ $unreadAlerts }} alerta{{ $unreadAlerts !== 1 ? 's' : '' }}
            </span>
        @endif
    </div>
</div>

{{-- KPI Cards Row --}}
<div class="row g-3 mb-4">

    {{-- Ventas hoy --}}
    <div class="col-6 col-xl-2">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <p class="text-secondary small text-uppercase fw-semibold mb-0" style="font-size:.68rem;letter-spacing:.05em">Ventas hoy</p>
                    <div class="rounded-3 p-2" style="background:rgba(94,114,228,.12)">
                        <i class="bi bi-currency-dollar" style="color:var(--argon-primary)"></i>
                    </div>
                </div>
                <h3 class="h5 mb-1">${{ number_format($salesToday, 2) }}</h3>
                @if($salesTrend !== null)
                    <small class="{{ $salesTrend >= 0 ? 'text-success' : 'text-danger' }}">
                        <i class="bi bi-arrow-{{ $salesTrend >= 0 ? 'up' : 'down' }}-short"></i>
                        {{ abs($salesTrend) }}% vs ayer
                    </small>
                @else
                    <small class="text-secondary">Sin datos de ayer</small>
                @endif
            </div>
        </div>
    </div>

    {{-- Tickets hoy --}}
    <div class="col-6 col-xl-2">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <p class="text-secondary small text-uppercase fw-semibold mb-0" style="font-size:.68rem;letter-spacing:.05em">Tickets hoy</p>
                    <div class="rounded-3 p-2" style="background:rgba(45,206,137,.12)">
                        <i class="bi bi-receipt" style="color:var(--argon-success)"></i>
                    </div>
                </div>
                <h3 class="h5 mb-1">{{ number_format($ticketsToday) }}</h3>
                @if($ticketsTrend !== null)
                    <small class="{{ $ticketsTrend >= 0 ? 'text-success' : 'text-danger' }}">
                        <i class="bi bi-arrow-{{ $ticketsTrend >= 0 ? 'up' : 'down' }}-short"></i>
                        {{ abs($ticketsTrend) }}% vs ayer
                    </small>
                @else
                    <small class="text-secondary">Sin datos de ayer</small>
                @endif
            </div>
        </div>
    </div>

    {{-- Sorteos abiertos --}}
    <div class="col-6 col-xl-2">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <p class="text-secondary small text-uppercase fw-semibold mb-0" style="font-size:.68rem;letter-spacing:.05em">Sorteos hoy</p>
                    <div class="rounded-3 p-2" style="background:rgba(17,205,239,.12)">
                        <i class="bi bi-calendar-event" style="color:var(--argon-info)"></i>
                    </div>
                </div>
                <h3 class="h5 mb-1">{{ $openDrawsCount }}</h3>
                <small class="text-secondary">{{ $openDrawsCount === 1 ? 'sorteo abierto' : 'sorteos abiertos' }}</small>
            </div>
        </div>
    </div>

    {{-- Premios por pagar --}}
    <div class="col-6 col-xl-2">
        <div class="card h-100 border-0 shadow-sm {{ $pendingPrizes > 0 ? 'border-warning border-2' : '' }}">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <p class="text-secondary small text-uppercase fw-semibold mb-0" style="font-size:.68rem;letter-spacing:.05em">Por pagar</p>
                    <div class="rounded-3 p-2" style="background:rgba(251,99,64,.12)">
                        <i class="bi bi-cash-coin" style="color:var(--argon-warning)"></i>
                    </div>
                </div>
                <h3 class="h5 mb-1 {{ $pendingPrizes > 0 ? 'text-warning' : '' }}">{{ $pendingPrizes }}</h3>
                <small class="text-secondary">premio{{ $pendingPrizes !== 1 ? 's' : '' }} pendiente{{ $pendingPrizes !== 1 ? 's' : '' }}</small>
            </div>
        </div>
    </div>

    {{-- Resultados pendientes --}}
    <div class="col-6 col-xl-2">
        <div class="card h-100 border-0 shadow-sm {{ $pendingResults > 0 ? 'border-info border-2' : '' }}">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <p class="text-secondary small text-uppercase fw-semibold mb-0" style="font-size:.68rem;letter-spacing:.05em">Resultados</p>
                    <div class="rounded-3 p-2" style="background:rgba(17,205,239,.12)">
                        <i class="bi bi-trophy" style="color:var(--argon-info)"></i>
                    </div>
                </div>
                <h3 class="h5 mb-1 {{ $pendingResults > 0 ? 'text-info' : '' }}">{{ $pendingResults }}</h3>
                <small class="text-secondary">{{ $pendingResults === 1 ? 'pendiente de confirmar' : 'pendientes de confirmar' }}</small>
            </div>
        </div>
    </div>

    {{-- Cancelados hoy --}}
    <div class="col-6 col-xl-2">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <p class="text-secondary small text-uppercase fw-semibold mb-0" style="font-size:.68rem;letter-spacing:.05em">Cancelados</p>
                    <div class="rounded-3 p-2" style="background:rgba(245,54,92,.12)">
                        <i class="bi bi-x-circle" style="color:var(--argon-danger)"></i>
                    </div>
                </div>
                <h3 class="h5 mb-1">{{ $cancelledToday }}</h3>
                <small class="text-secondary">ticket{{ $cancelledToday !== 1 ? 's' : '' }} hoy</small>
            </div>
        </div>
    </div>

    {{-- Incidencias de caja pendientes --}}
    @if($user->hasPermission('cash.incidents.view'))
        <div class="col-6 col-xl-2">
            <a href="{{ route('admin.cash.incidents.index', ['status' => 'OPEN']) }}" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm {{ $openIncidentsCount > 0 ? 'border-danger border-2' : '' }}">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <p class="text-secondary small text-uppercase fw-semibold mb-0" style="font-size:.68rem;letter-spacing:.05em">Incidencias</p>
                            <div class="rounded-3 p-2" style="background:rgba(245,54,92,.12)">
                                <i class="bi bi-exclamation-triangle" style="color:var(--argon-danger)"></i>
                            </div>
                        </div>
                        <h3 class="h5 mb-1 {{ $openIncidentsCount > 0 ? 'text-danger' : '' }}">{{ $openIncidentsCount }}</h3>
                        @if($openIncidentsCount > 0)
                            <small class="text-danger fw-semibold">
                                Faltante: RD$ {{ number_format($openShortageAmount, 2) }}
                            </small>
                        @else
                            <small class="text-secondary">Sin incidencias pendientes</small>
                        @endif
                    </div>
                </div>
            </a>
        </div>
    @endif
</div>

{{-- Chart + Open Draws --}}
<div class="row g-3 mb-4">

    {{-- Sales chart --}}
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="mb-0"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Ventas — últimos 7 días</h6>
            </div>
            <div class="card-body pt-2">
                <canvas id="salesChart" style="height:220px"></canvas>
            </div>
        </div>
    </div>

    {{-- Open draws today --}}
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-calendar-check me-2 text-success"></i>Sorteos abiertos hoy</h6>
                @if($user->hasPermission('draws.view'))
                    <a href="{{ route('admin.draws.index') }}" class="small text-secondary">Ver todos</a>
                @endif
            </div>
            <div class="card-body pt-2 px-0" style="overflow-y:auto;max-height:260px">
                @forelse($openDraws as $draw)
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <span class="badge bg-success-subtle text-success rounded-pill" style="font-size:.65rem;white-space:nowrap">
                                {{ $draw->close_time ? \Carbon\Carbon::parse($draw->close_time)->format('H:i') : '--:--' }}
                            </span>
                            <span class="text-truncate small" title="{{ $draw->lottery?->name }} – {{ $draw->name }}">
                                {{ $draw->lottery?->name }}
                            </span>
                        </div>
                        <span class="badge bg-success ms-2 flex-shrink-0">Abierto</span>
                    </div>
                @empty
                    <div class="text-center text-secondary py-4">
                        <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                        Sin sorteos abiertos hoy
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Recent tickets + Alerts + Top branches --}}
<div class="row g-3">

    {{-- Recent tickets --}}
    <div class="col-12 col-xl-{{ $topBranches->isEmpty() ? '8' : '5' }}">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-receipt me-2 text-primary"></i>Últimos tickets</h6>
                @if($user->hasPermission('tickets.view'))
                    <a href="{{ route('admin.tickets.index') }}" class="small text-secondary">Ver todos</a>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Sucursal / Usuario</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end pe-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTickets as $ticket)
                                <tr>
                                    <td class="ps-3">
                                        @if($user->hasPermission('tickets.view'))
                                            <a href="{{ route('admin.tickets.show', $ticket) }}" class="text-decoration-none small fw-semibold">
                                                #{{ $ticket->ticket_number ?? $ticket->id }}
                                            </a>
                                        @else
                                            <span class="small fw-semibold">#{{ $ticket->ticket_number ?? $ticket->id }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small">{{ $ticket->branch?->name ?? '—' }}</div>
                                        <div class="text-secondary" style="font-size:.72rem">{{ $ticket->user?->name ?? '—' }}</div>
                                    </td>
                                    <td class="text-end small">${{ number_format($ticket->total_amount, 2) }}</td>
                                    <td class="text-end pe-3"><x-status-badge :status="$ticket->status" /></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-secondary py-3">Sin tickets recientes</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    <div class="col-12 col-xl-{{ $topBranches->isEmpty() ? '4' : '4' }}">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-bell me-2 text-warning"></i>Alertas
                    @if($unreadAlerts > 0)
                        <span class="badge bg-danger ms-1">{{ $unreadAlerts }}</span>
                    @endif
                </h6>
                @if($user->hasPermission('notifications.view'))
                    <a href="{{ route('admin.notifications.index') }}" class="small text-secondary">Ver todas</a>
                @endif
            </div>
            <div class="card-body pt-2 px-0">
                @forelse($alerts as $alert)
                    @php
                        $alertColor = match($alert->severity) {
                            'CRITICAL' => 'danger',
                            'WARNING'  => 'warning',
                            default    => 'info',
                        };
                        $alertIcon = match($alert->severity) {
                            'CRITICAL' => 'bi-exclamation-octagon-fill',
                            'WARNING'  => 'bi-exclamation-triangle-fill',
                            default    => 'bi-info-circle-fill',
                        };
                    @endphp
                    <div class="d-flex gap-2 align-items-start px-3 py-2 border-bottom">
                        <i class="bi {{ $alertIcon }} text-{{ $alertColor }} mt-1 flex-shrink-0"></i>
                        <div class="min-w-0">
                            <div class="small text-truncate fw-semibold">{{ $alert->title }}</div>
                            <div class="text-secondary" style="font-size:.72rem">{{ $alert->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-secondary py-4 px-3">
                        <i class="bi bi-check-circle fs-3 d-block mb-2 text-success"></i>
                        Sin alertas pendientes
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Top branches (managers only) --}}
    @if($topBranches->isNotEmpty())
    <div class="col-12 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
                <h6 class="mb-0"><i class="bi bi-shop me-2 text-info"></i>Top sucursales hoy</h6>
            </div>
            <div class="card-body pt-2">
                @foreach($topBranches as $i => $branch)
                    @php $pct = $topBranches->first()->total > 0 ? round(($branch->total / $topBranches->first()->total) * 100) : 0; @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-pill {{ $i === 0 ? 'bg-warning text-dark' : 'bg-secondary' }}" style="min-width:1.4rem">{{ $i + 1 }}</span>
                                <span class="small text-truncate" style="max-width:110px" title="{{ $branch->branch?->name }}">{{ $branch->branch?->name ?? 'N/A' }}</span>
                            </div>
                            <span class="small fw-semibold">${{ number_format($branch->total, 0) }}</span>
                        </div>
                        <div class="progress" style="height:5px">
                            <div class="progress-bar {{ $i === 0 ? 'bg-warning' : 'bg-primary' }}"
                                 role="progressbar" style="width:{{ $pct }}%"></div>
                        </div>
                        <div class="text-secondary mt-1" style="font-size:.68rem">{{ $branch->count }} ticket{{ $branch->count !== 1 ? 's' : '' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const labels  = @json($chartLabels);
    const amounts = @json($chartAmounts);
    const counts  = @json($chartCounts);

    const ctx = document.getElementById('salesChart').getContext('2d');

    const gradient = ctx.createLinearGradient(0, 0, 0, 220);
    gradient.addColorStop(0, 'rgba(94,114,228,0.35)');
    gradient.addColorStop(1, 'rgba(94,114,228,0.02)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Monto ($)',
                    data: amounts,
                    backgroundColor: gradient,
                    borderColor: 'rgba(94,114,228,0.8)',
                    borderWidth: 2,
                    borderRadius: 6,
                    yAxisID: 'yAmount',
                },
                {
                    type: 'line',
                    label: 'Tickets',
                    data: counts,
                    borderColor: 'rgba(45,206,137,0.9)',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: 'rgba(45,206,137,1)',
                    tension: 0.4,
                    yAxisID: 'yCount',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ctx.datasetIndex === 0
                            ? ` $${ctx.parsed.y.toLocaleString('es-DO', { minimumFractionDigits: 2 })}`
                            : ` ${ctx.parsed.y} tickets`,
                    },
                },
            },
            scales: {
                yAmount: {
                    type: 'linear',
                    position: 'left',
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: { font: { size: 10 }, callback: v => '$' + v.toLocaleString('es-DO') },
                },
                yCount: {
                    type: 'linear',
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { font: { size: 10 }, stepSize: 1 },
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } },
                },
            },
        },
    });
})();
</script>
@endpush
