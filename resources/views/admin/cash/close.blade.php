@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-4">
        <i class="bi bi-lock me-2" style="color: var(--argon-danger);"></i>Cerrar caja
    </h1>

    @php $session->recalculateExpectedCash(); @endphp

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <p class="text-secondary small mb-1 text-uppercase">Efectivo esperado</p>
                    <h2 class="h3 mb-0 text-primary">RD$ {{ number_format($session->expected_cash, 2) }}</h2>
                    <p class="small text-secondary mt-2 mb-0">
                        Apertura: RD$ {{ number_format($session->opening_amount, 2) }}
                        | Ventas efectivo: +RD$ {{ number_format($session->sales_total, 2) }}
                        | Premios efectivo: -RD$ {{ number_format($session->prizes_paid_total, 2) }}
                        | Gastos: -RD$ {{ number_format($session->expenses_total, 2) }}
                        | Entradas: +RD$ {{ number_format($session->cash_in_total, 2) }}
                        | Salidas: -RD$ {{ number_format($session->cash_out_total, 2) }}
                        | Anulaciones: -RD$ {{ number_format($session->cancellations_total, 2) }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <p class="text-secondary small mb-1 text-uppercase">Formula de efectivo fisico</p>
                    <pre class="small bg-light p-2 rounded mb-0">esperado = apertura + ventas efectivo + entradas - anulaciones - premios efectivo - salidas - gastos</pre>
                    <p class="small text-secondary mt-2 mb-0">Las transferencias no forman parte del efectivo fisico esperado.</p>
                </div>
            </div>
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('admin.cash.close') }}"
        class="card"
        x-data="cashClose({{ (float) $session->expected_cash }}, @js($denominations))"
    >
        @csrf
        <div class="card-body">
            <div class="row g-4">
                <div class="col-lg-7">
                    <h2 class="h6 mb-3">Conteo por denominacion</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Denominacion</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($denominations as $key => $definition)
                                    <tr>
                                        <td>
                                            <span class="badge {{ $definition['type'] === 'BILL' ? 'bg-primary' : 'bg-secondary' }}">
                                                {{ $definition['type'] === 'BILL' ? 'Billete' : 'Moneda' }}
                                            </span>
                                            RD$ {{ number_format((float) $definition['value'], 2) }}
                                        </td>
                                        <td class="text-end" style="width: 150px;">
                                            <input
                                                class="form-control form-control-sm text-end"
                                                name="denominations[{{ $key }}]"
                                                type="number"
                                                min="0"
                                                max="100000"
                                                step="1"
                                                value="{{ old("denominations.$key", 0) }}"
                                                x-model.number="counts['{{ $key }}']"
                                            >
                                        </td>
                                        <td class="text-end fw-semibold" x-text="money(subtotal('{{ $key }}'))"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" name="counted_cash" :value="total.toFixed(2)">
                </div>

                <div class="col-lg-5">
                    <h2 class="h6 mb-3">Resultado del arqueo</h2>
                    <div class="border rounded p-3 mb-3" :class="differenceClass">
                        <div class="small text-secondary">Total contado fisico</div>
                        <div class="h4 mb-2" x-text="money(total)"></div>
                        <div class="small text-secondary">Diferencia contra esperado</div>
                        <div class="h5 mb-1" x-text="formattedDifference"></div>
                        <div class="small fw-semibold" x-text="message"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="notes">Notas</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4" maxlength="500">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-danger" type="submit" onclick="return confirm('¿Estas seguro de cerrar la caja?')">
                <i class="bi bi-lock-fill me-1"></i>Cerrar caja
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.cash.current') }}">Cancelar</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        function cashClose(expected, denominations) {
            const counts = {};

            Object.keys(denominations).forEach((key) => {
                counts[key] = Number(document.querySelector(`[name="denominations[${key}]"]`)?.value || 0);
            });

            return {
                expected,
                denominations,
                counts,
                subtotal(key) {
                    return Number(this.denominations[key].value) * Number(this.counts[key] || 0);
                },
                get total() {
                    return Object.keys(this.denominations).reduce((sum, key) => sum + this.subtotal(key), 0);
                },
                get difference() {
                    return Math.round((this.total - this.expected) * 100) / 100;
                },
                money(value) {
                    return `RD$ ${Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                },
                get formattedDifference() {
                    const prefix = this.difference >= 0 ? '+' : '-';
                    return `${prefix}${this.money(Math.abs(this.difference))}`;
                },
                get message() {
                    if (this.difference < 0) {
                        return 'Hay faltante de caja.';
                    }

                    if (this.difference > 0) {
                        return 'Hay sobrante de caja.';
                    }

                    return 'La caja cuadra exacta.';
                },
                get differenceClass() {
                    if (this.difference === 0) {
                        return 'bg-light';
                    }

                    return this.difference < 0 ? 'border-danger bg-danger-subtle' : 'border-warning bg-warning-subtle';
                },
            };
        }
    </script>
@endpush
