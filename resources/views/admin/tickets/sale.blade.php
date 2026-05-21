@extends('layouts.app')

@section('content')
@php
$companyId = session('active_company_id');
$branchId = session('active_branch_id');
$company = \App\Models\Company::find($companyId);
$branch = $branchId ? \App\Models\Branch::find($branchId) : null;
$activeCashSession = $branchId
    ? \App\Models\CashSession::where('company_id', $companyId)
        ->where('branch_id', $branchId)
        ->where('user_id', auth()->id())
        ->where('status', 'OPEN')
        ->latest('opened_at')
        ->first()
    : null;
$todaySales = $branchId
    ? \App\Models\Ticket::where('company_id', $companyId)
        ->where('branch_id', $branchId)
        ->whereDate('sold_at', now()->toDateString())
        ->where('status', '!=', 'CANCELLED')
        ->sum('total_amount')
    : 0;
$now = now();
$openDraws = \App\Models\Draw::with('lottery')
    ->where('company_id', $companyId)
    ->where('status', 'OPEN')
    ->where('draw_date', '>=', now()->toDateString())
    ->orderBy('draw_date')
    ->orderBy('scheduled_time')
    ->get();
$sortDrawsByName = fn ($draws) => $draws
    ->sortBy([
        fn ($draw) => mb_strtolower($draw->lottery?->name ?? ''),
        fn ($draw) => $draw->scheduled_time ?? '',
        fn ($draw) => mb_strtolower($draw->name ?? ''),
    ])
    ->values();
$drawColumns = [
    'AM' => $sortDrawsByName($openDraws->filter(fn ($draw) => ($draw->close_time ?? $draw->scheduled_time) < '12:00')),
    'PM' => $sortDrawsByName($openDraws->filter(fn ($draw) => ($draw->close_time ?? $draw->scheduled_time) >= '12:00' && ($draw->close_time ?? $draw->scheduled_time) < '20:00')),
    'NOCHE' => $sortDrawsByName($openDraws->filter(fn ($draw) => ($draw->close_time ?? $draw->scheduled_time) >= '20:00')),
];
$initialSelectedDrawIds = $openDraws
    ->filter(fn ($draw) => $draw->draw_date->isToday()
        && (! $draw->open_time || $now->format('H:i') >= $draw->open_time)
        && (! $draw->close_time || $now->format('H:i') <= $draw->close_time))
    ->take(1)
    ->pluck('id')
    ->values();
$betTypes = \App\Models\BetType::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();
@endphp

@if (!$branchId)
    <div class="text-center py-5">
        <i class="bi bi-exclamation-triangle" style="font-size: 3rem; color: var(--argon-warning);"></i>
        <h2 class="mt-3">Sin sucursal activa</h2>
        <p class="text-secondary">Selecciona una sucursal para comenzar a vender.</p>
    </div>
