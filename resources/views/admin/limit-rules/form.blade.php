@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-4">
        <i class="bi bi-shield-exclamation me-2" style="color: var(--argon-danger);"></i>
        {{ $rule->exists ? 'Editar regla de límite' : 'Crear regla de límite' }}
    </h1>

    <form method="POST" action="{{ $rule->exists ? route('admin.limit-rules.update', $rule) : route('admin.limit-rules.store') }}" class="card"
          x-data="{ ruleType: '{{ old('rule_type', $rule->rule_type) }}', branchId: '{{ old('branch_id', $rule->branch_id) }}' }">
        @csrf
        @if ($rule->exists)
            @method('PUT')
        @endif

        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tipo de regla *</label>
                    <select class="form-select" name="rule_type" required x-model="ruleType">
                        <option value="GLOBAL">Global</option>
                        <option value="SINGLE_NUMBER">Número individual</option>
                        <option value="NUMBER_RANGE">Rango de números</option>
                        <option value="NUMBER_LIST">Lista exacta de números</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Máx. por número (RD$) *</label>
                    <input class="form-control" name="max_amount_per_number" type="number" step="0.01" min="0.01" required value="{{ old('max_amount_per_number', $rule->max_amount_per_number) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Máx. total (RD$)</label>
                    <input class="form-control" name="max_total_amount" type="number" step="0.01" min="0.01" value="{{ old('max_total_amount', $rule->max_total_amount) }}">
                </div>

                <div class="col-md-4 mb-3" x-show="ruleType === 'SINGLE_NUMBER'">
                    <label class="form-label">Número *</label>
                    <input class="form-control" name="number_value" maxlength="10" value="{{ old('number_value', $rule->number_value) }}" placeholder="Ej: 00">
                </div>
                <div class="col-md-4 mb-3" x-show="ruleType === 'NUMBER_RANGE'">
                    <label class="form-label">Desde *</label>
                    <input class="form-control" name="number_from" maxlength="10" value="{{ old('number_from', $rule->number_from) }}" placeholder="Ej: 00">
                </div>
                <div class="col-md-4 mb-3" x-show="ruleType === 'NUMBER_RANGE'">
                    <label class="form-label">Hasta *</label>
                    <input class="form-control" name="number_to" maxlength="10" value="{{ old('number_to', $rule->number_to) }}" placeholder="Ej: 50">
                </div>
                <div class="col-md-8 mb-3" x-show="ruleType === 'NUMBER_LIST'">
                    <label class="form-label">Lista de números (separados por coma) *</label>
                    <input class="form-control" name="numbers_input" value="{{ old('numbers_input', is_array($rule->numbers_json) ? implode(',', $rule->numbers_json) : '') }}" placeholder="Ej: 00,12,25,38,49">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Jugada</label>
                    <select class="form-select" name="bet_type_id">
                        <option value="">Todas</option>
                        @foreach ($betTypes as $bt)
                            <option value="{{ $bt->id }}" @selected((int) old('bet_type_id', $rule->bet_type_id) === $bt->id)>{{ $bt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Sucursal</label>
                    <select class="form-select" name="branch_id" x-model="branchId">
                        <option value="">Todas las sucursales</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string)(int) old('branch_id', $rule->branch_id) === (string)$branch->id)>{{ $branch->code }} — {{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text" x-show="!branchId">Sin sucursal: elige abajo cómo contar.</div>
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
                {{-- Scope: only relevant when branch_id is empty (applies to all branches) --}}
                <div class="col-md-4 mb-3" x-show="!branchId">
                    <label class="form-label fw-semibold">¿Cómo contar el límite? *</label>
                    <div class="d-flex gap-3 mt-1">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="scope" id="scopeBranch" value="PER_BRANCH"
                                   @checked(old('scope', $rule->scope ?? 'PER_BRANCH') === 'PER_BRANCH')>
                            <label class="form-check-label" for="scopeBranch">
                                <strong>Por sucursal</strong>
                                <div class="text-secondary small">Cada sucursal tiene su propio contador independiente</div>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="scope" id="scopeCompany" value="COMPANY"
                                   @checked(old('scope', $rule->scope ?? 'PER_BRANCH') === 'COMPANY')>
                            <label class="form-check-label" for="scopeCompany">
                                <strong>Empresa total</strong>
                                <div class="text-secondary small">El límite se comparte entre todas las sucursales</div>
                            </label>
                        </div>
                    </div>
                </div>
                {{-- When a specific branch is selected, force scope to PER_BRANCH --}}
                <input type="hidden" name="scope" value="PER_BRANCH" x-show="!!branchId" x-bind:disabled="!branchId">

                <div class="col-md-4 mb-3">
                    <label class="form-label">Política *</label>
                    <select class="form-select" name="policy" required>
                        <option value="BLOCK_FULL" @selected(old('policy', $rule->policy) === 'BLOCK_FULL')>Bloquear completamente</option>
                        <option value="ALLOW_AVAILABLE" @selected(old('policy', $rule->policy) === 'ALLOW_AVAILABLE')>Permitir disponible</option>
                        <option value="REQUEST_AUTHORIZATION" @selected(old('policy', $rule->policy) === 'REQUEST_AUTHORIZATION')>Solicitar autorización</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="status">
                        <option value="ACTIVE" @selected(old('status', $rule->status) === 'ACTIVE')>Activo</option>
                        <option value="INACTIVE" @selected(old('status', $rule->status) === 'INACTIVE')>Inactivo</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check-lg me-1"></i>Guardar
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.limit-rules.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
