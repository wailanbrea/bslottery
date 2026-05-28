@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-ticket-perforated me-2" style="color: var(--argon-warning);"></i>Loterias</h1>
            <p class="text-secondary mb-0">Catalogo de loterias disponibles para sorteos.</p>
        </div>
        <div class="d-flex gap-2">
            @if (auth()->user()->hasPermission('lotteries.toggle'))
                <form method="POST" action="{{ route('admin.lotteries.bulk-status') }}" onsubmit="return confirm('Se abriran todas las loterias de la empresa activa.');">
                    @csrf
                    <input type="hidden" name="status" value="ACTIVE">
                    <button class="btn btn-outline-success" type="submit">
                        <i class="bi bi-unlock me-1"></i>Abrir todas
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.lotteries.bulk-status') }}" onsubmit="return confirm('Se cerraran todas las loterias de la empresa activa.');">
                    @csrf
                    <input type="hidden" name="status" value="INACTIVE">
                    <button class="btn btn-outline-danger" type="submit">
                        <i class="bi bi-lock me-1"></i>Cerrar todas
                    </button>
                </form>
            @endif

            @if (auth()->user()->hasPermission('lotteries.create'))
                <a class="btn btn-primary" href="{{ route('admin.lotteries.create') }}">
                    <i class="bi bi-plus-lg me-1"></i>Crear loteria
                </a>
            @endif
        </div>
    </div>

    <form class="mb-4" method="GET">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Buscar por nombre o codigo">
            <button class="btn btn-outline-secondary" type="submit">Buscar</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Nombre</th>
                        <th>Pais</th>
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
                                <div class="d-inline-flex gap-2">
                                    @if (auth()->user()->hasPermission('lotteries.toggle'))
                                        <form method="POST" action="{{ route('admin.lotteries.status', $lottery) }}" onsubmit="return confirm('Se {{ $lottery->status === 'ACTIVE' ? 'cerrara' : 'abrira' }} la loteria {{ $lottery->name }}.');">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ $lottery->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE' }}">
                                            <button class="btn btn-sm {{ $lottery->status === 'ACTIVE' ? 'btn-outline-danger' : 'btn-outline-success' }}" type="submit">
                                                <i class="bi {{ $lottery->status === 'ACTIVE' ? 'bi-lock' : 'bi-unlock' }} me-1"></i>{{ $lottery->status === 'ACTIVE' ? 'Cerrar' : 'Abrir' }}
                                            </button>
                                        </form>
                                    @endif

                                    @if (auth()->user()->hasPermission('lotteries.update'))
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.lotteries.edit', $lottery) }}">
                                            <i class="bi bi-pencil me-1"></i>Editar
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-4">No hay loterias registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $lotteries->links() }}</div>
@endsection
