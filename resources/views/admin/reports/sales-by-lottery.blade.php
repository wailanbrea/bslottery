@extends('layouts.app')

@section('content')
@php $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2); @endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-ticket-perforated me-2" style="color: var(--argon-warning);"></i>Ventas por lotería y sorteo</h1>
        <p class="text-secondary mb-0">Jugadas agrupadas por sorteo.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}"><i class="bi bi-arrow-left me-1"></i>Reportes</a>
</div>

@include('admin.reports.partials.filters')

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Lotería</th>
                    <th>Sorteo</th>
                    <th class="text-end">Jugadas</th>
                    <th class="text-end">Total apostado</th>
                    <th class="text-end">Premio posible</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row->lottery?->name ?? 'Sin lotería' }}</td>
                        <td>{{ $row->draw?->name ?? 'Sin sorteo' }}</td>
                        <td class="text-end">{{ number_format((int) $row->plays_count) }}</td>
                        <td class="text-end fw-bold text-success">{{ $money($row->total_amount) }}</td>
                        <td class="text-end text-warning">{{ $money($row->possible_prize) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4 text-secondary">Sin datos para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $sales->links() }}</div>
@endsection
