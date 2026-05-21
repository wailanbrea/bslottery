@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-4">
        <i class="bi bi-building me-2" style="color: var(--argon-primary);"></i>
        {{ $company->exists ? 'Editar empresa' : 'Crear empresa' }}
    </h1>

    <form method="POST" action="{{ $company->exists ? route('admin.companies.update', $company) : route('admin.companies.store') }}" class="card">
        @csrf
        @if ($company->exists)
            @method('PUT')
        @endif

        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nombre *</label>
                    <input class="form-control" name="name" required maxlength="150" value="{{ old('name', $company->name) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Razón social</label>
                    <input class="form-control" name="legal_name" maxlength="200" value="{{ old('legal_name', $company->legal_name) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">RNC</label>
                    <input class="form-control" name="rnc" maxlength="50" value="{{ old('rnc', $company->rnc) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Teléfono</label>
                    <input class="form-control" name="phone" maxlength="50" value="{{ old('phone', $company->phone) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Email</label>
                    <input class="form-control" name="email" type="email" maxlength="150" value="{{ old('email', $company->email) }}">
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">Dirección</label>
                    <input class="form-control" name="address" maxlength="255" value="{{ old('address', $company->address) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Estado *</label>
                    <select class="form-select" name="status" required>
                        @foreach (['ACTIVE', 'INACTIVE', 'SUSPENDED'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $company->status) === $status)>{{ \App\View\Components\StatusBadge::labelFor($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check-lg me-1"></i>Guardar
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.companies.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
