@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-list-task me-2" style="color: var(--argon-info);"></i>Cola de impresion</h1>
        <p class="text-secondary mb-0">Trabajos de impresion enviados al Print Connector.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.printers.queue', ['printer_config_id' => $printerId]) }}">
        <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
    </a>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-end justify-content-between">
        <form method="GET" action="{{ route('admin.printers.queue') }}" class="d-flex gap-2 align-items-end">
            <div>
                <label class="form-label mb-1 small">Filtrar por impresora</label>
                <select name="printer_config_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    @foreach ($printers as $printer)
                        <option value="{{ $printer->id }}" @selected($printerId === $printer->id)>
                            {{ $printer->name }} @if($printer->terminal_key) ({{ $printer->terminal_key }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
        @if (auth()->user()->hasPermission('printers.configure'))
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('admin.printers.queue.clear') }}"
                      onsubmit="return confirm('Cancelar todos los trabajos pendientes?')">
                    @csrf
                    <input type="hidden" name="printer_config_id" value="{{ $printerId }}">
                    <button class="btn btn-outline-warning" type="submit">
                        <i class="bi bi-x-circle me-1"></i>Cancelar pendientes
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.printers.queue.purge') }}"
                      onsubmit="return confirm('Eliminar TODOS los trabajos de la cola? Esta accion no se puede deshacer.')">
                    @csrf
                    <input type="hidden" name="printer_config_id" value="{{ $printerId }}">
                    <button class="btn btn-outline-danger" type="submit">
                        <i class="bi bi-trash me-1"></i>Vaciar cola
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ticket</th>
                    <th>Tipo</th>
                    <th>Impresora</th>
                    <th>Estado</th>
                    <th>Intentos</th>
                    <th>Creado</th>
                    <th>Impreso</th>
                    <th>Error</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jobs as $job)
                    <tr>
                        <td class="text-secondary small">{{ $job->id }}</td>
                        <td>{{ $job->ticket?->ticket_number ?: '—' }}</td>
                        <td><span class="badge bg-secondary">{{ $job->type }}</span></td>
                        <td class="small">{{ $job->printerConfig?->name ?: '—' }}</td>
                        <td>
                            @php
                                $badge = match($job->status) {
                                    'PRINTED' => 'bg-success',
                                    'PENDING' => 'bg-info',
                                    'PROCESSING' => 'bg-warning text-dark',
                                    'FAILED' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $badge }}">{{ $job->status }}</span>
                        </td>
                        <td class="text-center">{{ $job->attempts }}</td>
                        <td class="small">{{ $job->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="small">{{ $job->printed_at?->format('Y-m-d H:i:s') ?: '—' }}</td>
                        <td class="small text-danger">{{ $job->error_message ? \Illuminate\Support\Str::limit($job->error_message, 50) : '' }}</td>
                        <td class="text-end">
                            @if (auth()->user()->hasPermission('printers.configure') && in_array($job->status, ['FAILED', 'PROCESSING'], true))
                                <form method="POST" action="{{ route('admin.printers.queue.retry', $job) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary" type="submit" title="Reencolar">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-secondary py-4">No hay trabajos en la cola.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
