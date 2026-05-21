@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-4">
        <i class="bi bi-plus-slash-minus me-2" style="color: var(--argon-info);"></i>Registrar movimiento
    </h1>

    <form method="POST" action="{{ route('admin.cash.movement.store') }}" class="card" x-data="{ type: '{{ old('type', 'EXPENSE') }}' }">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tipo *</label>
                    <select class="form-select" name="type" required x-model="type">
                        <option value="EXPENSE">Gasto</option>
                        <option value="CASH_IN">Entrada de efectivo</option>
                        <option value="CASH_OUT">Salida de efectivo</option>
                        <option value="ADJUSTMENT">Ajuste</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Dirección *</label>
                    <select class="form-select" name="direction" required>
                        <option value="OUT">Salida (—)</option>
                        <option value="IN">Entrada (+)</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Monto (RD$) *</label>
                    <input class="form-control" name="amount" type="number" step="0.01" min="0.01" required value="{{ old('amount') }}">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Descripción *</label>
                    <textarea class="form-control" name="description" rows="2" maxlength="500" required>{{ old('description') }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check-lg me-1"></i>Registrar
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.cash.current') }}">Cancelar</a>
        </div>
    </form>
@endsection
