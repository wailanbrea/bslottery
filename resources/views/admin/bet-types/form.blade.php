@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-4">
        <i class="bi bi-dice-3 me-2" style="color: var(--argon-success);"></i>
        {{ $betType->exists ? 'Editar tipo de jugada' : 'Crear tipo de jugada' }}
    </h1>

    <form method="POST" action="{{ $betType->exists ? route('admin.bet-types.update', $betType) : route('admin.bet-types.store') }}" class="card">
        @csrf
        @if ($betType->exists)
            @method('PUT')
        @endif

        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Código *</label>
                    <input class="form-control" name="code" required maxlength="50" value="{{ old('code', $betType->code) }}" placeholder="Ej: QUINIELA">
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">Nombre *</label>
                    <input class="form-control" name="name" required maxlength="100" value="{{ old('name', $betType->name) }}" placeholder="Ej: Quiniela">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Cantidad de dígitos *</label>
                    <input class="form-control" name="digits_count" type="number" min="1" max="10" required value="{{ old('digits_count', $betType->digits_count) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Mín. números *</label>
                    <input class="form-control" name="min_numbers" type="number" min="1" max="100" required value="{{ old('min_numbers', $betType->min_numbers) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Máx. números *</label>
                    <input class="form-control" name="max_numbers" type="number" min="1" max="100" required value="{{ old('max_numbers', $betType->max_numbers) }}">
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <div class="form-check form-switch pb-2">
                        <input class="form-check-input" type="checkbox" name="requires_position" value="1" @checked(old('requires_position', $betType->requires_position))>
                        <label class="form-check-label">Requiere posición</label>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Números por jugada *</label>
                    <select class="form-select" name="numbers_count" required>
                        <option value="1" @selected(old('numbers_count', $betType->numbers_count) == 1)>1 (Quiniela)</option>
                        <option value="2" @selected(old('numbers_count', $betType->numbers_count) == 2)>2 (Pale, Super Pale)</option>
                        <option value="3" @selected(old('numbers_count', $betType->numbers_count) == 3)>3 (Tripleta)</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <div class="form-check form-switch pb-2">
                        <input class="form-check-input" type="checkbox" name="is_cross_lottery" value="1" @checked(old('is_cross_lottery', $betType->is_cross_lottery))>
                        <label class="form-check-label">Multi-lotería</label>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Monto máximo por jugada (RD$)</label>
                    <input class="form-control" name="max_amount" type="number" step="0.01" min="0.01"
                           value="{{ old('max_amount', $betType->max_amount) }}"
                           placeholder="Vacío = sin límite">
                    <div class="form-text">Monto máximo que se puede apostar en una sola jugada de este tipo.</div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check-lg me-1"></i>Guardar
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.bet-types.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
