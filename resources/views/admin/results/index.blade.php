@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1"><i class="bi bi-trophy me-2" style="color: var(--argon-warning);"></i>Resultados</h1>
        <p class="text-secondary mb-0">Registro, confirmación y cálculo de ganadores.</p>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Sorteo</th>
                        <th>Lotería</th>
                        <th>1er</th>
                        <th>2do</th>
                        <th>3er</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th>Confirmación</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($results as $result)
                        <tr>
                            <td class="fw-semibold">{{ $result->draw->name }}</td>
                            <td>{{ $result->lottery->name }}</td>
                            <td><code class="fs-6">{{ $result->first_number }}</code></td>
                            <td><code>{{ $result->second_number ?: '—' }}</code></td>
                            <td><code>{{ $result->third_number ?: '—' }}</code></td>
                            <td><x-status-badge :status="$result->status" /></td>
                            <td>
                                <div class="fw-semibold">{{ $result->registeredBy?->name ?: '—' }}</div>
                                <div class="text-secondary small">{{ $result->registered_at?->format('d/m/Y h:i A') ?: '—' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $result->confirmedBy?->name ?: '—' }}</div>
                                <div class="text-secondary small">{{ $result->confirmed_at?->format('d/m/Y h:i A') ?: 'Pendiente' }}</div>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    @if (auth()->user()->hasPermission('results.confirm') && $result->status === 'REGISTERED' && $result->registered_by !== auth()->id())
                                        <form method="POST" action="{{ route('admin.results.confirm', $result) }}" onsubmit="return confirm('¿Confirmar este resultado?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success" type="submit">
                                                <i class="bi bi-check-lg me-1"></i>Confirmar
                                            </button>
                                        </form>
                                    @endif
                                    @if (auth()->user()->hasPermission('winners.calculate') && $result->status === 'CONFIRMED' && ! in_array($result->draw->status, ['WINNERS_CALCULATED', 'PAYMENTS_RELEASED', 'FINALIZED']))
                                        <form method="POST" action="{{ route('admin.results.calculate', $result->draw) }}" onsubmit="return confirm('¿Calcular ganadores?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-primary" type="submit">
                                                <i class="bi bi-calculator me-1"></i>Ganadores
                                            </button>
                                        </form>
                                    @endif
                                    @if (auth()->user()->hasPermission('payments.authorize') && $result->draw->status === 'WINNERS_CALCULATED')
                                        <form method="POST" action="{{ route('admin.results.authorize', $result->draw) }}" onsubmit="return confirm('¿Autorizar pagos?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-warning" type="submit">
                                                <i class="bi bi-unlock me-1"></i>Autorizar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-secondary py-4">No hay resultados registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $results->links() }}</div>

    @if (auth()->user()->hasPermission('results.create'))
        <div class="mt-4">
            <a class="btn btn-primary" href="{{ route('admin.results.create') }}">
                <i class="bi bi-plus-lg me-1"></i>Registrar resultado
            </a>
        </div>
    @endif
@endsection
