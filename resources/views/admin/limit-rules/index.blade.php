@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-shield-exclamation me-2" style="color: var(--argon-danger);"></i>Reglas de límite</h1>
            <p class="text-secondary mb-0">Límites por número, rango, lista o globales. La sucursal puede tener límites diferentes.</p>
        </div>
        <div class="d-flex gap-2">
            @if (auth()->user()->hasPermission('limit_rules.create'))
                <a class="btn btn-primary" href="{{ route('admin.limit-rules.create') }}">
                    <i class="bi bi-plus-lg me-1"></i>Crear regla
                </a>
            @endif
            <a class="btn btn-outline-primary" href="{{ route('admin.limit-rules.consumptions') }}">
                <i class="bi bi-graph-up me-1"></i>Consumo
            </a>
        </div>
    </div>

    <form class="mb-4" method="GET">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Buscar por número, rango o sucursal">
            <button class="btn btn-outline-secondary" type="submit">Buscar</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Número / Rango</th>
                        <th>Sucursal</th>
                        <th>Jugada</th>
                        <th>Máx. por número</th>
                        <th>Política</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rules as $rule)
                        <tr>
                            <td>
                                @php
                                    $typeLabel = match($rule->rule_type) {
                                        'GLOBAL' => 'Global',
                                        'SINGLE_NUMBER' => 'Número',
                                        'NUMBER_RANGE' => 'Rango',
                                        'NUMBER_LIST' => 'Lista',
                                        default => $rule->rule_type,
                                    };
                                    $typeIcon = match($rule->rule_type) {
                                        'GLOBAL' => 'bi-globe2',
                                        'SINGLE_NUMBER' => 'bi-hash',
                                        'NUMBER_RANGE' => 'bi-arrow-left-right',
                                        'NUMBER_LIST' => 'bi-list-ol',
                                        default => 'bi-question',
                                    };
                                @endphp
                                <i class="bi {{ $typeIcon }} me-1 text-secondary"></i>{{ $typeLabel }}
                            </td>
                            <td>
                                @if ($rule->rule_type === 'SINGLE_NUMBER')
                                    <code>{{ $rule->number_value }}</code>
                                @elseif ($rule->rule_type === 'NUMBER_RANGE')
                                    <code>{{ $rule->number_from }}</code> → <code>{{ $rule->number_to }}</code>
                                @elseif ($rule->rule_type === 'NUMBER_LIST')
                                    {{ count($rule->numbers_json ?? []) }} números
                                @else
                                    Todos
                                @endif
                            </td>
                            <td>{{ $rule->branch?->name ?: 'Empresa' }}</td>
                            <td>{{ $rule->betType?->name ?: 'Todas' }}</td>
                            <td class="fw-semibold">RD$ {{ number_format($rule->max_amount_per_number, 2) }}</td>
                            <td>
                                @php
                                    $policyBadge = match($rule->policy) {
                                        'BLOCK_FULL' => 'bg-danger',
                                        'ALLOW_AVAILABLE' => 'bg-warning',
                                        'REQUEST_AUTHORIZATION' => 'bg-info',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $policyBadge }}">{{ $rule->policy }}</span>
                            </td>
                            <td>
                                <x-status-badge :status="$rule->status" />
                            </td>
                            <td class="text-end">
                                @if (auth()->user()->hasPermission('limit_rules.update'))
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.limit-rules.edit', $rule) }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-secondary py-4">No hay reglas de límite registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $rules->links() }}</div>
@endsection
