@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-4">
        <i class="bi bi-shop me-2" style="color: var(--argon-success);"></i>
        {{ $branch->exists ? 'Editar sucursal' : 'Crear sucursal' }}
    </h1>

    <form method="POST" action="{{ $branch->exists ? route('admin.branches.update', $branch) : route('admin.branches.store') }}" class="card">
        @csrf
        @if ($branch->exists)
            @method('PUT')
        @endif

        <div class="card-body">
            <div class="row">
                @if (auth()->user()->isSuperAdmin())
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Empresa *</label>
                        <select class="form-select" name="company_id" required>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected((int) old('company_id', $branch->company_id) === $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3 mb-3">
                    <label class="form-label">Código *</label>
                    <input class="form-control" name="code" required maxlength="50" value="{{ old('code', $branch->code) }}">
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">Nombre *</label>
                    <input class="form-control" name="name" required maxlength="150" value="{{ old('name', $branch->name) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Teléfono</label>
                    <input class="form-control" name="phone" maxlength="50" value="{{ old('phone', $branch->phone) }}">
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">Dirección</label>
                    <input class="form-control" name="address" maxlength="255" value="{{ old('address', $branch->address) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Encargado</label>
                    <input class="form-control" name="manager_name" maxlength="150" value="{{ old('manager_name', $branch->manager_name) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Estado *</label>
                    <select class="form-select" name="status" required>
                        @foreach (['ACTIVE', 'INACTIVE', 'SUSPENDED'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $branch->status) === $status)>{{ \App\View\Components\StatusBadge::labelFor($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Minutos offline</label>
                    <input class="form-control" name="offline_max_minutes" type="number" min="0" max="10080" value="{{ old('offline_max_minutes', $branch->offline_max_minutes) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Cupo offline total</label>
                    <input class="form-control" name="offline_total_limit" type="number" min="0" step="0.01" value="{{ old('offline_total_limit', $branch->offline_total_limit) }}">
                </div>
            </div>

            <div class="row mt-2">
                @foreach ([
                    'can_sell_online' => 'Venta online',
                    'can_sell_offline' => 'Venta offline',
                    'cash_control_enabled' => 'Caja',
                    'accounting_enabled' => 'Contabilidad',
                    'payroll_enabled' => 'Nómina',
                ] as $field => $label)
                    <div class="col-md-3 mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="{{ $field }}" value="1" @checked(old($field, $branch->{$field}))>
                            <label class="form-check-label">{{ $label }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check-lg me-1"></i>Guardar
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.branches.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
