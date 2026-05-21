@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-bank me-2" style="color: var(--argon-info);"></i>Nueva transferencia</h1>
            <p class="text-secondary mb-0">Caja #{{ $session->id }} — {{ $session->branch?->name }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.cash.transfers.index') }}">Volver</a>
    </div>

    <form method="POST" action="{{ route('admin.cash.transfers.store') }}" class="card">
        @csrf
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tipo *</label>
                    <select class="form-select" name="movement_type" required>
                        @foreach ($movementTypes as $value => $definition)
                            <option value="{{ $value }}" @selected(old('movement_type', 'SALE') === $value)>{{ $definition['label'] }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Define si la transferencia entra o sale del balance operativo.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Banco *</label>
                    <input class="form-control" name="bank_name" maxlength="120" required value="{{ old('bank_name') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Referencia *</label>
                    <input class="form-control" name="reference" maxlength="120" required value="{{ old('reference') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Monto (RD$) *</label>
                    <input class="form-control" name="amount" type="number" step="0.01" min="0.01" required value="{{ old('amount') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha/hora transferencia</label>
                    <input class="form-control" name="transferred_at" type="datetime-local" value="{{ old('transferred_at') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Evidencia</label>
                    <input class="form-control" name="evidence_path" maxlength="255" value="{{ old('evidence_path') }}" placeholder="Ruta o codigo interno">
                </div>
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <textarea class="form-control" name="notes" rows="3" maxlength="500">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check-lg me-1"></i>Registrar pendiente
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.cash.transfers.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