@else
<div id="posApp" x-data="posTerminal()" @keydown.window="handleGlobalKey($event)" class="d-flex flex-column h-100" style="margin: -1.5rem; height: calc(100vh - 92px); max-height: calc(100vh - 92px); overflow: hidden;">
    <!-- OPERATIVE HEADER -->
    <div class="pos-operator-header border-bottom bg-white">
        <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2">
            <div class="min-w-0">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-dark">BSLottery</span>
                    <span class="text-secondary">/</span>
                    <span class="fw-semibold text-truncate">{{ $branch?->name ?? 'Sucursal' }}</span>
                </div>
                <div class="small text-secondary text-truncate">{{ $company?->name ?? 'Empresa' }}</div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <span class="pos-kpi"><span>Caja</span><strong>{{ $activeCashSession ? str_pad((string) $activeCashSession->id, 2, '0', STR_PAD_LEFT) : 'Sin caja' }}</strong></span>
                <span class="pos-kpi"><span>Cajero</span><strong>{{ auth()->user()?->name ?? 'Usuario' }}</strong></span>
                <span class="pos-kpi"><span>Fecha</span><strong x-text="currentDateLabel"></strong></span>
                <span class="pos-kpi"><span>Hora</span><strong x-text="currentTimeLabel"></strong></span>
                <span class="pos-kpi pos-kpi-money"><span>Ventas hoy</span><strong>RD$ {{ number_format((float) $todaySales, 2) }}</strong></span>
            </div>
        </div>
    </div>

    <!-- TOP: Multi-Draw Selector -->
    <div class="pos-lottery-board border-bottom">
        <div class="px-3 py-2 border-bottom bg-white d-flex justify-content-between align-items-center">
            <div>
                <div class="text-uppercase small fw-bold text-secondary">Loter&iacute;as</div>
                <div class="small text-secondary"><span x-text="visibleDrawsCount()"></span> visibles &middot; <span x-text="selectedDraws.length"></span> seleccionadas</div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <div class="btn-group btn-group-sm pos-filter-group" role="group" aria-label="Filtros de loterias">
                    <button type="button" class="btn" :class="drawFilter === 'ALL' ? 'btn-primary' : 'btn-outline-secondary'" @click="drawFilter = 'ALL'">Todas</button>
                    <button type="button" class="btn" :class="drawFilter === 'AM' ? 'btn-primary' : 'btn-outline-secondary'" @click="drawFilter = 'AM'">AM</button>
                    <button type="button" class="btn" :class="drawFilter === 'PM' ? 'btn-primary' : 'btn-outline-secondary'" @click="drawFilter = 'PM'">PM</button>
                    <button type="button" class="btn" :class="drawFilter === 'NOCHE' ? 'btn-primary' : 'btn-outline-secondary'" @click="drawFilter = 'NOCHE'">Noche</button>
                    <button type="button" class="btn" :class="drawFilter === 'OPEN' ? 'btn-primary' : 'btn-outline-secondary'" @click="drawFilter = 'OPEN'">Abiertas</button>
                    <button type="button" class="btn" :class="drawFilter === 'CLOSED' ? 'btn-primary' : 'btn-outline-secondary'" @click="drawFilter = 'CLOSED'">Cerradas</button>
                    <button type="button" class="btn" :class="drawFilter === 'SELECTED' ? 'btn-primary' : 'btn-outline-secondary'" @click="drawFilter = 'SELECTED'">Seleccionadas</button>
                </div>
                <button class="btn btn-sm btn-outline-primary py-0 px-2" @click="selectAll()" x-show="selectedDraws.length < saleableDrawsCount()">
                    <i class="bi bi-check-all"></i> Todas
                </button>
                <button class="btn btn-sm btn-outline-secondary py-0 px-2" @click="deselectAll()" x-show="selectedDraws.length > 0">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
        <div class="pos-lottery-sections">
            @forelse ($drawColumns as $sectionTitle => $sectionDraws)
                <section class="pos-lottery-section" style="--draw-count: {{ max(1, $sectionDraws->count()) }};" x-show="sectionHasVisibleDraws('{{ $sectionTitle }}')">
                    <div class="pos-section-title">{{ $sectionTitle }}</div>
                    @if ($sectionDraws->isNotEmpty())
                        <div class="pos-draw-row">
                            @foreach ($sectionDraws as $draw)
                                <label class="pos-draw-card lh-sm" :class="drawCardClass({{ $draw->id }})" x-show="drawMatchesFilter({{ $draw->id }})" style="cursor: pointer;">
                                    <div class="d-flex align-items-start gap-2 h-100">
                                        <input type="checkbox" class="form-check-input mt-1 flex-shrink-0"
                                            value="{{ $draw->id }}"
                                            @change="toggleDraw({{ $draw->id }}, $event.target.checked)"
                                            :checked="isSelected({{ $draw->id }})"
                                            :disabled="!isDrawSaleable({{ $draw->id }})">
                                        <div class="min-w-0 flex-grow-1">
                                            <div class="fw-bold text-dark pos-draw-title">{{ $draw->lottery->name }}</div>
                                            <div class="text-secondary pos-draw-subtitle">{{ $draw->name }} &middot; {{ $draw->close_time }}</div>
                                            <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                                <span class="badge" :class="drawCountdownClass({{ $draw->id }})" style="font-size: .58rem;" x-text="drawStatusLabel({{ $draw->id }})"></span>
                                                <span class="text-secondary pos-draw-time" x-text="drawCountdown({{ $draw->id }})"></span>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="pos-empty-section">Sin sorteos</div>
                    @endif
                </section>
            @empty
                <div class="text-center text-secondary py-4 small">No hay sorteos abiertos</div>
            @endforelse
        </div>
    </div>

    <!-- CENTER: Input + Ticket Table -->
    <div class="pos-main-area flex-grow-1 d-flex flex-column overflow-hidden">
        <!-- Smart Input Area -->
        <div class="pos-input-panel mx-3 mt-2 rounded-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="pos-bet-type-strip d-flex gap-1 flex-wrap">
                        <template x-for="bt in betTypes" :key="bt.id">
                            <button type="button" class="btn btn-sm border-white text-white"
                                :class="selectedBetType == bt.id ? 'bg-white bg-opacity-25' : 'bg-white bg-opacity-10'"
                                @click="selectedBetType = bt.id"
                                x-text="bt.code"></button>
                        </template>
                    </div>
                    <div class="position-relative flex-grow-1">
                    <input type="text" id="smartInput"
                           x-ref="smartInput"
                           x-model="inputValue"
                           :disabled="preCheckLoading"
                           @keydown.enter.prevent="addPlay()"
                           @keydown.escape.prevent="clearInput()"
                           class="form-control form-control-lg border-0 shadow-sm"
                           style="font-size: 1.45rem; font-weight: 800; font-family: monospace; padding: .38rem .75rem; border-radius: .65rem;"
                           autofocus>
                    <span class="position-absolute top-50 end-0 translate-middle-y me-2 text-secondary"
                          x-show="preCheckLoading" x-transition.opacity>
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </span>
                    </div>
                    <div class="d-flex align-items-center gap-1 flex-wrap justify-content-end pos-amount-strip">
                        <span class="small text-white-50 me-1" x-show="!lastAmount">Sin monto</span>
                        <span class="small text-white fw-bold me-1" x-show="lastAmount" x-text="'$' + lastAmount"></span>
                        <button type="button" class="btn btn-sm text-white fw-bold"
                            :class="!lastAmount ? 'bg-white bg-opacity-25' : 'bg-white bg-opacity-10'"
                            @click="clearAmount()">Sin monto</button>
                        <template x-for="amt in quickAmounts" :key="amt">
                            <button type="button" class="btn btn-sm text-white fw-bold"
                                :class="lastAmount == amt ? 'bg-white bg-opacity-25' : 'bg-white bg-opacity-10'"
                                @click="setAmount(amt)" x-text="'$'+amt"></button>
                        </template>
                    </div>
                </div>
                    <div class="mt-2 p-2 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-25"
                         x-show="amountPromptVisible"
                         x-transition>
                        <div class="small text-white fw-bold mb-2">
                            Monto para <span class="font-monospace" x-text="pendingPlayValue"></span>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text">RD$</span>
                            <input type="number"
                                   min="0.01"
                                   step="0.01"
                                   inputmode="decimal"
                                   class="form-control"
                                   x-ref="amountInput"
                                   x-model="pendingAmount"
                                   @keydown.enter.prevent="confirmPendingAmount()"
                                   @keydown.escape.prevent="cancelPendingAmount()">
                            <button class="btn btn-light fw-bold" type="button" @click="confirmPendingAmount()">Agregar</button>
                            <button class="btn btn-outline-light" type="button" @click="cancelPendingAmount()">Cancelar</button>
                        </div>
                        <div class="small mt-2 text-white-50">Enter agrega la jugada con este monto. Esc cancela.</div>
                    </div>
            </div>

            <!-- Current Ticket Table -->
            <div class="flex-grow-1 d-flex overflow-hidden px-3 pb-2 gap-2">
                <div class="flex-grow-1 d-flex flex-column overflow-hidden">
                <div class="bg-white rounded-3 border shadow-sm d-flex flex-column flex-grow-1 overflow-hidden">
                    <div class="p-2 border-bottom d-flex justify-content-between align-items-center bg-light bg-opacity-50">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-receipt text-secondary"></i>
                            <span class="fw-bold">Ticket actual</span>
                            <span class="badge bg-primary" x-text="plays.length + ' JUGADAS'"></span>
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm" :class="voltearActivo ? 'btn-info text-white' : 'btn-outline-info'"
                                    @click="toggleVoltear()" x-show="plays.length > 0"
                                    title="Genera la version invertida de cada jugada (ej. 12-34 -> 21-43)">
                                <i class="bi bi-arrow-left-right me-1"></i><span x-text="voltearActivo ? 'Volteado' : 'Voltear'"></span>
                            </button>
                            <button class="btn btn-sm btn-outline-primary"
                                    @click="openCombinarModal()" x-show="plays.length > 0"
                                    title="Genera pales/tripletas/super-pales automaticamente desde las jugadas actuales">
                                <i class="bi bi-shuffle me-1"></i>Combinar
                            </button>
                            <button class="btn btn-sm btn-outline-danger" @click="clearAll()" x-show="plays.length > 0">
                                <i class="bi bi-trash me-1"></i>Limpiar
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="removeLastPlay()" x-show="plays.length > 0">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Deshacer
                            </button>
                        </div>
                    </div>
                    <div class="flex-grow-1 overflow-auto">
                        <table class="table table-sm align-middle mb-0" x-show="plays.length > 0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-3 small text-uppercase">#</th>
                                    <th class="small text-uppercase">Loter&iacute;a</th>
                                    <th class="small text-uppercase">Sorteo</th>
                                    <th class="small text-uppercase">Tipo</th>
                                    <th class="small text-uppercase">Jugada</th>
                                    <th class="small text-uppercase text-end">Monto</th>
                                    <th class="small text-uppercase text-end">Premio posible</th>
                                    <th class="text-end pe-3" style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(play, idx) in plays" :key="play.draw_id + '-' + play.bet_type_id + '-' + play.number_value + '-' + idx">
                                    <tr class="border-bottom">
                                        <td class="ps-3 text-secondary small" x-text="idx + 1"></td>
                                        <td class="small fw-semibold text-dark" x-text="play.lotteryName || '-'"></td>
                                        <td class="small text-secondary" x-text="play.drawName || '-'"></td>
                                        <td>
                                            <span class="badge text-uppercase fw-bold"
                                                :class="play.betTypeCode === 'QUINIELA' ? 'bg-info' : (play.betTypeCode === 'PALE' || play.betTypeCode === 'SUPER_PALE' ? 'bg-warning' : 'bg-danger')"
                                                x-text="betShortCode(play.betTypeCode)"></span>
                                            <template x-if="play._flipped"><span class="badge bg-info bg-opacity-25 text-info ms-1" title="Jugada volteada"><i class="bi bi-arrow-left-right"></i></span></template>
                                            <template x-if="play._combined"><span class="badge bg-primary bg-opacity-25 text-primary ms-1" title="Generada por Combinar"><i class="bi bi-shuffle"></i></span></template>
                                            <template x-if="play._limitAdjusted"><span class="badge bg-warning bg-opacity-25 text-warning ms-1" title="Monto ajustado al disponible"><i class="bi bi-arrows-collapse-vertical"></i></span></template>
                                        </td>
                                        <td class="fw-bold font-monospace fs-5" x-text="play.number_value"></td>
                                        <td class="text-end fw-bold text-success">
                                            <span x-text="'$' + play.amount.toFixed(2)"></span>
                                            <template x-if="play.limitStatus === 'checking'">
                                                <span class="ms-1 spinner-border spinner-border-sm text-secondary" style="width:.7rem;height:.7rem"></span>
                                            </template>
                                            <template x-if="play.limitStatus === 'error'">
                                                <span class="badge bg-danger ms-1" x-text="play.limitMessage || 'Límite excedido'"></span>
                                            </template>
                                            <template x-if="play.limitStatus === 'warning'">
                                                <span class="badge bg-warning text-dark ms-1" x-text="play.limitMessage"></span>
                                            </template>
                                        </td>
                                        <td class="text-end small text-secondary" x-text="play.estimatedPrize === null ? '-' : '$' + play.estimatedPrize.toFixed(2)"></td>
                                        <td class="text-end pe-3">
                                            <button class="btn btn-sm text-secondary hover-text-danger" @click="removePlay(idx)">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <div x-show="plays.length === 0" class="pos-empty-ticket d-flex align-items-center justify-content-center flex-grow-1 text-secondary">
                            <div class="text-center">
                                <i class="bi bi-keyboard" style="font-size: 2rem; opacity: .28;"></i>
                                <p class="mt-2 mb-0 fw-semibold">Tipea los d&iacute;gitos y presiona <kbd class="bg-light border px-1 rounded">Enter</kbd></p>
                                <p class="small text-secondary mt-1">2 d&iacute;gitos = Q &nbsp;|&nbsp; 4 d&iacute;gitos = P &nbsp;|&nbsp; 6 d&iacute;gitos = T</p>
                                <p class="small text-secondary">Ej: <code>2064 100</code> = Pale 20-64 por $100</p>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

        <!-- RIGHT: Summary & Pay -->
        <div class="border d-flex flex-column pos-summary-panel">
            <div class="p-2 bg-white border-bottom">
                <div class="small text-uppercase fw-bold text-secondary">Resumen</div>
            </div>
            <div class="flex-grow-1 p-2 d-flex flex-column">
                <div class="pos-total-box mb-2">
                    <div class="small text-uppercase text-white-50 fw-bold">Total</div>
                    <div class="pos-total-amount" x-text="'RD$ ' + totalAmount.toFixed(2)"></div>
                </div>
                <div class="pos-summary-grid mb-2">
                    <div>
                        <span>Jugadas</span>
                        <strong x-text="plays.length"></strong>
                    </div>
                    <div>
                        <span>Loter&iacute;as</span>
                        <strong x-text="drawsWithPlays().length"></strong>
                    </div>
                    <div>
                        <span>Subtotal</span>
                        <strong x-text="'RD$ ' + totalAmount.toFixed(2)"></strong>
                    </div>
                    <div>
                        <span>Premio posible</span>
                        <strong x-text="totalEstimatedPrize === null ? '-' : 'RD$ ' + totalEstimatedPrize.toFixed(2)"></strong>
                    </div>
                </div>

                <template x-if="plays.some(p => p.limitStatus === 'error')">
                    <div class="alert alert-danger py-2 px-3 small mb-2">
                        <i class="bi bi-exclamation-octagon me-1"></i>
                        <strong>Jugadas bloqueadas por límite.</strong>
                        Elimínalas o reduce el monto para continuar.
                    </div>
                </template>

                <button type="button" class="btn btn-success w-100 py-2 fw-bold fs-6 d-flex align-items-center justify-content-center gap-2 mt-auto"
                    :disabled="!canSell"
                    @click="submitSale()">
                    <i class="bi bi-cart-check fs-5"></i>
                    <span x-show="!isSelling">COBRAR E IMPRIMIR</span>
                    <span x-show="isSelling">PROCESANDO...</span>
                </button>
                <div class="text-center mt-1">
                    <small class="text-secondary"><kbd class="bg-light border px-1 rounded">F9</kbd></small>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-outline-secondary btn-sm flex-grow-1" onclick="window.location.href='{{ route('admin.tickets.index') }}'">
                        <i class="bi bi-clock-history me-1"></i>Historial
                    </button>
                    <button class="btn btn-outline-info btn-sm flex-grow-1" @click="showPreview()">
                        <i class="bi bi-eye me-1"></i>Preview
                    </button>
                </div>

                <div class="accordion accordion-flush pos-ticket-lookup mt-2" id="ticketLookupAccordion">
                    <div class="accordion-item border rounded-3 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button py-2 px-2 small fw-bold" :class="lookupExpanded ? '' : 'collapsed'" type="button" @click="lookupExpanded = !lookupExpanded" :aria-expanded="lookupExpanded.toString()" aria-controls="ticketLookupPanel">
                                <i class="bi bi-qr-code-scan text-primary me-2"></i> Buscar ticket / QR
                            </button>
                        </h2>
                        <div id="ticketLookupPanel" x-show="lookupExpanded" x-transition x-cloak>
                            <div class="accordion-body p-2">
                                <div class="small text-secondary mb-2">Buscar, copiar o pagar</div>
                                <div class="input-group input-group-sm">
                                    <input type="text" x-ref="ticketLookup" x-model="ticketLookupToken"
                                           @keydown.enter.prevent="lookupTicket()" class="form-control"
                                           placeholder="Numero o QR">
                                    <button class="btn btn-outline-secondary" type="button" @click="scanQr()" title="Escanear QR">
                                        <i class="bi bi-upc-scan"></i>
                                    </button>
                                    <button class="btn btn-primary" type="button" @click="lookupTicket()" :disabled="lookupLoading">
                                        <span x-show="!lookupLoading">Buscar</span>
                                        <span x-show="lookupLoading">...</span>
                                    </button>
                                </div>
                                <div class="small mt-2" :class="lookupError ? 'text-danger' : 'text-secondary'" x-text="lookupMessage"></div>

                                <template x-if="lookupResult">
                                    <div class="mt-2 border rounded-3 p-2 bg-light">
                                        <div class="d-flex justify-content-between gap-2">
                                            <div class="min-w-0">
                                                <div class="fw-bold font-monospace small" x-text="lookupResult.ticket.ticket_number"></div>
                                                <div class="small text-secondary" x-text="lookupResult.ticket.status + ' - RD$ ' + money(lookupResult.ticket.total_amount)"></div>
                                            </div>
                                            <a class="btn btn-sm btn-outline-primary" :href="lookupResult.ticket.show_url">Ver</a>
                                        </div>
                                        <button class="btn btn-sm btn-outline-success w-100 mt-2" type="button"
                                                @click="copyLookupPlays()"
                                                :disabled="!lookupResult.copy_plays.length">
                                            <i class="bi bi-copy me-1"></i>Copiar jugadas
                                        </button>
                                        <button class="btn btn-sm btn-success w-100 mt-2" type="button"
                                                x-show="lookupResult.ticket.can_pay_released_prizes"
                                                @click="payTicket(lookupResult.ticket)">
                                            <i class="bi bi-cash-coin me-1"></i>
                                            Pagar ticket completo
                                            <span x-text="'RD$ ' + money(lookupResult.ticket.released_prize_total)"></span>
                                        </button>

                                        <template x-if="lookupResult.winners.length">
                                            <div class="mt-2">
                                                <div class="small text-uppercase fw-bold text-secondary mb-1">Premios</div>
                                                <template x-for="winner in lookupResult.winners" :key="winner.id">
                                                    <div class="d-flex justify-content-between align-items-center gap-2 border-top py-2">
                                                        <div class="small">
                                                            <div><code x-text="winner.number_value"></code> <span x-text="winner.status"></span></div>
                                                            <div class="text-success fw-bold" x-text="'RD$ ' + money(winner.prize_amount)"></div>
                                                        </div>
                                                        <button class="btn btn-sm btn-success" type="button"
                                                                x-show="winner.can_pay"
                                                                @click="payWinner(winner)">
                                                            Pagar
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-2 border-top bg-white">
                <div class="d-flex flex-wrap gap-2">
                    <span class="small text-secondary"><kbd class="bg-light border px-1 rounded small">Ctrl+L</kbd> Jugadas</span>
                    <span class="small text-secondary"><kbd class="bg-light border px-1 rounded small">Ctrl+T</kbd> Todo</span>
                    <span class="small text-secondary"><kbd class="bg-light border px-1 rounded small">F9</kbd> Cobrar</span>
                    <span class="small text-secondary"><kbd class="bg-light border px-1 rounded small">ESC</kbd> Cancelar</span>
                </div>
            </div>
        </div>
            </div>
    </div>

    <!-- LIMIT CONFLICT MODAL -->
    <div class="pos-combinar-overlay" x-show="limitDialog.visible" x-transition.opacity x-cloak
         @keydown.escape.window="cancelLimitDialog()"
         @keydown.enter.window.prevent="applyLimitDecisions()">
        <div class="pos-combinar-modal" @click.outside="cancelLimitDialog()">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                <div>
                    <h6 class="mb-0 fw-bold text-warning">
                        <i class="bi bi-exclamation-triangle me-1"></i>Limite alcanzado
                    </h6>
                    <small class="text-secondary">
                        Numero <strong x-text="limitDialog.numberValue"></strong>
                        por <strong x-text="'RD$ ' + Number(limitDialog.requestedAmount || 0).toFixed(2)"></strong>
                        no cabe en algunas loterias.
                    </small>
                </div>
                <button type="button" class="btn-close" @click="cancelLimitDialog()" aria-label="Cerrar"></button>
            </div>
            <div class="p-3">
                <div class="d-flex justify-content-end mb-2">
                    <button type="button" class="btn btn-sm btn-outline-warning" @click="applyAllAdjust()">
                        <i class="bi bi-arrows-collapse-vertical me-1"></i>Ajustar todo al disponible
                    </button>
                </div>
                <div class="d-flex flex-column gap-2">
                    <template x-for="(c, idx) in limitDialog.conflicts" :key="c.drawId">
                        <div class="border rounded p-2" :class="{
                            'border-success bg-light': c.status === 'ok',
                            'border-warning bg-light': c.status === 'partial',
                            'border-danger bg-light': c.status === 'agotado',
                        }">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-semibold text-truncate" x-text="c.drawLabel"></div>
                                    <div class="small text-secondary">
                                        <template x-if="c.status === 'ok'">
                                            <span><i class="bi bi-check-circle text-success"></i> Disponible: RD$ <span x-text="Number(c.effective).toFixed(2)"></span></span>
                                        </template>
                                        <template x-if="c.status === 'partial'">
                                            <span>
                                                Disponible: <strong class="text-warning">RD$ <span x-text="Number(c.effective).toFixed(2)"></span></strong>
                                                / Solicitado: RD$ <span x-text="Number(c.requested).toFixed(2)"></span>
                                                <template x-if="c.pendingInCart > 0">
                                                    <span class="ms-1">(ya en cart: RD$ <span x-text="Number(c.pendingInCart).toFixed(2)"></span>)</span>
                                                </template>
                                            </span>
                                        </template>
                                        <template x-if="c.status === 'agotado'">
                                            <span class="text-danger"><i class="bi bi-x-circle"></i> Numero agotado en esta loteria</span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-2" x-show="c.status !== 'ok'">
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <template x-if="c.status === 'partial'">
                                        <input type="radio" class="btn-check"
                                               :id="'limitDec-' + idx + '-adjust'"
                                               :name="'limitDec-' + idx"
                                               value="adjust"
                                               x-model="limitDialog.decisions[idx].action">
                                    </template>
                                    <template x-if="c.status === 'partial'">
                                        <label class="btn btn-outline-warning" :for="'limitDec-' + idx + '-adjust'">
                                            Jugar RD$ <span x-text="Number(c.effective).toFixed(2)"></span>
                                        </label>
                                    </template>

                                    <input type="radio" class="btn-check"
                                           :id="'limitDec-' + idx + '-omit'"
                                           :name="'limitDec-' + idx"
                                           value="omit"
                                           x-model="limitDialog.decisions[idx].action">
                                    <label class="btn btn-outline-secondary" :for="'limitDec-' + idx + '-omit'">
                                        Omitir
                                    </label>
                                </div>
                            </div>
                            <div class="mt-2 small text-success" x-show="c.status === 'ok'">
                                <i class="bi bi-check2"></i> Se anadira con RD$ <span x-text="Number(c.requested).toFixed(2)"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
                <small class="text-secondary"><kbd class="bg-white border px-1 rounded small">Enter</kbd> aplicar &middot; <kbd class="bg-white border px-1 rounded small">Esc</kbd> cancelar</small>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" @click="cancelLimitDialog()">Cancelar todo</button>
                    <button class="btn btn-sm btn-success" @click="applyLimitDecisions()">
                        <i class="bi bi-check-lg me-1"></i>Aplicar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- COMBINAR MODAL -->
    <div class="pos-combinar-overlay" x-show="combinarModalVisible" x-transition.opacity x-cloak
         @keydown.escape.window="combinarModalVisible = false">
        <div class="pos-combinar-modal" @click.outside="combinarModalVisible = false">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                <div>
                    <h6 class="mb-0 fw-bold">Combinar jugadas</h6>
                    <small class="text-secondary">Genera pales, tripletas y super-pales desde tus jugadas actuales.</small>
                </div>
                <button type="button" class="btn-close" @click="combinarModalVisible = false" aria-label="Cerrar"></button>
            </div>
            <div class="p-3">
                <div class="small text-secondary mb-2">
                    Fuente: <strong x-text="combinarSourceSummary()"></strong>
                </div>

                <div class="alert alert-info py-2 px-3 small mb-3" x-show="combinarPreview().total === 0">
                    <i class="bi bi-info-circle me-1"></i>
                    Selecciona al menos una opcion. Se necesitan 2+ numeros por loteria para Pale, 3+ para Tripleta, y 2 loterias con numeros para Super.
                </div>

                <div class="d-flex flex-column gap-2">
                    <div class="border rounded p-2" :class="combinarOpts.pale ? 'border-primary bg-light' : ''">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="combPale" x-model="combinarOpts.pale">
                            <label class="form-check-label fw-semibold" for="combPale">
                                Pale <small class="text-secondary">(2 numeros, misma loteria)</small>
                            </label>
                            <span class="badge bg-primary float-end" x-show="combinarOpts.pale" x-text="combinarPreview().pale + ' jugadas'"></span>
                        </div>
                        <div class="input-group input-group-sm mt-2" x-show="combinarOpts.pale">
                            <span class="input-group-text">Monto</span>
                            <input type="number" min="0" step="0.01" class="form-control" x-model="combinarMontos.pale" placeholder="Usa monto global">
                        </div>
                    </div>

                    <div class="border rounded p-2" :class="combinarOpts.tripleta ? 'border-primary bg-light' : ''">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="combTri" x-model="combinarOpts.tripleta">
                            <label class="form-check-label fw-semibold" for="combTri">
                                Tripleta <small class="text-secondary">(3 numeros, misma loteria)</small>
                            </label>
                            <span class="badge bg-primary float-end" x-show="combinarOpts.tripleta" x-text="combinarPreview().tripleta + ' jugadas'"></span>
                        </div>
                        <div class="input-group input-group-sm mt-2" x-show="combinarOpts.tripleta">
                            <span class="input-group-text">Monto</span>
                            <input type="number" min="0" step="0.01" class="form-control" x-model="combinarMontos.tripleta" placeholder="Usa monto global">
                        </div>
                    </div>

                    <div class="border rounded p-2" :class="combinarOpts.super ? 'border-primary bg-light' : ''">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="combSuper" x-model="combinarOpts.super" :disabled="!canCombinarSuper()">
                            <label class="form-check-label fw-semibold" for="combSuper">
                                Super Pale <small class="text-secondary">(2 numeros, 2 loterias)</small>
                            </label>
                            <span class="badge bg-primary float-end" x-show="combinarOpts.super" x-text="combinarPreview().super + ' jugadas'"></span>
                        </div>
                        <div class="small text-warning mt-1" x-show="!canCombinarSuper()">
                            <i class="bi bi-exclamation-triangle"></i> Requiere 2+ loterias con numeros.
                        </div>
                        <div class="input-group input-group-sm mt-2" x-show="combinarOpts.super && canCombinarSuper()">
                            <span class="input-group-text">Monto</span>
                            <input type="number" min="0" step="0.01" class="form-control" x-model="combinarMontos.super" placeholder="Usa monto global">
                        </div>
                    </div>

                    <div class="border rounded p-2" :class="combinarOpts.quiniela ? 'border-primary bg-light' : ''">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="combQ" x-model="combinarOpts.quiniela">
                            <label class="form-check-label fw-semibold" for="combQ">
                                Quiniela <small class="text-secondary">(extrae cada numero de los pales/tripletas)</small>
                            </label>
                            <span class="badge bg-primary float-end" x-show="combinarOpts.quiniela" x-text="combinarPreview().quiniela + ' jugadas'"></span>
                        </div>
                        <div class="input-group input-group-sm mt-2" x-show="combinarOpts.quiniela">
                            <span class="input-group-text">Monto</span>
                            <input type="number" min="0" step="0.01" class="form-control" x-model="combinarMontos.quiniela" placeholder="Usa monto global">
                        </div>
                    </div>

                    <div class="border rounded p-2 bg-light">
                        <label class="form-label small fw-bold mb-1">Monto global (fallback)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">RD$</span>
                            <input type="number" min="0" step="0.01" class="form-control" x-model="combinarMontos.global">
                        </div>
                        <small class="text-secondary">Se usa cuando un tipo no tiene monto propio. Por defecto = promedio de jugadas actuales.</small>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 px-3 py-2 border-top bg-light">
                <button class="btn btn-sm btn-outline-secondary" @click="combinarModalVisible = false">Cancelar</button>
                <button class="btn btn-sm btn-success" @click="submitCombinar()"
                        :disabled="combinarPreview().total === 0">
                    <i class="bi bi-check-lg me-1"></i>Agregar <span x-text="combinarPreview().total"></span> jugadas
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<script>
function posTerminal() {
    return {
        draws: @json($openDraws),
        betTypes: @json($betTypes),
        selectedDraws: @json($initialSelectedDrawIds),
        selectedBetType: {{ $betTypes->first()?->id ?? 0 }},
        plays: [],
        inputValue: '',
        lastAmount: null,
        amountPromptVisible: false,
        pendingPlayValue: '',
        pendingAmount: '',
        quickAmounts: [5, 10, 20, 25, 50, 100, 200, 500, 1000, 2000],
        branchId: {{ $branchId ?? 0 }},
        companyName: @json($company?->name ?? 'BSLottery'),
        branchName: @json($branch?->name ?? 'Banca'),
        csrfToken: '{{ csrf_token() }}',
        storeUrl: '{{ route('admin.tickets.store') }}',
        lookupUrl: '{{ route('admin.tickets.lookup') }}',
        checkLimitUrl: '{{ route('admin.tickets.check-limit') }}',
        ticketLookupToken: '',
        lookupResult: null,
        lookupExpanded: false,
        lookupLoading: false,
        lookupMessage: 'Escanea o escribe un ticket para copiarlo o pagarlo.',
        lookupError: false,
        isSelling: false,
        currentTime: new Date(),
        clockTimer: null,
        drawFilter: 'OPEN',

        // Voltear / Combinar
        voltearActivo: false,
        combinarModalVisible: false,
        combinarOpts: { pale: false, tripleta: false, super: false, quiniela: false },
        combinarMontos: { pale: '', tripleta: '', super: '', quiniela: '', global: '' },

        // Pre-check de limites
        preCheckLoading: false,
        limitDialog: {
            visible: false,
            numberValue: '',
            betType: null,
            requestedAmount: 0,
            conflicts: [],
            decisions: [],
        },

        get canSell() {
            return this.plays.length > 0 && this.drawsWithPlays().length > 0 && !this.isSelling
                && this.drawsWithPlays().every(drawId => this.isDrawSaleable(drawId))
                && !this.plays.some(p => p.limitStatus === 'error');
        },

        get totalAmount() {
            return this.plays.reduce((sum, p) => sum + p.amount, 0);
        },

        get totalEstimatedPrize() {
            const estimated = this.plays
                .map(play => play.estimatedPrize)
                .filter(value => value !== null && value !== undefined);

            if (estimated.length === 0) return null;

            return estimated.reduce((sum, value) => sum + Number(value || 0), 0);
        },

        get currentDateLabel() {
            return this.currentTime.toLocaleDateString('es-DO', {
                weekday: 'short',
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        },

        get currentTimeLabel() {
            return this.currentTime.toLocaleTimeString('es-DO', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
        },

        isSelected(id) {
            return this.selectedDraws.includes(id);
        },

        saleableDrawsCount() {
            return this.draws.filter(draw => this.isDrawSaleable(draw.id)).length;
        },

        visibleDrawsCount() {
            return this.draws.filter(draw => this.drawMatchesFilter(draw.id)).length;
        },

        parseDrawDate(draw) {
            if (!draw?.draw_date) return new Date();
            const raw = String(draw.draw_date);
            const datePart = raw.slice(0, 10);
            const [year, month, day] = datePart.split('-').map(Number);

            if (year && month && day) {
                return new Date(year, month - 1, day, 0, 0, 0);
            }

            return new Date();
        },

        drawDateTime(draw, timeValue) {
            const base = this.parseDrawDate(draw);
            const [hours, minutes] = String(timeValue || '00:00').slice(0, 5).split(':').map(Number);

            return new Date(base.getFullYear(), base.getMonth(), base.getDate(), hours || 0, minutes || 0, 0);
        },

        drawTiming(draw) {
            const openAt = this.drawDateTime(draw, draw.open_time || '00:00');
            const closeAt = this.drawDateTime(draw, draw.close_time || draw.scheduled_time || '23:59');

            if (this.currentTime < openAt) return { state: 'WAITING', seconds: Math.floor((openAt - this.currentTime) / 1000) };
            if (this.currentTime > closeAt) return { state: 'CLOSED', seconds: 0 };

            return { state: 'OPEN', seconds: Math.floor((closeAt - this.currentTime) / 1000) };
        },

        isDrawSaleable(id) {
            const draw = this.getDrawById(id);
            if (!draw || draw.status !== 'OPEN') return false;

            return this.drawTiming(draw).state === 'OPEN';
        },

        drawCountdown(id) {
            const draw = this.getDrawById(id);
            if (!draw) return '-';

            const timing = this.drawTiming(draw);
            if (timing.state === 'CLOSED') return 'Cerrada';
            if (timing.state === 'WAITING') return 'Abre en ' + this.formatDuration(timing.seconds);

            return this.formatDuration(timing.seconds);
        },

        drawStatusLabel(id) {
            const draw = this.getDrawById(id);
            if (!draw) return '-';

            const timing = this.drawTiming(draw);
            if (timing.state === 'CLOSED') return 'Cerrada';
            if (timing.state === 'WAITING') return 'Espera';
            if (timing.seconds <= 900) return 'Cerrando';

            return 'Abierta';
        },

        drawCountdownClass(id) {
            const draw = this.getDrawById(id);
            if (!draw) return '-';

            const timing = this.drawTiming(draw);
            if (timing.state === 'CLOSED') return 'bg-danger';
            if (timing.state === 'WAITING') return 'bg-primary';
            if (timing.seconds <= 900) return 'bg-danger';
            if (timing.seconds <= 3600) return 'bg-success';

            return 'bg-primary';
        },

        drawCardClass(id) {
            if (!this.isDrawSaleable(id)) return 'bg-light border opacity-75';
            if (this.isSelected(id)) return 'bg-primary bg-opacity-10 border border-primary';

            return 'bg-white border cursor-pointer';
        },

        drawPeriod(draw) {
            const timeValue = String(draw?.close_time || draw?.scheduled_time || '23:59').slice(0, 5);
            if (timeValue < '12:00') return 'AM';
            if (timeValue < '20:00') return 'PM';

            return 'NOCHE';
        },

        drawMatchesFilter(id) {
            const draw = this.getDrawById(id);
            if (!draw) return false;

            if (this.drawFilter === 'ALL') return true;
            if (this.drawFilter === 'OPEN') return this.isDrawSaleable(id);
            if (this.drawFilter === 'CLOSED') return !this.isDrawSaleable(id);
            if (this.drawFilter === 'SELECTED') return this.isSelected(id);

            return this.drawPeriod(draw) === this.drawFilter;
        },

        sectionHasVisibleDraws(section) {
            return this.draws.some(draw => this.drawPeriod(draw) === section && this.drawMatchesFilter(draw.id));
        },

        formatDuration(totalSeconds) {
            const seconds = Math.max(0, Number(totalSeconds || 0));
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const remainingSeconds = seconds % 60;

            return [
                String(hours).padStart(2, '0'),
                String(minutes).padStart(2, '0'),
                String(remainingSeconds).padStart(2, '0'),
            ].join(':');
        },

        toggleDraw(id, checked) {
            if (checked && !this.isDrawSaleable(id)) {
                this.selectedDraws = this.selectedDraws.filter(d => d !== id);
                this.$refs.smartInput.focus();
                return;
            }

            if (checked) {
                if (!this.selectedDraws.includes(id)) this.selectedDraws.push(id);
            } else {
                this.selectedDraws = this.selectedDraws.filter(d => d !== id);
            }
            this.$refs.smartInput.focus();
        },

        selectAll() {
            this.selectedDraws = this.draws.filter(draw => this.isDrawSaleable(draw.id)).map(d => d.id);
            this.$refs.smartInput.focus();
        },

        deselectAll() {
            this.selectedDraws = [];
            this.$refs.smartInput.focus();
        },

        setAmount(amt) {
            const normalizedAmount = Number(amt);
            this.lastAmount = Number(this.lastAmount) === normalizedAmount ? null : normalizedAmount;
            this.cancelPendingAmount(false);
            this.$refs.smartInput.focus();
        },

        clearAmount() {
            this.lastAmount = null;
            this.cancelPendingAmount(false);
            this.$refs.smartInput.focus();
        },

        async addPlay() {
            const val = this.inputValue.trim();
            if (!val) return;
            if (this.selectedDraws.length === 0) {
                alert('Selecciona al menos una loteria.');
                return;
            }

            const activeSelectedDraws = this.selectedDraws.filter(drawId => this.isDrawSaleable(drawId));
            if (activeSelectedDraws.length === 0) {
                alert('No hay sorteos disponibles para vender. Revisa la hora de apertura/cierre.');
                this.selectedDraws = [];
                return;
            }
            let parts = val.split(/[\s\-]+/);
            let amount = this.lastAmount ? Number(this.lastAmount) : null;
            let numberPart;

            if (parts.length >= 2 && /^\d+$/.test(parts[parts.length - 1]) && parseFloat(parts[parts.length - 1]) > 0) {
                const possibleAmount = parseFloat(parts[parts.length - 1]);
                if (possibleAmount < 10000 || parts.length === 2) {
                    amount = possibleAmount;
                    parts.pop();
                }
            }

            numberPart = parts.join('');
            if (!numberPart) return;

            const digitCount = numberPart.length;
            let numberValue, betType;

            if (digitCount === 2) {
                numberValue = numberPart;
                betType = this.getBetTypeByNumberCount(1);
            } else if (digitCount === 4) {
                numberValue = numberPart.substring(0, 2) + '-' + numberPart.substring(2, 4);
                betType = this.getBetTypeByNumberCount(2);
            } else if (digitCount === 6) {
                numberValue = numberPart.substring(0, 2) + '-' + numberPart.substring(2, 4) + '-' + numberPart.substring(4, 6);
                betType = this.getBetTypeByNumberCount(3);
            } else {
                alert('La jugada debe usar pares de 2 digitos: 01, 1214 o 121314.');
                return;
            }

            if (!betType) {
                alert('No hay tipo de jugada para ' + (digitCount >= 6 ? 3 : digitCount >= 4 ? 2 : 1) + ' numero(s).');
                return;
            }


            if (!amount || amount <= 0 || isNaN(amount)) {
                this.requestAmountForCurrentPlay(numberPart);
                return;
            }

            // Validación de monto máximo por tipo de jugada (instantánea, client-side)
            if (betType.max_amount && amount > parseFloat(betType.max_amount)) {
                alert('Monto máximo para ' + betType.name + ': RD$' + parseFloat(betType.max_amount).toFixed(2) + '. Ingresaste RD$' + amount.toFixed(2) + '.');
                return;
            }

            // Pre-check de limite por draw (resta lo ya pendiente en el cart)
            this.preCheckLoading = true;
            let conflicts;
            try {
                conflicts = await this.resolveLimitConflicts(activeSelectedDraws, betType, numberValue, amount);
            } finally {
                this.preCheckLoading = false;
            }

            // Sin conflictos: anadir directo
            if (conflicts.every(c => c.status === 'ok')) {
                activeSelectedDraws.forEach(drawId => {
                    this.appendPlayToDraw(drawId, betType, numberValue, amount, null);
                });
                this.inputValue = '';
                this.cancelPendingAmount(false);
                this.$nextTick(() => this.$refs.smartInput.focus());
                return;
            }

            // Hay conflictos: abrir dialog para que el cajero decida
            this.limitDialog = {
                visible: true,
                numberValue,
                betType,
                requestedAmount: amount,
                conflicts,
                decisions: conflicts.map(c => ({
                    drawId: c.drawId,
                    // default sensato: ajustar si hay disponible, omitir si agotado
                    action: c.status === 'ok' ? 'ok' : (c.status === 'partial' ? 'adjust' : 'omit'),
                })),
            };
        },

        async resolveLimitConflicts(drawIds, betType, numberValue, requestedAmount) {
            const numberDigits = String(numberValue).replace(/-/g, '');
            const checks = await Promise.all(drawIds.map(async drawId => {
                let available = null;
                try {
                    const params = new URLSearchParams({
                        draw_id: drawId,
                        bet_type_id: betType.id,
                        number_value: numberDigits,
                    });
                    const response = await fetch(this.checkLimitUrl + '?' + params, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (response.ok) {
                        const data = await response.json();
                        available = data.available;
                    }
                } catch { /* sin red: no podemos pre-checkear, dejar pasar y que el backend valide */ }

                // Restar lo ya pendiente en el cart del mismo draw+betType+number
                const pendingInCart = this.getCartPendingFor(drawId, betType.id, numberValue);
                const effective = available === null ? null : Math.max(0, available - pendingInCart);

                let status;
                if (effective === null) status = 'ok';
                else if (effective >= requestedAmount) status = 'ok';
                else if (effective > 0) status = 'partial';
                else status = 'agotado';

                return {
                    drawId,
                    drawLabel: this.getDrawLabel(drawId),
                    available,
                    pendingInCart,
                    effective,
                    requested: requestedAmount,
                    status,
                };
            }));
            return checks;
        },

        getCartPendingFor(drawId, betTypeId, numberValue) {
            return this.plays
                .filter(p => p.draw_id == drawId && p.bet_type_id === betTypeId && p.number_value === numberValue)
                .reduce((s, p) => s + Number(p.amount || 0), 0);
        },

        applyLimitDecisions() {
            const { numberValue, betType, requestedAmount, conflicts, decisions } = this.limitDialog;
            let added = 0;

            decisions.forEach(d => {
                if (d.action === 'omit') return;
                const conflict = conflicts.find(c => c.drawId === d.drawId);
                if (!conflict) return;

                let amount;
                let adjusted = false;
                if (d.action === 'ok') {
                    amount = requestedAmount;
                } else if (d.action === 'adjust') {
                    amount = conflict.effective;
                    adjusted = true;
                } else {
                    return;
                }

                if (!amount || amount <= 0) return;

                this.appendPlayToDraw(d.drawId, betType, numberValue, amount, null, adjusted);
                added++;
            });

            this.limitDialog.visible = false;
            if (added > 0) {
                this.inputValue = '';
                this.cancelPendingAmount(false);
            }
            this.$nextTick(() => this.$refs.smartInput && this.$refs.smartInput.focus());
        },

        applyAllAdjust() {
            this.limitDialog.decisions.forEach(d => {
                const c = this.limitDialog.conflicts.find(x => x.drawId === d.drawId);
                if (!c) return;
                d.action = c.status === 'agotado' ? 'omit' : 'adjust';
            });
        },

        cancelLimitDialog() {
            this.limitDialog.visible = false;
            this.$nextTick(() => this.$refs.smartInput && this.$refs.smartInput.focus());
        },

        getBetTypeByNumberCount(count) {
            return this.betTypes.find(bt => bt.numbers_count == count);
        },

        requestAmountForCurrentPlay(value) {
            this.pendingPlayValue = value;
            this.pendingAmount = '';
            this.amountPromptVisible = true;
            this.$nextTick(() => this.$refs.amountInput.focus());
        },

        confirmPendingAmount() {
            const amount = Number(String(this.pendingAmount).replace(',', '.').trim());

            if (!amount || amount <= 0 || isNaN(amount)) {
                alert('El monto debe ser mayor a 0.');
                this.$nextTick(() => this.$refs.amountInput.focus());
                return;
            }

            const playValue = this.pendingPlayValue;
            this.cancelPendingAmount(false);
            this.inputValue = playValue + ' ' + amount;
            this.addPlay();
        },

        cancelPendingAmount(refocus = true) {
            this.amountPromptVisible = false;
            this.pendingPlayValue = '';
            this.pendingAmount = '';
            if (refocus) this.$nextTick(() => this.$refs.smartInput.focus());
        },

        getBetTypeById(id) {
            return this.betTypes.find(bt => bt.id == id);
        },

        getDrawById(id) {
            return this.draws.find(draw => draw.id == id);
        },

        getDrawLabel(drawId) {
            const draw = this.getDrawById(drawId);
            if (!draw) return '-';

            const lotteryName = draw.lottery?.name || draw.name || 'Loteria';

            return draw.name && draw.name !== lotteryName
                ? lotteryName + ' - ' + draw.name
                : lotteryName;
        },

        getDrawLotteryName(drawId) {
            const draw = this.getDrawById(drawId);

            return draw?.lottery?.name || draw?.name || 'Loteria';
        },

        getDrawName(drawId) {
            const draw = this.getDrawById(drawId);

            return draw?.name || '-';
        },

        appendPlayToDraw(drawId, betType, numberValue, amount, estimatedPrize, adjusted = false) {
            const existing = this.plays.find(p =>
                p.draw_id == drawId &&
                p.bet_type_id === betType.id &&
                p.number_value === numberValue
            );

            if (existing) {
                existing.amount += amount;
                existing.estimatedPrize = null;
                existing.limitStatus = 'checking';
                existing.limitMessage = null;
                if (adjusted) existing._limitAdjusted = true;
                this.checkPlayLimit(existing);
                return;
            }

            const play = {
                draw_id: drawId,
                bet_type_id: betType.id,
                betTypeCode: betType.code,
                number_value: numberValue,
                amount: amount,
                estimatedPrize: estimatedPrize,
                lotteryName: this.getDrawLotteryName(drawId),
                drawName: this.getDrawName(drawId),
                limitStatus: 'checking',  // null | 'checking' | 'ok' | 'warning' | 'error'
                limitMessage: null,
                _limitAdjusted: adjusted,
            };
            this.plays.push(play);
            this.checkPlayLimit(play);
        },

        async checkPlayLimit(play) {
            try {
                const params = new URLSearchParams({
                    draw_id: play.draw_id,
                    bet_type_id: play.bet_type_id,
                    number_value: play.number_value.replace(/-/g, ''),
                });
                const response = await fetch(this.checkLimitUrl + '?' + params, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) {
                    play.limitStatus = null;
                    return;
                }
                const data = await response.json();

                if (data.blocked) {
                    play.limitStatus = 'error';
                    play.limitMessage = 'Número agotado (disponible: $0)';
                    return;
                }

                if (data.available !== null) {
                    const remaining = data.available - play.amount;
                    if (remaining < 0) {
                        play.limitStatus = 'error';
                        play.limitMessage = 'Excede límite. Disponible: RD$' + data.available.toFixed(2);
                    } else if (data.available < play.amount * 3) {
                        // Warning: menos de 3x el monto apostado disponible
                        play.limitStatus = 'warning';
                        play.limitMessage = 'Quedan RD$' + Math.max(0, remaining).toFixed(2) + ' disponibles';
                    } else {
                        play.limitStatus = 'ok';
                        play.limitMessage = null;
                    }
                } else {
                    play.limitStatus = 'ok';
                    play.limitMessage = null;
                }
            } catch {
                play.limitStatus = null;
            }
        },

        getPlaysByDraw(drawId) {
            return this.plays.filter(play => play.draw_id == drawId);
        },

        drawsWithPlays() {
            return [...new Set(this.plays.map(play => play.draw_id))];
        },

        removePlay(idx) { this.plays.splice(idx, 1); },
        removeLastPlay() { this.plays.pop(); },
        clearInput() { this.inputValue = ''; },

        // -------- VOLTEAR --------
        flipPlayValue(value) {
            // "12" -> "21"; "12-34" -> "21-43"; "12-34-56" -> "21-43-65"
            return String(value || '')
                .split('-')
                .map(chunk => chunk.length === 2 ? chunk.split('').reverse().join('') : chunk)
                .join('-');
        },

        toggleVoltear() {
            if (this.voltearActivo) {
                // Quitar todas las jugadas auto-volteadas
                this.plays = this.plays.filter(p => !p._flipped);
                this.voltearActivo = false;
                return;
            }
            // Generar volteadas a partir de las jugadas actuales
            const sourcePlays = this.plays.filter(p => !p._flipped);
            const flipped = [];
            sourcePlays.forEach(p => {
                const flippedValue = this.flipPlayValue(p.number_value);
                if (flippedValue === p.number_value) return; // capicua: no duplicar
                // Evitar duplicar contra una jugada existente
                const exists = this.plays.find(x =>
                    x.draw_id == p.draw_id &&
                    x.bet_type_id === p.bet_type_id &&
                    x.number_value === flippedValue
                );
                if (exists) return;
                flipped.push({
                    ...p,
                    number_value: flippedValue,
                    estimatedPrize: null,
                    limitStatus: 'checking',
                    limitMessage: null,
                    _flipped: true,
                    _combined: false,
                });
            });
            if (flipped.length === 0) {
                alert('No se generaron jugadas volteadas (todas eran capicuas o ya existian).');
                return;
            }
            this.plays.push(...flipped);
            this.voltearActivo = true;
            flipped.forEach(p => this.checkPlayLimit(p));
        },

        // -------- COMBINAR --------
        averagePlayAmount() {
            if (this.plays.length === 0) return 0;
            const sum = this.plays.reduce((s, p) => s + Number(p.amount || 0), 0);
            return Math.round((sum / this.plays.length) * 100) / 100;
        },

        // Numeros unicos de 2 digitos por draw, extraidos de TODAS las jugadas (quiniela tal cual, pale/tripleta divididas)
        expandNumbersByDraw() {
            const map = {}; // drawId -> Set<string>
            this.plays.forEach(p => {
                const code = String(p.betTypeCode || '').toUpperCase();
                if (code === 'SUPER_PALE') return; // super se reconstruye desde quinielas; ignorar como fuente
                const chunks = String(p.number_value || '').split('-').filter(c => c.length === 2);
                if (!chunks.length) return;
                if (!map[p.draw_id]) map[p.draw_id] = new Set();
                chunks.forEach(n => map[p.draw_id].add(n));
            });
            // Set -> sorted array
            const out = {};
            Object.keys(map).forEach(drawId => {
                out[drawId] = [...map[drawId]].sort();
            });
            return out;
        },

        canCombinarSuper() {
            const byDraw = this.expandNumbersByDraw();
            return Object.values(byDraw).filter(arr => arr.length > 0).length >= 2;
        },

        combinarSourceSummary() {
            const byDraw = this.expandNumbersByDraw();
            const totalNumbers = Object.values(byDraw).reduce((s, arr) => s + arr.length, 0);
            const lotteries = Object.keys(byDraw).length;
            return `${totalNumbers} numero(s) en ${lotteries} loteria(s)`;
        },

        combinarPreview() {
            const byDraw = this.expandNumbersByDraw();
            let pale = 0, tripleta = 0, sup = 0, quiniela = 0;

            Object.entries(byDraw).forEach(([drawId, numeros]) => {
                if (this.combinarOpts.pale && numeros.length >= 2) {
                    pale += (numeros.length * (numeros.length - 1)) / 2;
                }
                if (this.combinarOpts.tripleta && numeros.length >= 3) {
                    const n = numeros.length;
                    tripleta += (n * (n - 1) * (n - 2)) / 6;
                }
            });

            if (this.combinarOpts.super && this.canCombinarSuper()) {
                const drawIds = Object.keys(byDraw).filter(d => byDraw[d].length > 0).slice(0, 2);
                const [a, b] = drawIds;
                sup = byDraw[a].length * byDraw[b].length;
            }

            if (this.combinarOpts.quiniela) {
                // 1 quiniela por numero unico por loteria
                quiniela = Object.values(byDraw).reduce((s, arr) => s + arr.length, 0);
            }

            return { pale, tripleta, super: sup, quiniela, total: pale + tripleta + sup + quiniela };
        },

        openCombinarModal() {
            if (this.plays.length === 0) return;
            this.combinarOpts = { pale: false, tripleta: false, super: false, quiniela: false };
            this.combinarMontos = {
                pale: '', tripleta: '', super: '', quiniela: '',
                global: String(this.averagePlayAmount()),
            };
            this.combinarModalVisible = true;
        },

        getCombinarAmount(tipo) {
            const own = Number(this.combinarMontos[tipo]);
            if (own && own > 0) return own;
            const global = Number(this.combinarMontos.global);
            if (global && global > 0) return global;
            return this.averagePlayAmount();
        },

        getBetTypeByCode(code) {
            return this.betTypes.find(bt => String(bt.code || '').toUpperCase() === code);
        },

        submitCombinar() {
            const byDraw = this.expandNumbersByDraw();
            const preview = this.combinarPreview();
            if (preview.total === 0) { this.combinarModalVisible = false; return; }

            const generated = [];

            // PALE: combinaciones C(n,2) por loteria
            if (this.combinarOpts.pale) {
                const bt = this.getBetTypeByCode('PALE');
                if (!bt) { alert('No existe tipo PALE configurado.'); return; }
                const amount = this.getCombinarAmount('pale');
                Object.entries(byDraw).forEach(([drawId, numeros]) => {
                    if (numeros.length < 2) return;
                    if (!this.isDrawSaleable(Number(drawId))) return;
                    for (let i = 0; i < numeros.length - 1; i++) {
                        for (let j = i + 1; j < numeros.length; j++) {
                            generated.push({ drawId: Number(drawId), bt, value: `${numeros[i]}-${numeros[j]}`, amount });
                        }
                    }
                });
            }

            // TRIPLETA: C(n,3) por loteria
            if (this.combinarOpts.tripleta) {
                const bt = this.getBetTypeByCode('TRIPLETA');
                if (!bt) { alert('No existe tipo TRIPLETA configurado.'); return; }
                const amount = this.getCombinarAmount('tripleta');
                Object.entries(byDraw).forEach(([drawId, numeros]) => {
                    if (numeros.length < 3) return;
                    if (!this.isDrawSaleable(Number(drawId))) return;
                    for (let i = 0; i < numeros.length - 2; i++) {
                        for (let j = i + 1; j < numeros.length - 1; j++) {
                            for (let k = j + 1; k < numeros.length; k++) {
                                generated.push({ drawId: Number(drawId), bt, value: `${numeros[i]}-${numeros[j]}-${numeros[k]}`, amount });
                            }
                        }
                    }
                });
            }

            // SUPER PALE: cross-product entre las 2 primeras loterias con numeros
            // NOTA: super_pale en BSLotery se modela como una jugada por draw; aqui repetimos la
            // misma jugada en cada uno de los 2 draws (consistente con el resto del POS, que vende
            // por draw_id). Si en el futuro hay un modelo cross-draw real, ajustar aqui.
            if (this.combinarOpts.super && this.canCombinarSuper()) {
                const bt = this.getBetTypeByCode('SUPER_PALE');
                if (!bt) { alert('No existe tipo SUPER_PALE configurado.'); return; }
                const amount = this.getCombinarAmount('super');
                const drawIds = Object.keys(byDraw).filter(d => byDraw[d].length > 0).slice(0, 2);
                const [a, b] = drawIds;
                if (this.isDrawSaleable(Number(a)) && this.isDrawSaleable(Number(b))) {
                    byDraw[a].forEach(n1 => {
                        byDraw[b].forEach(n2 => {
                            generated.push({ drawId: Number(a), bt, value: `${n1}-${n2}`, amount });
                        });
                    });
                }
            }

            // QUINIELA: 1 jugada Q por numero unico por loteria
            if (this.combinarOpts.quiniela) {
                const bt = this.getBetTypeByCode('QUINIELA');
                if (!bt) { alert('No existe tipo QUINIELA configurado.'); return; }
                const amount = this.getCombinarAmount('quiniela');
                Object.entries(byDraw).forEach(([drawId, numeros]) => {
                    if (!this.isDrawSaleable(Number(drawId))) return;
                    numeros.forEach(n => {
                        generated.push({ drawId: Number(drawId), bt, value: n, amount });
                    });
                });
            }

            // Aplicar: dedup contra existentes, sumar monto si ya esta, sino crear con marca _combined
            let added = 0;
            generated.forEach(g => {
                const existing = this.plays.find(p =>
                    p.draw_id == g.drawId &&
                    p.bet_type_id === g.bt.id &&
                    p.number_value === g.value
                );
                if (existing) {
                    existing.amount += g.amount;
                    existing.estimatedPrize = null;
                    existing.limitStatus = 'checking';
                    existing.limitMessage = null;
                    this.checkPlayLimit(existing);
                    return;
                }
                const play = {
                    draw_id: g.drawId,
                    bet_type_id: g.bt.id,
                    betTypeCode: g.bt.code,
                    number_value: g.value,
                    amount: g.amount,
                    estimatedPrize: null,
                    lotteryName: this.getDrawLotteryName(g.drawId),
                    drawName: this.getDrawName(g.drawId),
                    limitStatus: 'checking',
                    limitMessage: null,
                    _flipped: false,
                    _combined: true,
                };
                this.plays.push(play);
                this.checkPlayLimit(play);
                added++;
            });

            this.combinarModalVisible = false;
            this.$nextTick(() => this.$refs.smartInput && this.$refs.smartInput.focus());
        },
        money(value) { return Number(value || 0).toFixed(2); },
        betShortCode(code) {
            const normalized = String(code || '').toUpperCase();
            if (normalized === 'QUINIELA') return 'Q';
            if (normalized === 'PALE') return 'P';
            if (normalized === 'TRIPLETA') return 'T';
            if (normalized === 'SUPER_PALE') return 'SP';

            return normalized.slice(0, 3);
        },
        padRight(value, length) { return String(value).padEnd(length, ' ').slice(0, length); },
        padLeft(value, length) { return String(value).padStart(length, ' ').slice(-length); },
        betTicketCode(code) {
            const normalized = String(code || '').toUpperCase();
            if (normalized === 'QUINIELA') return 'q';
            if (normalized === 'PALE') return 'pl';
            if (normalized === 'TRIPLETA') return 'tri';
            if (normalized === 'SUPER_PALE') return 'sp';
            return normalized.toLowerCase().slice(0, 3);
        },

        buildSaleConfirmationText() {
            const line = '-'.repeat(32);
            const lines = [
                this.companyName.toUpperCase().padStart(Math.floor((32 + this.companyName.length) / 2)).slice(0, 32),
                line,
                'Fecha: ' + new Date().toLocaleString('es-DO', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                }),
                line,
                '    PREVISUALIZACION VENTA',
                'Ticket No: se genera al cobrar',
                line,
            ];

            this.drawsWithPlays().forEach(drawId => {
                const draw = this.getDrawById(drawId);
                const lottery = draw?.lottery || {};
                const lotteryCode = lottery.code || draw?.name || 'LOTERIA';
                const lotteryName = lottery.name || draw?.name || 'Loteria';

                lines.push(this.padRight(lotteryCode, 10) + ' -- ' + lotteryName);
                lines.push(line);
                lines.push(lotteryCode);

                this.getPlaysByDraw(drawId).forEach(play => {
                    const code = this.padRight(this.betTicketCode(play.betTypeCode), 4);
                    const number = this.padRight(play.number_value, 11);
                    const amount = this.padLeft(this.money(play.amount), 9);
                    lines.push(code + number + ' RD$ ' + amount);
                });

                lines.push(line);
            });

            lines.push('Total:' + this.padLeft('RD$ ' + this.money(this.totalAmount), 26));
            lines.push(line);
            lines.push('Jug. con lot. inic. no se paga');
            lines.push('Buena Suerte, Revise su Ticket.');
            lines.push(line);

            return lines.join('\n');
        },

        clearAll() {
            if (confirm('Limpiar todas las jugadas?')) {
                this.plays = [];
                this.voltearActivo = false;
                this.$nextTick(() => this.$refs.smartInput.focus());
            }
        },

        resetAll() {
            if (confirm('Limpiar jugadas y loterías seleccionadas?')) {
                this.plays = [];
                this.voltearActivo = false;
                this.selectedDraws = [];
                this.$nextTick(() => this.$refs.smartInput.focus());
            }
        },

        showPreview() {
            const drawId = this.drawsWithPlays()[0];
            if (!drawId) return;

            const playsForDraw = this.getPlaysByDraw(drawId);
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('admin.tickets.preview') }}';
            form.innerHTML = '@csrf<input type="hidden" name="branch_id" value="' + this.branchId + '"><input type="hidden" name="draw_id" value="' + drawId + '">';
            playsForDraw.forEach((p, i) => {
                form.innerHTML += '<input type="hidden" name="plays[' + i + '][bet_type_id]" value="' + p.bet_type_id + '">';
                form.innerHTML += '<input type="hidden" name="plays[' + i + '][number_value]" value="' + p.number_value + '">';
                form.innerHTML += '<input type="hidden" name="plays[' + i + '][amount]" value="' + p.amount + '">';
            });
            document.body.appendChild(form);
            form.submit();
        },

        async submitSale() {
            if (!this.canSell) return;
            const drawIds = this.drawsWithPlays();
            if (!drawIds.length) return;
            if (!drawIds.every(drawId => this.isDrawSaleable(drawId))) {
                alert('Hay jugadas en sorteos cerrados o fuera de horario. Elimina esas jugadas antes de cobrar.');
                return;
            }
            if (!confirm(this.buildSaleConfirmationText())) return;

            this.isSelling = true;
            let lastTicketUrl = null;

            try {
                for (const drawId of drawIds) {
                    const playsForDraw = this.getPlaysByDraw(drawId);
                    const formData = new FormData();
                    formData.append('_token', this.csrfToken);
                    formData.append('branch_id', this.branchId);
                    formData.append('draw_id', drawId);

                    playsForDraw.forEach((p, i) => {
                        formData.append('plays[' + i + '][bet_type_id]', p.bet_type_id);
                        formData.append('plays[' + i + '][number_value]', p.number_value);
                        formData.append('plays[' + i + '][amount]', p.amount);
                    });

                    const response = await fetch(this.storeUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(payload.message || 'No se pudo vender el ticket.');
                    }

                    lastTicketUrl = payload.show_url;
                }

                this.plays = [];
                this.voltearActivo = false;

                if (lastTicketUrl) {
                    window.location.href = lastTicketUrl;
                }
            } catch (error) {
                alert(error.message || 'Error vendiendo ticket.');
                this.isSelling = false;
            }
        },
        async lookupTicket() {
            const token = this.ticketLookupToken.trim();
            if (!token) {
                this.lookupMessage = 'Escribe o escanea un numero de ticket o QR.';
                this.lookupError = true;
                this.$refs.ticketLookup.focus();
                return;
            }

            this.lookupLoading = true;
            this.lookupError = false;
            this.lookupMessage = 'Buscando ticket...';

            try {
                const response = await fetch(this.lookupUrl + '?token=' + encodeURIComponent(token), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(payload.message || 'Ticket no encontrado.');
                }

                this.lookupResult = payload;
                this.lookupMessage = 'Ticket encontrado.';
                this.lookupError = false;
            } catch (error) {
                this.lookupResult = null;
                this.lookupMessage = error.message || 'No se pudo buscar el ticket.';
                this.lookupError = true;
            } finally {
                this.lookupLoading = false;
                this.$nextTick(() => this.$refs.ticketLookup.focus());
            }
        },

        copyLookupPlays() {
            if (!this.lookupResult || !this.lookupResult.copy_plays.length) return;
            const activeSelectedDraws = this.selectedDraws.filter(drawId => this.isDrawSaleable(drawId));
            if (activeSelectedDraws.length === 0) {
                alert('Selecciona al menos una loteria.');
                return;
            }

            this.lookupResult.copy_plays.forEach((play) => {
                const betType = this.getBetTypeById(play.bet_type_id);
                if (!betType) return;

                const amount = Number(play.amount || 0);

                activeSelectedDraws.forEach(drawId => {
                    this.appendPlayToDraw(drawId, betType, play.number_value, amount, null);
                });
            });

            this.lookupMessage = 'Jugadas copiadas al ticket actual.';
            this.lookupError = false;
            this.$nextTick(() => this.$refs.smartInput.focus());
        },

        payWinner(winner) {
            if (!winner.can_pay) return;
            if (!confirm('Pagar premio de RD$ ' + this.money(winner.prize_amount) + '?')) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = winner.pay_url;
            form.innerHTML = '<input type="hidden" name="_token" value="' + this.csrfToken + '"><input type="hidden" name="return_to" value="sales">';
            document.body.appendChild(form);
            form.submit();
        },

        payTicket(ticket) {
            if (!ticket.can_pay_released_prizes) return;
            if (!confirm('Pagar todos los premios liberados de este ticket por RD$ ' + this.money(ticket.released_prize_total) + '?')) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = ticket.pay_url;
            form.innerHTML = '<input type="hidden" name="_token" value="' + this.csrfToken + '"><input type="hidden" name="return_to" value="sales">';
            document.body.appendChild(form);
            form.submit();
        },

        async scanQr() {
            if (!('BarcodeDetector' in window) || !navigator.mediaDevices?.getUserMedia) {
                this.lookupMessage = 'Lector QR listo: escanea con pistola, escribe o pega el codigo.';
                this.lookupError = false;
                this.$refs.ticketLookup.focus();
                this.$refs.ticketLookup.select();
                return;
            }

            let stream = null;
            let overlay = null;

            try {
                const detector = new BarcodeDetector({ formats: ['qr_code'] });
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                overlay = document.createElement('div');
                overlay.className = 'qr-scan-overlay';
                overlay.innerHTML = '<div class="qr-scan-box"><video autoplay playsinline></video><button type="button" class="btn btn-light btn-sm mt-3">Cancelar</button></div>';
                document.body.appendChild(overlay);

                const video = overlay.querySelector('video');
                const cancel = overlay.querySelector('button');
                video.srcObject = stream;

                let cancelled = false;
                cancel.addEventListener('click', () => { cancelled = true; });

                const deadline = Date.now() + 15000;
                while (!cancelled && Date.now() < deadline) {
                    const codes = await detector.detect(video).catch(() => []);
                    if (codes.length > 0) {
                        this.ticketLookupToken = codes[0].rawValue;
                        break;
                    }
                    await new Promise(resolve => setTimeout(resolve, 300));
                }

                if (this.ticketLookupToken) {
                    await this.lookupTicket();
                }
            } catch (error) {
                this.lookupMessage = 'No se pudo abrir camara. Usa lector QR, teclado o pega el codigo.';
                this.lookupError = true;
                this.$refs.ticketLookup.focus();
            } finally {
                if (stream) stream.getTracks().forEach(track => track.stop());
                if (overlay) overlay.remove();
            }
        },

        handleGlobalKey(e) {
            if (e.ctrlKey && e.key === 'l') { e.preventDefault(); this.clearAll(); return; }
            if (e.ctrlKey && e.key === 't') { e.preventDefault(); this.resetAll(); return; }
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
            switch (e.key) {
                case 'F1': e.preventDefault(); this.clearAll(); break;
                case 'F9': e.preventDefault(); this.submitSale(); break;
                case 'Escape': e.preventDefault(); this.clearInput(); break;
            }
        },

        init() {
            this.clockTimer = setInterval(() => {
                this.currentTime = new Date();
                this.selectedDraws = this.selectedDraws.filter(drawId => this.isDrawSaleable(drawId));
            }, 1000);
            this.$nextTick(() => {
                if (this.$refs.smartInput) this.$refs.smartInput.focus();
            });
        }
    };
}
</script>

<style>
#posApp [x-cloak] { display: none !important; }
#posApp .pos-operator-header {
    flex: 0 0 auto;
}
#posApp .pos-kpi {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .25rem .5rem;
    border: 1px solid #e9ecef;
    border-radius: .55rem;
    background: #f8f9fe;
    color: #67748e;
    font-size: .72rem;
    white-space: nowrap;
}
#posApp .pos-kpi strong {
    color: #344767;
    font-size: .78rem;
}
#posApp .pos-kpi-money {
    background: #ecfdf3;
    border-color: #bbf7d0;
}
#posApp .pos-kpi-money strong {
    color: #15803d;
}
#posApp .pos-lottery-board {
    flex: 0 0 auto;
    background: #f8f9fe;
}
#posApp .pos-main-area,
#posApp .pos-summary-panel {
    min-height: 0;
}
#posApp .pos-lottery-sections {
    display: flex;
    align-items: start;
    gap: .5rem;
    max-height: 235px;
    overflow: hidden;
    padding: .5rem;
}
#posApp .pos-lottery-section {
    flex: var(--draw-count) 1 180px;
    min-width: 0;
    padding: .45rem;
    border: 1px solid #e9ecef;
    border-radius: .65rem;
    background: #fff;
}
#posApp .pos-section-title {
    padding: .08rem .25rem .35rem;
    color: #344767;
    font-size: .7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .02em;
}
#posApp .pos-draw-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(136px, 1fr));
    gap: .35rem;
}
#posApp .pos-draw-card {
    display: block;
    min-height: 58px;
    padding: .45rem;
    border-radius: .55rem;
    transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease, opacity .15s ease;
}
#posApp .pos-empty-section {
    padding: .75rem .5rem;
    border: 1px dashed #d9dee8;
    border-radius: .65rem;
    color: #8392ab;
    font-size: .75rem;
    text-align: center;
    background: #fff;
}
#posApp .pos-draw-card:hover {
    box-shadow: 0 3px 10px rgba(15, 23, 42, .08);
}
#posApp .pos-draw-title,
#posApp .pos-draw-subtitle,
#posApp .pos-draw-time {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
#posApp .pos-draw-title {
    font-size: .78rem;
    line-height: 1.12;
}
#posApp .pos-draw-subtitle,
#posApp .pos-draw-time {
    font-size: .64rem;
    line-height: 1.12;
}
#posApp .pos-input-panel {
    background: linear-gradient(135deg, #5e72e4 0%, #825ee4 100%);
    padding: .55rem;
}
#posApp .pos-bet-type-strip {
    max-width: 270px;
}
#posApp .pos-amount-strip {
    max-width: 520px;
}
#posApp .pos-empty-ticket {
    min-height: 150px;
}
#posApp table.table {
    font-size: .82rem;
}
#posApp table.table td,
#posApp table.table th {
    padding-top: .35rem;
    padding-bottom: .35rem;
}
#posApp .pos-summary-panel {
    width: 330px;
    min-width: 330px;
    background: #f8f9fe;
    border-radius: .75rem;
    overflow: hidden;
}
#posApp .pos-total-box {
    padding: .85rem;
    border-radius: .8rem;
    background: linear-gradient(135deg, #172b4d 0%, #2dce89 100%);
    color: #fff;
}
#posApp .pos-total-amount {
    font-size: 2rem;
    line-height: 1;
    font-weight: 800;
    letter-spacing: 0;
}
#posApp .pos-summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .45rem;
}
#posApp .pos-summary-grid div {
    padding: .5rem;
    border: 1px solid #e9ecef;
    border-radius: .65rem;
    background: #fff;
}
#posApp .pos-summary-grid span {
    display: block;
    color: #8392ab;
    font-size: .68rem;
}
#posApp .pos-summary-grid strong {
    display: block;
    color: #344767;
    font-size: .9rem;
}
#posApp .pos-ticket-lookup .accordion-button {
    box-shadow: none;
}
#posApp .hover-text-danger:hover { color: var(--bs-danger) !important; }
@media (max-width: 1180px) {
    #posApp .pos-lottery-sections {
        max-height: 225px;
        gap: .35rem;
        padding: .35rem;
    }
    #posApp .pos-draw-row {
        grid-template-columns: repeat(auto-fill, minmax(116px, 1fr));
    }
    #posApp .pos-summary-panel {
        width: 300px;
        min-width: 300px;
    }
    #posApp .pos-total-amount {
        font-size: 1.6rem;
    }
    #posApp .pos-draw-card {
        min-height: 54px;
        padding: .38rem;
    }
    #posApp .pos-draw-title {
        font-size: .72rem;
    }
    #posApp .pos-draw-subtitle,
    #posApp .pos-draw-time {
        font-size: .58rem;
    }
}
#posApp .pos-combinar-overlay {
    position: fixed;
    inset: 0;
    z-index: 1990;
    background: rgba(15, 23, 42, .55);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
#posApp .pos-combinar-modal {
    width: min(520px, 96vw);
    max-height: 92vh;
    overflow: auto;
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 12px 40px rgba(15, 23, 42, .25);
    display: flex;
    flex-direction: column;
}
.qr-scan-overlay {
    position: fixed;
    inset: 0;
    z-index: 2000;
    background: rgba(15, 23, 42, .82);
    display: flex;
    align-items: center;
    justify-content: center;
}
.qr-scan-box {
    width: min(420px, 92vw);
    padding: 1rem;
    border-radius: 1rem;
    background: #111827;
    text-align: center;
}
.qr-scan-box video {
    width: 100%;
    border-radius: .75rem;
    border: 2px solid rgba(255,255,255,.35);
}
</style>
@endsection
