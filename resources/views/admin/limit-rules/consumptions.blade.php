@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-graph-up me-2" style="color: var(--argon-info);"></i>Consumo de límites</h1>
            <p class="text-secondary mb-0">Acumulado de ventas por número, sorteo y sucursal.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.limit-rules.index') }}">
            <i class="bi bi-arrow-left me-1"></i>Volver a reglas
        </a>
    </div>

    <form class="mb-4" method="GET">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Buscar por número">
            <button class="btn btn-outline-secondary" type="submit">Buscar</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Jugada</th>
                        <th>Sucursal</th>
                        <th>Sorteo</th>
                        <th>Vendido</th>
                        <th>Reservado offline</th>
                        <th>Cancelado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($consumptions as $c)
                        <tr>
                            <td><code>{{ $c->number_value }}</code></td>
                            <td>{{ $c->betType?->name ?: '—' }}</td>
                            <td>{{ $c->branch?->name ?: '—' }}</td>
                            <td>{{ $c->draw?->name ?: '—' }} <small class="text-secondary">({{ optional($c->draw)->draw_date?->format('Y-m-d') }})</small></td>
                            <td class="fw-semibold">RD$ {{ number_format($c->sold_amount, 2) }}</td>
                            <td>RD$ {{ number_format($c->reserved_offline_amount, 2) }}</td>
                            <td>RD$ {{ number_format($c->cancelled_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No hay consumo registrado aún.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $consumptions->links() }}</div>
@endsection
