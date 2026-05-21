@extends('layouts.app')

@section('content')
@php
    $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2);
    $rank = ($numbers->currentPage() - 1) * $numbers->perPage();
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-bar-chart me-2" style="color: var(--argon-primary);"></i>Números más jugados</h1>
        <p class="text-secondary mb-0">Ranking por monto apostado, separado por lotería, sorteo y tipo.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}"><i class="bi bi-arrow-left me-1"></i>Reportes</a>
</div>

@include('admin.reports.partials.filters')

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Número</th>
                    <th>Lotería</th>
                    <th>Sorteo</th>
                    <th>Tipo</th>
                    <th class="text-end">Veces</th>
                    <th class="text-end">Total apostado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($numbers as $row)
                    <tr>
                        <td class="fw-bold">{{ ++$rank }}</td>
                        <td><code class="fs-5">{{ $row->number_value }}</code></td>
                        <td>{{ $row->lottery?->name ?? 'Sin lotería' }}</td>
                        <td>{{ $row->draw?->name ?? 'Sin sorteo' }}</td>
                        <td>{{ $row->betType?->name ?? 'Sin tipo' }}</td>
                        <td class="text-end">{{ number_format((int) $row->plays_count) }}</td>
                        <td class="text-end fw-bold text-success">{{ $money($row->total_amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-4">Sin datos para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $numbers->links() }}</div>
@endsection
