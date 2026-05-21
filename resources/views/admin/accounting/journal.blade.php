@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1"><i class="bi bi-journal-bookmark me-2" style="color: var(--argon-primary);"></i>Diario contable</h1>
        <p class="text-secondary mb-0">Asientos contables generados automáticamente por operaciones.</p>
    </div>

    <form class="mb-4" method="GET">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Buscar por descripción o número de asiento">
            <button class="btn btn-outline-secondary" type="submit">Buscar</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Sucursal</th>
                        <th>Origen</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td><code>{{ $entry->entry_number }}</code></td>
                            <td>{{ $entry->entry_date->format('Y-m-d') }}</td>
                            <td class="small">{{ Str::limit($entry->description, 60) }}</td>
                            <td>{{ $entry->branch?->name ?: '—' }}</td>
                            <td>{{ $entry->source_type ? $entry->source_type . ' #' . $entry->source_id : 'Manual' }}</td>
                            <td>
                                <x-status-badge :status="$entry->status" />
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.accounting.entry', $entry) }}">
                                    <i class="bi bi-eye me-1"></i>Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No hay asientos contables registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $entries->links() }}</div>
@endsection
