@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-book me-2" style="color: var(--argon-primary);"></i>Catálogo de cuentas</h1>
            <p class="text-secondary mb-0">Plan de cuentas contables de la empresa.</p>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td><code>{{ $account->code }}</code></td>
                            <td class="fw-semibold">{{ $account->name }}</td>
                            <td>
                                @php
                                    $typeBadge = match($account->type) {
                                        'ASSET' => 'bg-info',
                                        'LIABILITY' => 'bg-warning',
                                        'EQUITY' => 'bg-secondary',
                                        'INCOME' => 'bg-success',
                                        'EXPENSE' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $typeBadge }}">{{ $account->type }}</span>
                            </td>
                            <td>
                                <x-status-badge :status="$account->status" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-secondary py-4">No hay cuentas contables. Ejecuta el seeder inicial.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $accounts->links() }}</div>
@endsection
