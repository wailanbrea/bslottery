@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-eye me-2" style="color: var(--argon-info);"></i>Vista previa</h1>
            <p class="text-secondary mb-0">{{ $draw->lottery->name }} — {{ $draw->name }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.tickets.create') }}">
            <i class="bi bi-arrow-left me-1"></i>Modificar
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-3"><span class="text-secondary small">Sucursal:</span> <strong>{{ $branch->name }}</strong></div>
                <div class="col-md-3"><span class="text-secondary small">Sorteo:</span> <strong>{{ $draw->name }}</strong></div>
                <div class="col-md-3"><span class="text-secondary small">Fecha:</span> <strong>{{ $draw->draw_date->format('Y-m-d') }}</strong></div>
                <div class="col-md-3"><span class="text-secondary small">Cierre:</span> <strong>{{ $draw->close_time }}</strong></div>
            </div>

            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Jugada</th>
                        <th>Número</th>
                        <th>Monto</th>
                        <th>Multiplicador</th>
                        <th class="text-end">Posible premio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($preview['plays'] as $play)
                        <tr>
                            <td class="fw-semibold">{{ $play['bet_type_name'] }}</td>
                            <td><code>{{ $play['number_value'] }}</code></td>
                            <td>RD$ {{ number_format($play['amount'], 2) }}</td>
                            <td><span class="badge bg-success">{{ $play['payout_multiplier'] }}x</span></td>
                            <td class="text-end fw-semibold text-success">RD$ {{ number_format($play['possible_prize'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="2">Totales</td>
                        <td>RD$ {{ number_format($preview['total_amount'], 2) }}</td>
                        <td></td>
                        <td class="text-end text-success">RD$ {{ number_format($preview['total_possible_prize'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.tickets.store') }}">
        @csrf
        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
        <input type="hidden" name="draw_id" value="{{ $draw->id }}">
        @foreach ($plays as $i => $play)
            <input type="hidden" name="plays[{{ $i }}][bet_type_id]" value="{{ $play['bet_type_id'] }}">
            <input type="hidden" name="plays[{{ $i }}][number_value]" value="{{ $play['number_value'] }}">
            <input type="hidden" name="plays[{{ $i }}][amount]" value="{{ $play['amount'] }}">
            @if (! empty($play['position']))
                <input type="hidden" name="plays[{{ $i }}][position]" value="{{ $play['position'] }}">
            @endif
        @endforeach

        <div class="d-flex gap-2">
            <button class="btn btn-success btn-lg" type="submit" onclick="return confirm('¿Confirmar venta por RD$ {{ number_format($preview['total_amount'], 2) }}?')">
                <i class="bi bi-cart-check me-1"></i>Confirmar venta
            </button>
            <a class="btn btn-outline-secondary btn-lg" href="{{ route('admin.tickets.create') }}">Cancelar</a>
        </div>
    </form>
@endsection
