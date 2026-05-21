@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-receipt me-2" style="color: var(--argon-primary);"></i>Tickets</h1>
            <p class="text-secondary mb-0">Historial de ventas.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.tickets.create') }}">
            <i class="bi bi-cart-plus me-1"></i>Nueva venta
        </a>
    </div>

    <form class="mb-4" method="GET">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Buscar por número de ticket">
            <button class="btn btn-outline-secondary" type="submit">Buscar</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Origen</th>
                        <th>Sucursal</th>
                        <th>Cajero</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        @php
                            $origen = match(strtoupper((string)$ticket->sale_mode)) {
                                'MOBILE' => ['label' => 'App', 'badge' => 'bg-success-subtle text-success-emphasis', 'icon' => 'phone'],
                                'OFFLINE' => ['label' => 'Offline', 'badge' => 'bg-warning-subtle text-warning-emphasis', 'icon' => 'cloud-slash'],
                                'ONLINE' => ['label' => 'Web', 'badge' => 'bg-primary-subtle text-primary-emphasis', 'icon' => 'pc-display'],
                                default => ['label' => $ticket->sale_mode ?: '—', 'badge' => 'bg-secondary-subtle text-secondary-emphasis', 'icon' => 'question-circle'],
                            };
                        @endphp
                        <tr>
                            <td><code>{{ $ticket->ticket_number }}</code></td>
                            <td>
                                <span class="badge {{ $origen['badge'] }}">
                                    <i class="bi bi-{{ $origen['icon'] }} me-1"></i>{{ $origen['label'] }}
                                </span>
                            </td>
                            <td>{{ $ticket->branch?->name ?: '—' }}</td>
                            <td>{{ $ticket->user?->username ?: '—' }}</td>
                            <td class="small">{{ $ticket->sold_at->format('Y-m-d H:i') }}</td>
                            <td class="fw-semibold">RD$ {{ number_format($ticket->total_amount, 2) }}</td>
                            <td><x-status-badge :status="$ticket->status" /></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.tickets.show', $ticket) }}">
                                    <i class="bi bi-eye me-1"></i>Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-secondary py-4">No hay tickets registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $tickets->links() }}</div>
@endsection
