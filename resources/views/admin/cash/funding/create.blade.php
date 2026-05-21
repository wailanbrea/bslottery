@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-plus-circle me-2" style="color: var(--argon-success);"></i>Nuevo refuerzo de caja</h1>
            <p class="text-secondary mb-0">Registra efectivo enviado por administracion a una caja abierta.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.cash.funding.index') }}">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @if ($sessions->isEmpty())
        <div class="alert alert-warning">No hay cajas abiertas disponibles para reforzar.</div>
    @endif

    <form method="POST" action="{{ route('admin.cash.funding.store') }}" class="card">
        @csrf
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Caja abierta *</label>
                    <select class="form-select" name="cash_session_id" required>
                        <option value="">Seleccionar caja</option>
                        @foreach ($sessions as $session)
                            <option value="{{ $session->id }}" @selected((int) old('cash_session_id') === $session->id)>
                                #{{ $session->id }} · {{ $session->branch?->name }} · {{ $session->user?->name }} · Esperado RD$ {{ number_format((float) $session->expected_cash, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Monto (RD$) *</label>
                    <input class="form-control" type="number" name="amount" step="0.01" min="0.01" required value="{{ old('amount') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Origen</label>
                    <input class="form-control" name="source" maxlength="120" value="{{ old('source', 'Administracion') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Referencia</label>
                    <input class="form-control" name="reference" maxlength="120" value="{{ old('reference') }}" placeholder="Comprobante, mensajero, autorizacion">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Notas</label>
                    <textarea class="form-control" name="notes" rows="2" maxlength="500">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-primary" type="submit" @disabled($sessions->isEmpty())>
                <i class="bi bi-check-lg me-1"></i>Registrar refuerzo
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.cash.funding.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
