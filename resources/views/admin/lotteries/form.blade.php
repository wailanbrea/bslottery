@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-4">
        <i class="bi bi-ticket-perforated me-2" style="color: var(--argon-warning);"></i>
        {{ $lottery->exists ? 'Editar lotería' : 'Crear lotería' }}
    </h1>

    <form method="POST" action="{{ $lottery->exists ? route('admin.lotteries.update', $lottery) : route('admin.lotteries.store') }}" class="card">
        @csrf
        @if ($lottery->exists)
            @method('PUT')
        @endif

        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Código *</label>
                    <input class="form-control" name="code" required maxlength="50" value="{{ old('code', $lottery->code) }}" placeholder="Ej: LOTNAC">
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">Nombre *</label>
                    <input class="form-control" name="name" required maxlength="150" value="{{ old('name', $lottery->name) }}" placeholder="Ej: Lotería Nacional">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">País</label>
                    <select class="form-select" name="country">
                        <option value="DO" @selected(old('country', $lottery->country) === 'DO')>República Dominicana</option>
                        <option value="US" @selected(old('country', $lottery->country) === 'US')>Estados Unidos</option>
                        <option value="PR" @selected(old('country', $lottery->country) === 'PR')>Puerto Rico</option>
                        <option value="VE" @selected(old('country', $lottery->country) === 'VE')>Venezuela</option>
                        <option value="CO" @selected(old('country', $lottery->country) === 'CO')>Colombia</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Estado *</label>
                    <select class="form-select" name="status" required>
                        @foreach (['ACTIVE', 'INACTIVE'] as $s)
                            <option value="{{ $s }}" @selected(old('status', $lottery->status) === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check-lg me-1"></i>Guardar
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.lotteries.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
