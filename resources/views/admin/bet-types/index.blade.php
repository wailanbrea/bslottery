@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-dice-3 me-2" style="color: var(--argon-success);"></i>Tipos de jugada</h1>
            <p class="text-secondary mb-0">Quiniela, pale, tripleta y otras jugadas disponibles.</p>
        </div>
        @if (auth()->user()->hasPermission('lotteries.create'))
            <a class="btn btn-primary" href="{{ route('admin.bet-types.create') }}">
                <i class="bi bi-plus-lg me-1"></i>Crear tipo
            </a>
        @endif
    </div>

    <form class="mb-4" method="GET">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Buscar por nombre o código">
            <button class="btn btn-outline-secondary" type="submit">Buscar</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Dígitos</th>
                        <th>Núm/jugada</th>
                        <th>Posición</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($betTypes as $betType)
                        <tr>
                            <td><code>{{ $betType->code }}</code></td>
                            <td class="fw-semibold">{{ $betType->name }}</td>
                            <td>{{ $betType->digits_count }}</td>
                            <td>{{ $betType->numbers_count }}</td>
                            <td>{{ $betType->requires_position ? 'Sí' : 'No' }}</td>
                            <td>
                                <x-status-badge :status="$betType->status" />
                            </td>
                            <td class="text-end">
                                @if (auth()->user()->hasPermission('lotteries.update'))
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.bet-types.edit', $betType) }}">
                                        <i class="bi bi-pencil me-1"></i>Editar
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No hay tipos de jugada registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $betTypes->links() }}</div>
@endsection
