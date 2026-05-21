@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-journal-bookmark me-2" style="color: var(--argon-primary);"></i>Asiento {{ $entry->entry_number }}</h1>
            <p class="text-secondary mb-0">{{ $entry->entry_date->format('Y-m-d') }} — {{ $entry->description }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.accounting.journal') }}">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-secondary small text-uppercase">Número</div>
                    <div class="fw-semibold"><code>{{ $entry->entry_number }}</code></div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small text-uppercase">Fecha</div>
                    <div class="fw-semibold">{{ $entry->entry_date->format('Y-m-d') }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small text-uppercase">Sucursal</div>
                    <div class="fw-semibold">{{ $entry->branch?->name ?: '—' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small text-uppercase">Estado</div>
                    <x-status-badge :status="$entry->status" />
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small text-uppercase">Creado por</div>
                    <div class="fw-semibold">{{ $entry->creator?->name ?: '—' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small text-uppercase">Origen</div>
                    <div class="fw-semibold">{{ $entry->source_type ? $entry->source_type . ' #' . $entry->source_id : 'Manual' }}</div>
                </div>
                <div class="col-12">
                    <div class="text-secondary small text-uppercase">Descripción</div>
                    <div>{{ $entry->description }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <h2 class="h6 mb-0">Líneas del asiento</h2>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Cuenta</th>
                        <th class="text-end">Débito</th>
                        <th class="text-end">Crédito</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entry->lines as $line)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $line->account->name }}</div>
                                <code class="small">{{ $line->account->code }}</code>
                            </td>
                            <td class="text-end fw-semibold {{ $line->debit > 0 ? 'text-success' : '' }}">RD$ {{ number_format($line->debit, 2) }}</td>
                            <td class="text-end fw-semibold {{ $line->credit > 0 ? 'text-danger' : '' }}">RD$ {{ number_format($line->credit, 2) }}</td>
                            <td class="small">{{ $line->description ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-secondary py-4">Sin líneas.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td>Totales</td>
                        <td class="text-end text-success">RD$ {{ number_format($totalDebit, 2) }}</td>
                        <td class="text-end text-danger">RD$ {{ number_format($totalCredit, 2) }}</td>
                        <td>
                            @if (abs($totalDebit - $totalCredit) < 0.001)
                                <span class="badge bg-success">Cuadra</span>
                            @else
                                <span class="badge bg-danger">No cuadra</span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
