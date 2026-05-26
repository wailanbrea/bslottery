@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-cash-stack me-2" style="color: var(--argon-success);"></i>Reglas de pago</h1>
            <p class="text-secondary mb-0">Multiplicadores configurables por empresa y sucursal.</p>
        </div>
        @if (auth()->user()->hasPermission('payout_rules.create'))
            <a class="btn btn-primary" href="{{ route('admin.payout-rules.create') }}">
                <i class="bi bi-plus-lg me-1"></i>Crear regla
            </a>
        @endif
    </div>

    <form class="mb-4" method="GET">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" name="search" value="{{ $search }}" placeholder="Buscar por jugada, lotería o sucursal">
            <button class="btn btn-outline-secondary" type="submit">Buscar</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Jugada</th>
                        <th>Lotería</th>
                        <th>Sucursal</th>
                        <th>Posición</th>
                        <th>Multiplicador</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody x-data="{ openRule: null }">
                    @forelse ($rules as $rule)
                        <tr>
                            <td class="fw-semibold">{{ $rule->betType?->name ?: '—' }}</td>
                            <td>{{ $rule->lottery?->name ?: 'Todas' }}</td>
                            <td>{{ $rule->branch?->name ?: 'Empresa' }}</td>
                            <td>{{ $rule->position ?: 'Cualquiera' }}</td>
                            <td><strong class="text-success">{{ $rule->payout_multiplier }}x</strong></td>
                            <td><x-status-badge :status="$rule->status" /></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button
                                        class="btn btn-sm btn-outline-secondary"
                                        type="button"
                                        @click="openRule = openRule === {{ $rule->id }} ? null : {{ $rule->id }}"
                                        x-bind:aria-expanded="openRule === {{ $rule->id }} ? 'true' : 'false'"
                                        aria-controls="payout-example-{{ $rule->id }}"
                                        title="Ver ejemplo">
                                        <i class="bi bi-question-circle"></i>
                                    </button>
                                    @if (auth()->user()->hasPermission('payout_rules.update'))
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.payout-rules.edit', $rule) }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                    @if (auth()->user()->hasPermission('payout_rules.approve') && $rule->status === 'DRAFT')
                                        <form method="POST" action="{{ route('admin.payout-rules.approve', $rule) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success" type="submit">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr x-cloak x-show="openRule === {{ $rule->id }}" id="payout-example-{{ $rule->id }}">
                            <td colspan="7" class="bg-light">
                                <div class="p-3 small">
                                    <div class="fw-semibold mb-2">Ejemplo de pago</div>
                                    <div>{{ $rule->example['trigger'] }}</div>
                                    <div class="text-secondary mt-1">{{ $rule->example['result'] }}</div>
                                    @if (!empty($rule->example['note']))
                                        <div class="text-warning mt-2">{{ $rule->example['note'] }}</div>
                                    @endif
                                    <div class="text-success fw-semibold mt-2">{{ $rule->example['payout'] }}</div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No hay reglas de pago registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $rules->links() }}</div>

    @if (auth()->user()->hasPermission('payout_rules.create'))
        <div class="card mt-4">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0">Copiar reglas entre sucursales</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.payout-rules.copy-branch') }}" class="row g-3">
                    @csrf
                    @php
                        $companyId = session('active_company_id');
                        $branchList = \App\Models\Branch::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();
                    @endphp
                    <div class="col-md-5">
                        <label class="form-label">Sucursal origen</label>
                        <select class="form-select" name="source_branch_id" required>
                            <option value="">Seleccionar...</option>
                            @foreach ($branchList as $b)
                                <option value="{{ $b->id }}">{{ $b->code }} — {{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Sucursal destino</label>
                        <select class="form-select" name="target_branch_id" required>
                            <option value="">Seleccionar...</option>
                            @foreach ($branchList as $b)
                                <option value="{{ $b->id }}">{{ $b->code }} — {{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-outline-primary w-100" type="submit">
                            <i class="bi bi-copy me-1"></i>Copiar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
