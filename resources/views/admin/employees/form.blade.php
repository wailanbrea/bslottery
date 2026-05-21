@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h1 class="h4 mb-1">
        <i class="bi bi-person-badge me-2" style="color: var(--argon-info);"></i>
        {{ $employee->exists ? 'Editar empleado' : 'Nuevo empleado' }}
    </h1>
</div>

<form method="POST" action="{{ $employee->exists ? route('admin.employees.update', $employee) : route('admin.employees.store') }}">
    @csrf
    @if ($employee->exists) @method('PUT') @endif

    <div class="card mb-4">
        <div class="card-header fw-semibold">Datos personales</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre completo <span class="text-danger">*</span></label>
                    <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $employee->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cédula / Documento</label>
                    <input class="form-control @error('document_number') is-invalid @enderror" name="document_number" value="{{ old('document_number', $employee->document_number) }}">
                    @error('document_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Teléfono</label>
                    <input class="form-control" name="phone" value="{{ old('phone', $employee->phone) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Usuario del sistema</label>
                    <select class="form-select" name="user_id">
                        <option value="">Sin vincular</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" {{ old('user_id', $employee->user_id) == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->username }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Dirección</label>
                    <input class="form-control" name="address" value="{{ old('address', $employee->address) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header fw-semibold">Puesto y salario</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Sucursal</label>
                    <select class="form-select" name="branch_id">
                        <option value="">Todas las sucursales</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id', $employee->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Puesto</label>
                    <input class="form-control" name="position" value="{{ old('position', $employee->position ?? 'Staff') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipo de salario <span class="text-danger">*</span></label>
                    <select class="form-select @error('salary_type') is-invalid @enderror" name="salary_type" required>
                        <option value="FIXED" {{ old('salary_type', $employee->salary_type) === 'FIXED' ? 'selected' : '' }}>Fijo</option>
                        <option value="COMMISSION" {{ old('salary_type', $employee->salary_type) === 'COMMISSION' ? 'selected' : '' }}>Comisión</option>
                        <option value="MIXED" {{ old('salary_type', $employee->salary_type) === 'MIXED' ? 'selected' : '' }}>Fijo + Comisión</option>
                    </select>
                    @error('salary_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Salario base (RD$)</label>
                    <input class="form-control @error('base_salary') is-invalid @enderror" type="number" step="0.01" min="0" name="base_salary" value="{{ old('base_salary', $employee->base_salary ?? 0) }}">
                    @error('base_salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Comisión sobre ventas (%)</label>
                    <input class="form-control" type="number" step="0.01" min="0" max="100" name="commission_percent" value="{{ old('commission_percent', $employee->commission_percent ?? 0) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="status">
                        <option value="ACTIVE" {{ old('status', $employee->status ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : '' }}>Activo</option>
                        <option value="INACTIVE" {{ old('status', $employee->status) === 'INACTIVE' ? 'selected' : '' }}>Inactivo</option>
                        <option value="TERMINATED" {{ old('status', $employee->status) === 'TERMINATED' ? 'selected' : '' }}>Terminado</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha de ingreso</label>
                    <input class="form-control" type="date" name="hired_at" value="{{ old('hired_at', $employee->hired_at?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha de egreso</label>
                    <input class="form-control" type="date" name="terminated_at" value="{{ old('terminated_at', $employee->terminated_at?->format('Y-m-d')) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-check-lg me-1"></i>{{ $employee->exists ? 'Actualizar' : 'Crear empleado' }}
        </button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.employees.index') }}">Cancelar</a>
    </div>
</form>
@endsection
