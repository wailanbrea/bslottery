@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-4">
        <i class="bi bi-people me-2" style="color: var(--argon-info);"></i>
        {{ $targetUser->exists ? 'Editar usuario' : 'Crear usuario' }}
    </h1>

    <form method="POST" action="{{ $targetUser->exists ? route('admin.users.update', $targetUser) : route('admin.users.store') }}" class="card">
        @csrf
        @if ($targetUser->exists)
            @method('PUT')
        @endif

        <div class="card-body">
            <div class="row">
                @if (auth()->user()->isSuperAdmin())
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Empresa *</label>
                        <select class="form-select" name="company_id" required>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected((int) old('company_id', $targetUser->company_id) === $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-6 mb-3">
                    <label class="form-label">Sucursal</label>
                    <select class="form-select" name="branch_id">
                        <option value="">Todas</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) old('branch_id', $targetUser->branch_id) === $branch->id)>{{ $branch->code }} — {{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Nombre *</label>
                    <input class="form-control" name="name" required maxlength="150" value="{{ old('name', $targetUser->name) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Usuario *</label>
                    <input class="form-control" name="username" required maxlength="80" value="{{ old('username', $targetUser->username) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input class="form-control" name="email" type="email" maxlength="150" value="{{ old('email', $targetUser->email) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Rol *</label>
                    <select class="form-select" name="role_id" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((int) old('role_id', $targetUser->role_id) === $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Estado *</label>
                    <select class="form-select" name="status" required>
                        @foreach (['ACTIVE', 'INACTIVE', 'BLOCKED'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $targetUser->status) === $status)>{{ \App\View\Components\StatusBadge::labelFor($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Contraseña {{ $targetUser->exists ? '(dejar vacía para mantener)' : '*' }}</label>
                    <input class="form-control" name="password" type="password" autocomplete="new-password" @required(! $targetUser->exists)>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Confirmar contraseña</label>
                    <input class="form-control" name="password_confirmation" type="password" autocomplete="new-password" @required(! $targetUser->exists)>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check-lg me-1"></i>Guardar
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
