@extends('layouts.app')

@section('content')
@php $money = fn ($amount) => 'RD$ '.number_format((float) $amount, 2); @endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-calendar3 me-2" style="color: var(--argon-success);"></i>Ventas por día</h1>
        <p class="text-secondary mb-0">Resumen diario de tickets activos.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}"><i class="bi bi-arrow-left me-1"></i>Reportes</a>
</div>

@include('admin.reports.partials.filters')

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th class="text-end">Tickets</th>
                    <th class="text-end">Total vendido</th>
                    <th class="text-end">Premio posible</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $row)
                    <tr>
                        <td class="fw-semibold">{{ \Carbon\CarbonImmutable::parse($row->date)->format('d/m/Y') }}</td>
                        <td class="text-end">{{ number_format((int) $row->tickets_count) }}</td>
                        <td class="text-end fw-bold text-success">{{ $money($row->total_amount) }}</td>
                        <td class="text-end text-warning">{{ $money($row->possible_prize) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-secondary py-4">Sin ventas para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $sales->links() }}</div>
@endsection
