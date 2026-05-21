@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-bank me-2" style="color: var(--argon-info);"></i>Préstamos a empleados</h1>
        <p class="text-secondary mb-0">Préstamos con descuento automático en nómina.</p>
    </div>
    @if (auth()->user()->hasPermission('payroll.manage'))
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newLoanModal">
            <i class="bi bi-plus-lg me-1"></i>Nuevo préstamo
        </button>
    @endif
</div>

<form class="row g-2 mb-4" method="GET">
    <div class="col-auto">
        <select class="form-select" name="status">
            <option value="">Todos</option>
            <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>Activo</option>
            <option value="PAID_OFF" {{ request('status') === 'PAID_OFF' ? 'selected' : '' }}>Pagado</option>
            <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>Cancelado</option>
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-funnel"></i></button>
        <a class="btn btn-link" href="{{ route('admin.employees.loans') }}">Limpiar</a>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th class="text-end">Principal</th>
                    <th class="text-end">Saldo</th>
                    <th class="text-end">Cuota</th>
                    <th>Inicio</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($loans as $loan)
                    <tr>
                        <td class="fw-semibold">{{ $loan->employee->name }}</td>
                        <td class="text-end">RD$ {{ number_format((float)$loan->principal, 2) }}</td>
                        <td class="text-end {{ (float)$loan->balance > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                            RD$ {{ number_format((float)$loan->balance, 2) }}
                        </td>
                        <td class="text-end">RD$ {{ number_format((float)$loan->installment, 2) }}</td>
                        <td>{{ $loan->started_at->format('d/m/Y') }}</td>
                        <td>
                            @php
                                $badgeClass = match($loan->status) {
                                    'ACTIVE' => 'bg-warning text-dark', 'PAID_OFF' => 'bg-success',
                                    'CANCELLED' => 'bg-secondary', default => 'bg-secondary'
                                };
                            @endphp
                            <x-status-badge :status="$loan->status" />
                        </td>
                        <td class="text-end">
                            @if ($loan->isActive() && auth()->user()->hasPermission('payroll.manage'))
                                <form method="POST" action="{{ route('admin.employees.loans.cancel', $loan) }}" class="d-inline"
                                    onsubmit="return confirm('¿Cancelar este préstamo?')">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-danger" title="Cancelar préstamo">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-4">No hay préstamos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $loans->links() }}</div>

@if (auth()->user()->hasPermission('payroll.manage'))
<div class="modal fade" id="newLoanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.employees.loans.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Registrar préstamo</h5>
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
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Monto préstamo (RD$) <span class="text-danger">*</span></label>
                            <input class="form-control" type="number" step="0.01" min="1" name="principal" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Cuota por período (RD$) <span class="text-danger">*</span></label>
                            <input class="form-control" type="number" step="0.01" min="1" name="installment" required>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Fecha de inicio</label>
                        <input class="form-control" type="date" name="started_at" value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar préstamo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
