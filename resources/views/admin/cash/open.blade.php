@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-4">
        <i class="bi bi-unlock me-2" style="color: var(--argon-success);"></i>Abrir caja
    </h1>

    <form method="POST" action="{{ route('admin.cash.open') }}" class="card">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Sucursal *</label>
                    <select class="form-select" name="branch_id" required>
                        <option value="">Seleccionar...</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) session('active_branch_id') === $branch->id)>{{ $branch->code }} — {{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Monto de apertura (RD$) *</label>
                    <input class="form-control" name="opening_amount" type="number" step="0.01" min="0" required value="{{ old('opening_amount', '0.00') }}">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Notas</label>
                    <textarea class="form-control" name="notes" rows="2" maxlength="500">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check-lg me-1"></i>Abrir caja
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.cash.current') }}">Cancelar</a>
        </div>
    </form>
@endsection
