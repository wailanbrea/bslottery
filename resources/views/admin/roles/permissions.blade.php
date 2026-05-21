@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-4">
        <i class="bi bi-key me-2" style="color: var(--argon-primary);"></i>
        Permisos: {{ $role->name }}
    </h1>

    <form method="POST" action="{{ route('admin.roles.permissions.update', $role) }}" class="card">
        @csrf
        @method('PUT')

        <div class="card-body">
            @foreach ($permissions as $module => $items)
                <div class="mb-4">
                    <h2 class="h6 text-uppercase text-secondary mb-3">{{ $module }}</h2>
                    <div class="row">
                        @foreach ($items as $permission)
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="permission_ids[]"
                                        value="{{ $permission->id }}"
                                        id="permission_{{ $permission->id }}"
                                        @checked($role->permissions->contains('id', $permission->id))
                                    >
                                    <label class="form-check-label" for="permission_{{ $permission->id }}">
                                        <code>{{ $permission->slug }}</code>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check-lg me-1"></i>Guardar permisos
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.roles.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
