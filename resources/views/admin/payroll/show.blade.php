@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-calendar2-check me-2" style="color: var(--argon-info);"></i>Nómina #{{ $period->id }}</h1>
        <p class="text-secondary mb-0">
            {{ $period->period_start->format('d/m/Y') }} — {{ $period->period_end->format('d/m/Y') }} &bull; {{ $period->frequency }}
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        @php
            $badgeClass = match($period->status) {
                'DRAFT' => 'bg-warning text-dark', 'APPROVED' => 'bg-info', 'PAID' => 'bg-success', default => 'bg-secondary'
            };
        @endphp
        <x-status-badge :status="$period->status" />
        @if ($period->isDraft() && auth()->user()->hasPermission('payroll.approve'))
            <form method="POST" action="{{ route('admin.payroll.approve', $period) }}">
                @csrf @method('PATCH')
                <button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Aprobar</button>
            </form>
        @endif
        <a class="btn btn-outline-secondary" href="{{ route('admin.payroll.index') }}"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-body text-center">
            <div class="h4 mb-0 text-primary">RD$ {{ number_format((float)$period->total_gross, 2) }}</div>
            <div class="text-secondary small">Bruto total</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body text-center">
            <div class="h4 mb-0 text-danger">RD$ {{ number_format((float)$period->total_deductions, 2) }}</div>
            <div class="text-secondary small">Total deducciones</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body text-center">
            <div class="h4 mb-0 text-success">RD$ {{ number_format((float)$period->total_net, 2) }}</div>
            <div class="text-secondary small">Neto a pagar</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body text-center">
            <div class="h4 mb-0">{{ $period->details->count() }}</div>
            <div class="text-secondary small">Empleados</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Detalle por empleado</strong>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0 small">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Sucursal</th>
                    <th class="text-end">Salario</th>
                    <th class="text-end">Comisión</th>
                    <th class="text-end">Bono</th>
                    <th class="text-end">Bruto</th>
                    <th class="text-end text-danger">Avances</th>
                    <th class="text-end text-danger">Préstamos</th>
                    <th class="text-end text-danger">Faltantes</th>
                    <th class="text-end text-danger">Otros</th>
                    <th class="text-end fw-bold text-success">Neto</th>
                    <th>Estado</th>
                    @if ($period->isDraft() && auth()->user()->hasPermission('payroll.manage'))
                        <th></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($period->details as $detail)
                    <tr>
                        <td class="fw-semibold">{{ $detail->employee->name }}</td>
                        <td>{{ $detail->branch?->name ?: '—' }}</td>
                        <td class="text-end">{{ number_format((float)$detail->base_salary, 2) }}</td>
                        <td class="text-end">{{ number_format((float)$detail->commission, 2) }}</td>
                        <td class="text-end">{{ number_format((float)$detail->bonus, 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float)$detail->gross_pay, 2) }}</td>
                        <td class="text-end text-danger">{{ (float)$detail->advance_deduction > 0 ? number_format((float)$detail->advance_deduction, 2) : '—' }}</td>
                        <td class="text-end text-danger">{{ (float)$detail->loan_deduction > 0 ? number_format((float)$detail->loan_deduction, 2) : '—' }}</td>
                        <td class="text-end text-danger">{{ (float)$detail->cash_shortage > 0 ? number_format((float)$detail->cash_shortage, 2) : '—' }}</td>
                        <td class="text-end text-danger">{{ (float)$detail->other_deductions > 0 ? number_format((float)$detail->other_deductions, 2) : '—' }}</td>
                        <td class="text-end fw-bold text-success">RD$ {{ number_format((float)$detail->net_pay, 2) }}</td>
                        <td>
                            <x-status-badge :status="$detail->status" />
                        </td>
                        @if ($period->isDraft() && auth()->user()->hasPermission('payroll.manage'))
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editDetailModal"
                                    data-detail-id="{{ $detail->id }}"
                                    data-employee="{{ $detail->employee->name }}"
                                    data-bonus="{{ $detail->bonus }}"
                                    data-other-deductions="{{ $detail->other_deductions }}"
                                    data-url="{{ route('admin.payroll.detail.update', [$period, $detail]) }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="5" class="text-end">Totales:</td>
                    <td class="text-end">RD$ {{ number_format((float)$period->total_gross, 2) }}</td>
                    <td class="text-end text-danger">RD$ {{ number_format($period->details->sum(fn($d)=>(float)$d->advance_deduction), 2) }}</td>
                    <td class="text-end text-danger">RD$ {{ number_format($period->details->sum(fn($d)=>(float)$d->loan_deduction), 2) }}</td>
                    <td class="text-end text-danger">RD$ {{ number_format($period->details->sum(fn($d)=>(float)$d->cash_shortage), 2) }}</td>
                    <td class="text-end text-danger">RD$ {{ number_format($period->details->sum(fn($d)=>(float)$d->other_deductions), 2) }}</td>
                    <td class="text-end text-success">RD$ {{ number_format((float)$period->total_net, 2) }}</td>
                    <td></td>
                    @if ($period->isDraft() && auth()->user()->hasPermission('payroll.manage'))
                        <td></td>
                    @endif
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="mt-3 text-secondary small">
    Generada por: {{ $period->creator?->name ?: '—' }}
    @if ($period->approver) &bull; Aprobada por: {{ $period->approver->name }} el {{ $period->approved_at?->format('d/m/Y H:i') }} @endif
    @if ($period->paid_at) &bull; Pagada el: {{ $period->paid_at->format('d/m/Y H:i') }} @endif
</div>

@if ($period->isDraft() && auth()->user()->hasPermission('payroll.manage'))
<div class="modal fade" id="editDetailModal" tabindex="-1" aria-labelledby="editDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editDetailForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title" id="editDetailModalLabel">
                        <i class="bi bi-pencil me-2"></i>Editar ajustes — <span id="modalEmployeeName"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="inputBonus" class="form-label">Bono adicional (RD$)</label>
                        <input type="number" class="form-control" id="inputBonus" name="bonus"
                               min="0" step="0.01" required>
                        <div class="form-text">Se suma al salario base + comisión para calcular el bruto.</div>
                    </div>
                    <div class="mb-3">
                        <label for="inputOtherDeductions" class="form-label">Otras deducciones (RD$)</label>
                        <input type="number" class="form-control" id="inputOtherDeductions" name="other_deductions"
                               min="0" step="0.01" required>
                        <div class="form-text">Se resta del bruto junto con avances, préstamos y faltantes.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2 me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('editDetailModal').addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    document.getElementById('modalEmployeeName').textContent = btn.dataset.employee;
    document.getElementById('inputBonus').value = parseFloat(btn.dataset.bonus).toFixed(2);
    document.getElementById('inputOtherDeductions').value = parseFloat(btn.dataset.otherDeductions).toFixed(2);
    document.getElementById('editDetailForm').action = btn.dataset.url;
});
</script>
@endpush
@endif
@endsection
