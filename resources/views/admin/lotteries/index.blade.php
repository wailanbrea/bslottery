@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-ticket-perforated me-2" style="color: var(--argon-warning);"></i>Loterías</h1>
            <p class="text-secondary mb-0">Catálogo de loterías disponibles para sorteos.</p>
        </div>
        @if (auth()->user()->hasPermission('lotteries.create'))
            <a class="btn btn-primary" href="{{ route('admin.lotteries.create') }}">
                <i class="bi bi-plus-lg me-1"></i>Crear lotería
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
                        <th>País</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lotteries as $lottery)
                        <tr>
                            <td><code>{{ $lottery->code }}</code></td>
                            <td class="fw-semibold">{{ $lottery->name }}</td>
                            <td>{{ $lottery->country }}</td>
                            <td>
                                <x-status-badge :status="$lottery->status" />
                            </td>
                            <td class="text-end">
                                @if (auth()->user()->hasPermission('lotteries.update'))
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.lotteries.edit', $lottery) }}">
                                        <i class="bi bi-pencil me-1"></i>Editar
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-4">No hay loterías registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $lotteries->links() }}</div>
@endsection
