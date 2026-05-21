@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-cash-coin me-2" style="color: var(--argon-info);"></i>Avances de nómina</h1>
        <p class="text-secondary mb-0">Avances de efectivo solicitados por empleados, descontados en la próxima nómina.</p>
    </div>
    @if (auth()->user()->hasPermission('payroll.manage'))
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAdvanceModal">
            <i class="bi bi-plus-lg me-1"></i>Nuevo avance
        </button>
    @endif
</div>

<form class="row g-2 mb-4" method="GET">
    <div class="col-auto">
        <select class="form-select" name="status">
            <option value="">Todos</option>
            <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>Pendiente</option>
            <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>Aprobado</option>
            <option value="PAID" {{ request('status') === 'PAID' ? 'selected' : '' }}>Pagado</option>
            <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>Rechazado</option>
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-funnel"></i></button>
        <a class="btn btn-link" href="{{ route('admin.employees.advances') }}">Limpiar</a>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Sucursal</th>
                    <th class="text-end">Monto</th>
                    <th>Fecha solicitado</th>
                    <th>Estado</th>
                    <th>Aprobado por</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($advances as $advance)
                    <tr>
                        <td class="fw-semibold">{{ $advance->employee->name }}</td>
                        <td>{{ $advance->branch?->name ?: '—' }}</td>
                        <td class="text-end">RD$ {{ number_format((float)$advance->amount, 2) }}</td>
                        <td>{{ $advance->requested_at->format('d/m/Y') }}</td>
                        <td>
                            @php
                                $badgeClass = match($advance->status) {
                                    'APPROVED' => 'bg-success', 'PENDING' => 'bg-warning text-dark',
                                    'PAID' => 'bg-info', 'REJECTED' => 'bg-danger', default => 'bg-secondary'
                                };
                            @endphp
                            <x-status-badge :status="$advance->status" />
                        </td>
                        <td>{{ $advance->approver?->name ?: '—' }}</td>
                        <td class="text-end">
                            @if ($advance->isPending() && auth()->user()->hasPermission('payroll.approve'))
                                <form method="POST" action="{{ route('admin.employees.advances.approve', $advance) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="approve">
                                    <button class="btn btn-sm btn-success" title="Aprobar"><i class="bi bi-check-lg"></i></button>
                                </form>
                                <form method="POST" action="{{ route('admin.employees.advances.approve', $advance) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="reject">
                                    <button class="btn btn-sm btn-danger" title="Rechazar"><i class="bi bi-x-lg"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-4">No hay avances registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $advances->links() }}</div>

@if (auth()->user()->hasPermission('payroll.manage'))
<div class="modal fade" id="newAdvanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.employees.advances.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Registrar avance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Empleado <span class="text-danger">*</span></label>
                        <select class="form-select" name="employee_id" required>
                            <option value="">Seleccionar…</option>
                            @foreach (\App\Models\Employee::where('company_id', session('active_company_id'))->where('status', 'ACTIVE')->orderBy('name')->get() as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto (RD$) <span class="text-danger">*</span></label>
                        <input class="form-control" type="number" step="0.01" min="1" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha de solicitud</label>
                        <input class="form-control" type="date" name="requested_at" value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar avance</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
