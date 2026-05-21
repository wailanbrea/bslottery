@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
    $dirBadge = fn (string $d) => $d === 'IN' ? 'success' : 'danger';
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-arrow-left-right me-2" style="color: var(--argon-info);"></i>Movimientos de caja</h1>
        <p class="text-secondary mb-0">Todos los movimientos registrados con filtros por tipo y dirección.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}">
        <i class="bi bi-arrow-left me-1"></i>Reportes
    </a>
</div>

@include('admin.reports.partials.filters')

{{-- Extra filters --}}
<form method="GET" action="{{ url()->current() }}" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Tipo de movimiento</label>
                <input class="form-control form-control-sm" name="mov_type" value="{{ $mov_type }}" placeholder="Ej: SALE, PAYROLL">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Dirección</label>
                <select class="form-select form-select-sm" name="direction">
                    <option value="">Todas</option>
                    <option value="IN" @selected($direction === 'IN')>IN — Entrada</option>
                    <option value="OUT" @selected($direction === 'OUT')>OUT — Salida</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filtrar</button>
            </div>
        </div>
        @foreach(request()->except(['mov_type','direction','page']) as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Sucursal</th>
                    <th>Usuario</th>
                    <th>Tipo</th>
                    <th class="text-center">Dirección</th>
                    <th class="text-end">Monto</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movements as $mov)
                    <tr>
                        <td class="text-secondary small">{{ $mov->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $mov->branch?->name ?? '—' }}</td>
                        <td class="small">{{ $mov->user?->name ?? '—' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $mov->type }}</span></td>
                        <td class="text-center">
                            <span class="badge bg-{{ $dirBadge($mov->direction) }}">{{ $mov->direction }}</span>
                        </td>
                        <td class="text-end fw-semibold">{{ $money($mov->amount) }}</td>
                        <td class="text-secondary small">{{ $mov->description }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">Sin movimientos en el rango seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $movements->links() }}</div>
@endsection
