@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-truck me-2" style="color: var(--argon-primary);"></i>Refuerzos de caja</h1>
            <p class="text-secondary mb-0">Entradas de efectivo autorizadas por administracion hacia cajas abiertas.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.cash.index') }}">
                <i class="bi bi-arrow-left me-1"></i>Caja
            </a>
            @if (auth()->user()?->hasPermission('cash.funding.create'))
                <a class="btn btn-primary" href="{{ route('admin.cash.funding.create') }}">
                    <i class="bi bi-plus-lg me-1"></i>Nuevo refuerzo
                </a>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Sucursal / caja</th>
                        <th>Registrado por</th>
                        <th>Origen</th>
                        <th>Referencia</th>
                        <th class="text-end">Monto</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transfers as $transfer)
                        <tr>
                            <td class="small">{{ $transfer->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <div class="fw-semibold">{{ $transfer->branch?->name ?: '-' }}</div>
                                <small class="text-secondary">Caja #{{ $transfer->cash_session_id }} · {{ $transfer->cashSession?->user?->name ?: '-' }}</small>
                            </td>
                            <td>{{ $transfer->createdBy?->name ?: '-' }}</td>
                            <td>{{ $transfer->source ?: '-' }}</td>
                            <td><code>{{ $transfer->reference ?: '-' }}</code></td>
                            <td class="text-end fw-semibold text-success">RD$ {{ number_format((float) $transfer->amount, 2) }}</td>
                            <td><x-status-badge :status="$transfer->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No hay refuerzos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $transfers->links() }}</div>
@endsection
