@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-printer me-2" style="color: var(--argon-secondary);"></i>Tickets reimpresos</h1>
        <p class="text-secondary mb-0">Historial auditable de reimpresiones por rango, sucursal, cajero, impresora y estado.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index', request()->query()) }}">
        <i class="bi bi-arrow-left me-1"></i>Reportes
    </a>
</div>

@include('admin.reports.partials.filters')

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Sucursal</th>
                    <th>Cajero</th>
                    <th class="text-end">Conteo</th>
                    <th>Impresora</th>
                    <th>Dispositivo</th>
                    <th>Estado</th>
                    <th class="text-end">Intentos</th>
                    <th>Impreso</th>
                    <th>Creado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jobs as $job)
                    <tr>
                        <td>
                            @if ($job->ticket)
                                <a href="{{ route('admin.tickets.show', $job->ticket) }}"><code>{{ $job->ticket->ticket_number }}</code></a>
                            @else
                                <span class="text-secondary">Sin ticket</span>
                            @endif
                        </td>
                        <td>{{ $job->ticket?->branch?->name ?? $job->branch?->name }}</td>
                        <td>{{ $job->ticket?->user?->username ?? '—' }}</td>
                        <td class="text-end fw-semibold">{{ number_format((int) ($job->ticket?->print_count ?? 0)) }}</td>
                        <td>{{ $job->printerConfig?->name ?? '—' }}</td>
                        <td>{{ $job->device?->name ?? '—' }}</td>
                        <td><x-status-badge :status="$job->status" /></td>
                        <td class="text-end">{{ number_format((int) $job->attempts) }}</td>
                        <td class="small">{{ $job->printed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="small">{{ $job->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-secondary">Sin reimpresiones para los filtros seleccionados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $jobs->links() }}</div>
@endsection
