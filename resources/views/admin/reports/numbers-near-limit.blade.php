@extends('layouts.app')

@section('content')
@php $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2); @endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-exclamation-triangle me-2" style="color: var(--argon-warning);"></i>Números cerca del límite</h1>
        <p class="text-secondary mb-0">Números cuyo consumo supera el umbral configurado.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}"><i class="bi bi-arrow-left me-1"></i>Reportes</a>
</div>

@include('admin.reports.partials.filters')

{{-- Umbral adicional --}}
<form method="GET" action="{{ url()->current() }}" class="d-inline-block mb-3">
    @foreach(request()->except('threshold') as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach
    <div class="input-group" style="width:220px;">
        <span class="input-group-text"><i class="bi bi-percent"></i></span>
        <input class="form-control" type="number" name="threshold" min="1" max="100" value="{{ $threshold }}" placeholder="Umbral %">
        <button class="btn btn-outline-secondary" type="submit">Aplicar</button>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Sucursal</th>
                    <th>Lotería / Sorteo</th>
                    <th>Tipo</th>
                    <th class="text-end">Máx (RD$)</th>
                    <th class="text-end">Consumido</th>
                    <th class="text-end">Disponible</th>
                    <th class="text-end">Uso %</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($numbers as $row)
                    @php
                        $pct = (float) $row->usage_pct;
                        $available = max(0, (float) $row->max_allowed - (float) $row->net_consumed);
                        $badgeClass = $pct >= 100 ? 'bg-danger' : ($pct >= 90 ? 'bg-warning text-dark' : 'bg-info text-dark');
                    @endphp
                    <tr>
                        <td class="fw-bold font-monospace">{{ $row->number_value }}</td>
                        <td>{{ $row->branch?->name ?? '—' }}</td>
                        <td class="small">
                            {{ $row->lottery?->name ?? '—' }}<br>
                            <span class="text-secondary">{{ $row->draw?->name ?? '—' }}</span>
                        </td>
                        <td>{{ $row->betType?->name ?? '—' }}</td>
                        <td class="text-end">{{ $money($row->max_allowed) }}</td>
                        <td class="text-end fw-semibold">{{ $money($row->net_consumed) }}</td>
                        <td class="text-end {{ $available <= 0 ? 'text-danger fw-bold' : 'text-success' }}">{{ $money($available) }}</td>
                        <td class="text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <div class="progress flex-grow-1" style="height:6px;min-width:60px;max-width:100px;">
                                    <div class="progress-bar {{ $pct >= 100 ? 'bg-danger' : ($pct >= 90 ? 'bg-warning' : 'bg-info') }}"
                                         style="width:{{ min(100, $pct) }}%"></div>
                                </div>
                                <span class="badge {{ $badgeClass }}">{{ $pct }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-secondary py-4">
                            No hay números con consumo ≥ {{ $threshold }}% para los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $numbers->appends(['threshold' => $threshold])->links() }}</div>
@endsection
