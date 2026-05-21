@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-calendar-event me-2" style="color: var(--argon-info);"></i>Sorteos</h1>
            <p class="text-secondary mb-0">Sorteos programados por lotería.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if (auth()->user()->hasPermission('draws.update'))
                <form class="d-flex gap-2" method="POST" action="{{ route('admin.draws.open-time.update') }}">
                    @csrf
                    <input class="form-control form-control-sm" name="open_time" type="time" value="08:00" required style="width: 120px;">
                    <button class="btn btn-sm btn-outline-info" type="submit">
                        <i class="bi bi-clock me-1"></i>Apertura todos
                    </button>
                </form>
            @endif
            @if (auth()->user()->hasPermission('draws.create'))
                <a class="btn btn-primary" href="{{ route('admin.draws.create') }}">
                    <i class="bi bi-plus-lg me-1"></i>Crear sorteo
                </a>
            @endif
        </div>
    </div>

    <form class="row g-2 mb-4" method="GET">
        <div class="col-md-8">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input class="form-control" name="search" value="{{ $search }}" placeholder="Buscar por nombre o lotería">
                <button class="btn btn-outline-secondary" type="submit">Buscar</button>
            </div>
        </div>
        <div class="col-md-4">
            <select class="form-select" name="status" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                @foreach (['OPEN' => 'Abierto', 'CLOSING_SOON' => 'Por cerrar', 'CLOSED' => 'Cerrado', 'RESULT_REGISTERED' => 'Resultado registrado', 'RESULT_CONFIRMED' => 'Resultado confirmado', 'FINALIZED' => 'Finalizado', 'CANCELLED' => 'Cancelado'] as $val => $lbl)
                    <option value="{{ $val }}" @selected($status === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Lotería</th>
                        <th>Fecha</th>
                        <th>Apertura</th>
                        <th>Hora</th>
                        <th>Cierre</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($draws as $draw)
                        <tr>
                            <td class="fw-semibold">{{ $draw->name }}</td>
                            <td>{{ $draw->lottery->name }}</td>
                            <td>{{ $draw->draw_date->format('Y-m-d') }}</td>
                            <td>{{ $draw->open_time ?? '00:00' }}</td>
                            <td>{{ $draw->scheduled_time }}</td>
                            <td>{{ $draw->close_time }}</td>
                            <td>
                                <x-status-badge :status="$draw->status" />
                                @if ($draw->active_ticket_details_count > 0)
                                    <span class="badge bg-light text-dark border ms-1">{{ $draw->active_ticket_details_count }} jug.</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    @if (auth()->user()->hasPermission('draws.update') && $draw->status !== 'CLOSED' && $draw->status !== 'FINALIZED')
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.draws.edit', $draw) }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                    @if (auth()->user()->hasPermission('draws.close') && $draw->isOpen())
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#closeDrawModal{{ $draw->id }}" title="Cerrar sorteo">
                                                <i class="bi bi-lock"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#cancelDrawModal{{ $draw->id }}" title="Cancelar sorteo">
                                            <i class="bi bi-x-octagon"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-secondary py-4">No hay sorteos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $draws->links() }}</div>

    @foreach ($draws as $draw)
        @if (auth()->user()->hasPermission('draws.close') && $draw->isOpen())
            <div class="modal fade" id="closeDrawModal{{ $draw->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('admin.draws.close', $draw) }}" class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Cerrar sorteo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-2"><strong>{{ $draw->lottery->name }}</strong> / {{ $draw->name }}</p>
                            @if ($draw->active_ticket_details_count > 0)
                                <div class="alert alert-warning small">
                                    Este sorteo tiene {{ $draw->active_ticket_details_count }} jugada(s) activa(s). Debes decidir qué hacer con esos tickets antes de cerrar.
                                </div>
                                <label class="form-label">Acción sobre tickets activos</label>
                                <select class="form-select" name="ticket_resolution_policy" required>
                                    <option value="KEEP_CURRENT">Mantener válidos para este sorteo</option>
                                    <option value="TRANSFER_NEXT">Mantener válidos para el próximo sorteo</option>
                                    <option value="CANCEL_TICKETS">Anular tickets y registrar devolución</option>
                                </select>
                                <label class="form-label mt-3">Nota interna</label>
                                <textarea class="form-control" name="reason" rows="2" maxlength="500" placeholder="Motivo operativo del cierre"></textarea>
                            @else
                                <input type="hidden" name="ticket_resolution_policy" value="NONE">
                                <p class="text-secondary mb-0">No hay jugadas activas asociadas a este sorteo.</p>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver</button>
                            <button type="submit" class="btn btn-secondary">Cerrar sorteo</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="cancelDrawModal{{ $draw->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('admin.draws.cancel', $draw) }}" class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Cancelar sorteo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-2"><strong>{{ $draw->lottery->name }}</strong> / {{ $draw->name }}</p>
                            <div class="alert alert-danger small">
                                Cancelar un sorteo es una operación financiera auditable. Si hay tickets activos, selecciona si seguirán válidos en el próximo sorteo o si serán anulados.
                            </div>
                            <label class="form-label">Acción sobre tickets activos</label>
                            <select class="form-select" name="ticket_resolution_policy" required>
                                <option value="TRANSFER_NEXT">Mantener válidos para el próximo sorteo</option>
                                <option value="CANCEL_TICKETS">Anular tickets y registrar devolución</option>
                            </select>
                            <label class="form-label mt-3">Motivo de cancelación *</label>
                            <textarea class="form-control" name="reason" rows="3" maxlength="500" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver</button>
                            <button type="submit" class="btn btn-danger">Cancelar sorteo</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach
@endsection
