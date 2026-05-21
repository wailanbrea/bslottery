@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-person-badge me-2" style="color: var(--argon-info);"></i>Empleados</h1>
        <p class="text-secondary mb-0">Registro de empleados activos, salarios y comisiones.</p>
    </div>
    @if (auth()->user()->hasPermission('payroll.manage'))
        <a class="btn btn-primary" href="{{ route('admin.employees.create') }}">
            <i class="bi bi-plus-lg me-1"></i>Nuevo empleado
        </a>
    @endif
</div>

<form class="row g-2 mb-4" method="GET">
    <div class="col-md-6">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre">
        </div>
    </div>
    <div class="col-md-3">
        <select class="form-select" name="status">
            <option value="">Todos los estados</option>
            <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>Activo</option>
            <option value="INACTIVE" {{ request('status') === 'INACTIVE' ? 'selected' : '' }}>Inactivo</option>
            <option value="TERMINATED" {{ request('status') === 'TERMINATED' ? 'selected' : '' }}>Terminado</option>
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-funnel me-1"></i>Filtrar</button>
        <a class="btn btn-link" href="{{ route('admin.employees.index') }}">Limpiar</a>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Puesto</th>
                    <th>Sucursal</th>
                    <th>Tipo salario</th>
                    <th class="text-end">Salario base</th>
                    <th class="text-end">Comisión %</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $employee->name }}</div>
                            @if ($employee->document_number)
                                <div class="text-secondary small">{{ $employee->document_number }}</div>
                            @endif
                        </td>
                        <td>{{ $employee->position }}</td>
                        <td>{{ $employee->branch?->name ?: 'Todas' }}</td>
                        <td>
                            <span class="badge {{ $employee->salary_type === 'FIXED' ? 'bg-info' : ($employee->salary_type === 'COMMISSION' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                {{ $employee->salary_type }}
                            </span>
                        </td>
                        <td class="text-end">RD$ {{ number_format((float)$employee->base_salary, 2) }}</td>
                        <td class="text-end">{{ $employee->commission_percent }}%</td>
                        <td><x-status-badge :status="$employee->status" /></td>
                        <td class="text-end">
                            @if (auth()->user()->hasPermission('payroll.manage'))
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.employees.edit', $employee) }}" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-secondary py-4">No hay empleados registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $employees->links() }}</div>
@endsection
