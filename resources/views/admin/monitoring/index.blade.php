@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-activity me-2" style="color: var(--argon-danger);"></i>Monitoreo de sucursales</h1>
            <p class="text-secondary mb-0">Ventas, premios, efectivo y alertas operativas por sucursal.</p>
        </div>
        <div class="d-flex gap-2">
            @if (auth()->user()?->hasPermission('monitoring.configure'))
                <a class="btn btn-outline-secondary" href="{{ route('admin.monitoring.settings') }}">
                    <i class="bi bi-sliders me-1"></i>Umbrales
                </a>
            @endif
            <a class="btn btn-outline-primary" href="{{ route('admin.notifications.index') }}">
                <i class="bi bi-bell me-1"></i>Notificaciones
            </a>
        </div>
    </div>

    <form class="card mb-3" method="GET" action="{{ route('admin.monitoring.index') }}">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Fecha</label>
                    <input class="form-control" type="date" name="date" value="{{ $filters['date'] }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sucursal</label>
                    <select class="form-select" name="branch_id">
                        <option value="">Todas</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) $filters['branch_id'] === $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="bi bi-search me-1"></i>Actualizar
                    </button>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card"><div class="card-body">
                <p class="text-secondary small text-uppercase mb-1">Sucursales</p>
                <h2 class="h4 mb-0">{{ $totals['branches_count'] }}</h2>
            </div></div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card"><div class="card-body">
                <p class="text-secondary small text-uppercase mb-1">Ventas</p>
                <h2 class="h4 mb-0 text-success">RD$ {{ number_format((float) $totals['sales_total'], 2) }}</h2>
            </div></div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card"><div class="card-body">
                <p class="text-secondary small text-uppercase mb-1">Premios pagados</p>
                <h2 class="h4 mb-0 text-danger">RD$ {{ number_format((float) $totals['prizes_total'], 2) }}</h2>
            </div></div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-danger"><div class="card-body">
                <p class="text-secondary small text-uppercase mb-1">Sucursales en perdida</p>
                <h2 class="h4 mb-0 text-danger">{{ $totals['branches_in_loss'] }}</h2>
            </div></div>
        </div>
    </div>

    @if ($notifications->isNotEmpty())
        <div class="alert alert-danger">
            <div class="fw-bold mb-1">Alertas activas</div>
            @foreach ($notifications as $notification)
                <div class="small">
                    <strong>{{ $notification->branch?->name ?: 'General' }}:</strong>
                    {{ $notification->title }}
                    @if ($notification->amount !== null)
                        — RD$ {{ number_format($notification->amount, 2) }}
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Sucursal</th>
                        <th class="text-end">Ventas</th>
                        <th class="text-end">Premios</th>
                        <th class="text-end">Neto</th>
                        <th class="text-end">Caja estimada</th>
                        <th>Jugada mas alta</th>
                        <th>Alertas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="{{ $row['is_loss'] ? 'table-danger' : ($row['is_low_cash'] ? 'table-warning' : '') }}">
                            <td>
                                <div class="fw-semibold">{{ $row['branch']->name }}</div>
                                <small class="text-secondary">{{ $row['branch']->code }}</small>
                            </td>
                            <td class="text-end">RD$ {{ number_format((float) $row['sales_total'], 2) }}</td>
                            <td class="text-end text-danger">RD$ {{ number_format((float) $row['prizes_total'], 2) }}</td>
                            <td class="text-end fw-semibold {{ (float) $row['net_total'] < 0 ? 'text-danger' : 'text-success' }}">
                                RD$ {{ number_format((float) $row['net_total'], 2) }}
                            </td>
                            <td class="text-end {{ (float) $row['expected_cash'] < 0 ? 'text-danger fw-bold' : '' }}">
                                RD$ {{ number_format((float) $row['expected_cash'], 2) }}
                            </td>
                            <td>
                                @if ($row['top_play'])
                                    <div><code>{{ $row['top_play']->number_value }}</code> RD$ {{ number_format((float) $row['top_play']->amount_total, 2) }}</div>
                                    <small class="text-secondary">
                                        {{ $row['top_play']->betType?->name }}
                                        · {{ $row['top_play']->lottery?->name }}
                                        · {{ $row['top_play']->draw?->name }}
                                    </small>
                                @else
                                    <span class="text-secondary">Sin jugadas</span>
                                @endif
                            </td>
                            <td>
                                @if ($row['is_loss'])
                                    <span class="badge bg-danger">Requiere efectivo</span>
                                    <div class="small text-danger">Perdida RD$ {{ number_format((float) $row['loss_amount'], 2) }}</div>
                                @elseif ($row['is_low_cash'])
                                    <span class="badge bg-warning text-dark">Caja baja</span>
                                    <div class="small text-warning-emphasis">Minimo RD$ {{ number_format((float) $row['monitoring_setting']->minimum_expected_cash, 2) }}</div>
                                @elseif ($row['is_top_play_alert'])
                                    <span class="badge bg-warning text-dark">Jugada alta</span>
                                @else
                                    <span class="badge bg-success">Normal</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No hay sucursales para monitorear.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
