@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-4">
        <i class="bi bi-cash-stack me-2" style="color: var(--argon-success);"></i>
        {{ $rule->exists ? 'Editar regla de pago' : 'Crear regla de pago' }}
    </h1>

    <form method="POST" action="{{ $rule->exists ? route('admin.payout-rules.update', $rule) : route('admin.payout-rules.store') }}" class="card">
        @csrf
        @if ($rule->exists)
            @method('PUT')
        @endif

        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Tipo de jugada *</label>
                    <select class="form-select" name="bet_type_id" required>
                        <option value="">Seleccionar...</option>
                        @foreach ($betTypes as $bt)
                            <option value="{{ $bt->id }}" @selected((int) old('bet_type_id', $rule->bet_type_id) === $bt->id)>{{ $bt->name }} ({{ $bt->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Multiplicador *</label>
                    <div class="input-group">
                        <input class="form-control" name="payout_multiplier" type="number" step="0.01" min="0.01" required value="{{ old('payout_multiplier', $rule->payout_multiplier) }}" placeholder="Ej: 80.00">
                        <span class="input-group-text">x</span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Lotería</label>
                    <select class="form-select" name="lottery_id">
                        <option value="">Todas</option>
                        @foreach ($lotteries as $lottery)
                            <option value="{{ $lottery->id }}" @selected((int) old('lottery_id', $rule->lottery_id) === $lottery->id)>{{ $lottery->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Sorteo</label>
                    <select class="form-select" name="draw_id">
                        <option value="">Ninguno</option>
                        @foreach ($draws as $draw)
                            <option value="{{ $draw->id }}" @selected((int) old('draw_id', $rule->draw_id) === $draw->id)>{{ $draw->name }} ({{ $draw->draw_date->format('Y-m-d') }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Sucursal (vacío = empresa)</label>
                    <select class="form-select" name="branch_id">
                        <option value="">Toda la empresa</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) old('branch_id', $rule->branch_id) === $branch->id)>{{ $branch->code }} — {{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Posición</label>
                    <select class="form-select" name="position">
                        <option value="">Cualquiera</option>
                        @foreach (['FIRST' => 'Primera', 'SECOND' => 'Segunda', 'THIRD' => 'Tercera', 'ANY' => 'Cualquiera', 'EXACT' => 'Exacta'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('position', $rule->position) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tipo de coincidencia</label>
                    <select class="form-select" name="match_type" required>
                        @foreach (['DIRECT' => 'Directa', 'COMBINATION' => 'Combinación', 'EXACT_ORDER' => 'Orden exacto', 'ANY_ORDER' => 'Cualquier orden'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('match_type', $rule->match_type) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="status">
                        @foreach (['DRAFT' => 'Borrador', 'ACTIVE' => 'Activo', 'INACTIVE' => 'Inactivo'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('status', $rule->status) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check-lg me-1"></i>Guardar
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.payout-rules.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
