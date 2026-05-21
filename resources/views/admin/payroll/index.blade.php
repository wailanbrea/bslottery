@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-calendar2-check me-2" style="color: var(--argon-info);"></i>Nómina</h1>
        <p class="text-secondary mb-0">Períodos de nómina: generación, aprobación y pago.</p>
    </div>
    @if (auth()->user()->hasPermission('payroll.manage'))
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#genModal">
            <i class="bi bi-plus-lg me-1"></i>Generar nómina
        </button>
    @endif
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-body text-center">
            <div class="h3 mb-0 text-primary">{{ $activeEmployees }}</div>
            <div class="text-secondary small">Empleados activos</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-body text-center">
            <div class="h3 mb-0 {{ $pendingAdvances > 0 ? 'text-warning' : 'text-success' }}">{{ $pendingAdvances }}</div>
            <div class="text-secondary small">Avances pendientes</div>
            @if ($pendingAdvances > 0)
                <a class="small" href="{{ route('admin.employees.advances') }}?status=PENDING">Ver avances</a>
            @endif
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-body text-center">
            <div class="h3 mb-0 {{ $activeLoans > 0 ? 'text-info' : 'text-success' }}">{{ $activeLoans }}</div>
            <div class="text-secondary small">Préstamos activos</div>
        </div>
    </div>
</div>

<form class="row g-2 mb-4" method="GET">
    <div class="col-auto">
        <select class="form-select" name="status">
            <option value="">Todos</option>
            <option value="DRAFT" {{ request('status') === 'DRAFT' ? 'selected' : '' }}>Borrador</option>
            <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>Aprobada</option>
            <option value="PAID" {{ request('status') === 'PAID' ? 'selected' : '' }}>Pagada</option>
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-funnel"></i></button>
        <a class="btn btn-link" href="{{ route('admin.payroll.index') }}">Limpiar</a>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Período</th>
                    <th>Frecuencia</th>
                    <th class="text-end">Empleados</th>
                    <th class="text-end">Bruto</th>
                    <th class="text-end">Deducciones</th>
                    <th class="text-end">Neto</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($periods as $period)
                    <tr>
                        <td class="text-secondary small">#{{ $period->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $period->period_start->format('d/m/Y') }} — {{ $period->period_end->format('d/m/Y') }}</div>
                        </td>
                        <td><span class="badge bg-secondary">{{ $period->frequency }}</span></td>
                        <td class="text-end">{{ $period->details_count }}</td>
                        <td class="text-end">RD$ {{ number_format((float)$period->total_gross, 2) }}</td>
                        <td class="text-end text-danger">RD$ {{ number_format((float)$period->total_deductions, 2) }}</td>
                        <td class="text-end fw-bold text-success">RD$ {{ number_format((float)$period->total_net, 2) }}</td>
                        <td>
                            @php
                                $badgeClass = match($period->status) {
                                    'DRAFT' => 'bg-warning text-dark', 'APPROVED' => 'bg-info',
                                    'PAID' => 'bg-success', default => 'bg-secondary'
                                };
                            @endphp
                            <x-status-badge :status="$period->status" />
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.payroll.show', $period) }}" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if ($period->isDraft() && auth()->user()->hasPermission('payroll.approve'))
                                    <form method="POST" action="{{ route('admin.payroll.approve', $period) }}">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-outline-success" title="Aprobar nómina"><i class="bi bi-check2-circle"></i></button>
                                    </form>
                                @endif
                                @if ($period->isApproved() && auth()->user()->hasPermission('payroll.approve'))
                                    <button class="btn btn-sm btn-outline-warning" title="Pagar nómina"
                                        data-bs-toggle="modal" data-bs-target="#payModal{{ $period->id }}">
                                        <i class="bi bi-cash-stack"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @if ($period->isApproved())
                    <div class="modal fade" id="payModal{{ $period->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.payroll.pay', $period) }}">
                                    @csrf @method('PATCH')
                                    <div class="modal-header"><h5 class="modal-title">Pagar nómina #{{ $period->id }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <p>Monto neto a pagar: <strong>RD$ {{ number_format((float)$period->total_net, 2) }}</strong></p>
                                        <div class="mb-3">
                                            <label class="form-label">Caja (opcional — registra movimiento de caja)</label>
                                            <select class="form-select" name="cash_session_id">
                                                <option value="">Sin vincular a caja</option>
                                                @foreach (\App\Models\CashSession::where('company_id', session('active_company_id'))->where('status', 'OPEN')->get() as $cs)
                                                    <option value="{{ $cs->id }}">Caja #{{ $cs->id }} — {{ $cs->branch?->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-warning">Confirmar pago</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                @empty
                    <tr><td colspan="9" class="text-center text-secondary py-4">No hay períodos de nómina.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $periods->links() }}</div>

@if (auth()->user()->hasPermission('payroll.manage'))
<div class="modal fade" id="genModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.payroll.generate') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Generar nómina</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Frecuencia</label>
                        <select class="form-select" name="frequency">
                            <option value="WEEKLY">Semanal</option>
                            <option value="BIWEEKLY">Quincenal</option>
                            <option value="MONTHLY" selected>Mensual</option>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Inicio del período</label>
                            <input class="form-control" type="date" name="period_start" value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fin del período</label>
                            <input class="form-control" type="date" name="period_end" value="{{ now()->endOfMonth()->format('Y-m-d') }}" required>
                        </div>
                    </div>
                    <p class="text-secondary small mt-3">
                        Se calcularán automáticamente salarios, comisiones sobre ventas del período, avances aprobados y cuotas de préstamos activos.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Generar nómina</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
