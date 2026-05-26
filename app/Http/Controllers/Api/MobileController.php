<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BetType;
use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Device;
use App\Models\Draw;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WinnerTicket;
use App\Services\Audit\AuditService;
use App\Services\Cash\CashService;
use App\Services\Lottery\DrawGenerationService;
use App\Services\Lottery\LimitValidationService;
use App\Services\Results\PrizePaymentService;
use App\Services\Sales\TicketSaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MobileController extends Controller
{
    public function __construct(
        private readonly TicketSaleService $saleService,
        private readonly CashService $cashService,
        private readonly PrizePaymentService $prizeService,
        private readonly LimitValidationService $limitValidator,
        private readonly DrawGenerationService $drawGenerationService,
    ) {}

    // GET /api/mobile/tickets/check-limit?draw_id=&bet_type_id=&number_value=
    public function checkLimit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'draw_id' => 'required|exists:draws,id',
            'bet_type_id' => 'required|exists:bet_types,id',
            'number_value' => 'required|string|max:20',
        ]);

        $user = $request->user();
        $branch = Branch::find($user->branch_id);

        if (! $branch) {
            return response()->json(['available' => null, 'max_bet' => null, 'blocked' => false]);
        }

        $draw = Draw::findOrFail($data['draw_id']);
        $betType = BetType::findOrFail($data['bet_type_id']);
        $numberValue = str_pad($data['number_value'], $betType->digits_count, '0', STR_PAD_LEFT);

        $available = $this->limitValidator->getAvailableForNumber($branch, $draw, $betType, $numberValue);

        return response()->json([
            'available' => $available,
            'max_bet' => $betType->max_amount !== null ? (float) $betType->max_amount : null,
            'blocked' => $available !== null && $available <= 0,
        ]);
    }

    // POST /api/mobile/login
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'username' => 'nullable|string|max:100',
            'password' => 'required|string',
            'device_uuid' => 'required|uuid',
            'device_name' => 'required|string|max:255',
            'platform' => 'nullable|string|max:50',
            'app_version' => 'nullable|string|max:30',
            'device_fingerprint' => 'nullable|string|max:255',
        ]);

        $login = $data['login'] ?? $data['email'] ?? $data['username'] ?? null;
        if (! $login) {
            return response()->json(['message' => 'Usuario o correo requerido.'], 422);
        }

        $user = User::query()
            ->where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        if ($user->status !== 'ACTIVE') {
            return response()->json(['message' => 'Usuario inactivo o bloqueado'], 403);
        }

        $device = Device::query()->firstOrNew([
            'uuid' => $data['device_uuid'],
            'company_id' => $user->company_id,
        ]);

        if ($device->exists && $device->status === 'BLOCKED') {
            return response()->json([
                'message' => 'Dispositivo bloqueado. Contacte a su administrador.',
                'code' => 'DEVICE_BLOCKED',
            ], 403);
        }

        $device->forceFill([
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'name' => $data['device_name'],
            'device_type' => 'ANDROID',
            'platform' => $data['platform'] ?? 'ANDROID',
            'app_version' => $data['app_version'] ?? $device->app_version,
            'device_fingerprint' => $data['device_fingerprint'] ?? $device->device_fingerprint ?? $data['device_uuid'],
            'last_seen_at' => now(),
            'status' => $device->exists ? $device->status : 'PENDING',
        ])->save();

        $token = $user->createToken($data['device_name'], ['mobile'])->plainTextToken;

        $user->load('role.permissions', 'branch', 'company');

        $permissions = $user->role?->permissions->pluck('slug') ?? collect();

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->role ? [$user->role->name] : [],
                'permissions' => $permissions,
            ],
            'branch' => $user->branch ? ['id' => $user->branch->id,  'name' => $user->branch->name,  'company_id' => $user->branch->company_id] : null,
            'company' => $user->company ? ['id' => $user->company->id, 'name' => $user->company->name] : null,
            'device_status' => $device->status,
        ]);
    }

    // POST /api/mobile/logout
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'OK']);
    }

    // GET /api/mobile/sync/data
    public function syncData(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->drawGenerationService->generateForCompany((int) $user->company_id, days: 2);

        $timezone = $user->company?->timezone ?: config('app.timezone', 'America/Santo_Domingo');
        $now = now($timezone);
        $nowTime = $now->format('H:i:s');
        $today = $now->toDateString();

        $draws = Draw::where('company_id', $user->company_id)
            ->where('status', 'OPEN')
            ->whereDate('draw_date', '>=', $today)
            ->where(function ($q) use ($today, $nowTime): void {
                // Sorteos de hoy: solo los que aún no han cerrado
                $q->where(function ($q2) use ($today, $nowTime): void {
                    $q2->whereDate('draw_date', $today)
                        ->where('close_time', '>', $nowTime);
                })
                // Sorteos de días futuros: todos
                ->orWhereDate('draw_date', '>', $today);
            })
            ->with('lottery')
            ->orderBy('draw_date')
            ->orderBy('scheduled_time')
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'lottery_id' => $d->lottery_id,
                'lottery_name' => $d->lottery->name ?? '',
                'name' => $d->name,
                'draw_date' => $d->draw_date?->toDateString(),
                'open_time' => $d->open_time ? substr((string) $d->open_time, 0, 5) : null,
                'draw_time' => substr((string) $d->scheduled_time, 0, 5),
                'status' => $d->status,
                'cutoff_time' => substr((string) $d->close_time, 0, 5),
            ]);

        $betTypes = BetType::where(function ($query) use ($user): void {
            $query->where('company_id', $user->company_id)->orWhereNull('company_id');
        })
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'code' => $b->code,
                'multiplier' => '0.00',
                'lottery_id' => null,
            ]);

        return response()->json([
            'draws' => $draws,
            'bet_types' => $betTypes,
            'server_time' => now()->toISOString(),
        ]);
    }

    // GET /api/mobile/tickets/lookup?token={uuid|ticket_number|ticket:uuid}
    public function lookupTicket(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string|max:120',
        ]);

        $user = $request->user();
        $token = trim($data['token']);
        $normalized = str_starts_with($token, 'ticket:') ? substr($token, 7) : $token;

        $ticket = Ticket::query()
            ->where('company_id', $user->company_id)
            ->when($user->branch_id, fn ($query) => $query->where('branch_id', $user->branch_id))
            ->where(function ($query) use ($normalized): void {
                $query->where('uuid', $normalized)
                    ->orWhere('ticket_number', $normalized);
            })
            ->with(['details.betType', 'details.draw.lottery'])
            ->firstOrFail();

        return response()->json($this->formatTicket($ticket, true));
    }

    // POST /api/mobile/tickets
    public function storeTicket(Request $request): JsonResponse
    {
        $data = $request->validate([
            'uuid' => 'required|uuid',
            'draw_id' => 'required|integer|exists:draws,id',
            'details' => 'required|array|min:1',
            'details.*.number_value' => 'required|string|max:20',
            'details.*.bet_type_id' => 'required|integer|exists:bet_types,id',
            'details.*.amount' => 'required|numeric|min:1',
            'sold_at' => 'nullable|date',
            'offline' => 'boolean',
        ]);

        // Idempotency: return existing ticket if UUID already processed
        $existing = Ticket::where('uuid', $data['uuid'])->first();
        if ($existing) {
            return response()->json($this->formatTicket($existing, true), 200);
        }

        try {
            $ticket = DB::transaction(function () use ($data, $request) {
                return $this->saleService->sellFromMobile(
                    user: $request->user(),
                    drawId: $data['draw_id'],
                    details: $data['details'],
                    uuid: $data['uuid'],
                    soldAt: $data['sold_at'] ?? null,
                    device: $request->attributes->get('device') instanceof Device ? $request->attributes->get('device') : null,
                );
            });

            return response()->json($this->formatTicket($ticket, true), 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // POST /api/mobile/tickets/batch
    public function storeTicketsBatch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tickets' => 'required|array|min:1|max:50',
            'tickets.*.uuid' => 'required|uuid',
            'tickets.*.draw_id' => 'required|integer',
            'tickets.*.details' => 'required|array|min:1',
            'tickets.*.details.*.number_value' => 'required|string',
            'tickets.*.details.*.bet_type_id' => 'required|integer',
            'tickets.*.details.*.amount' => 'required|numeric|min:1',
            'tickets.*.sold_at' => 'nullable|date',
        ]);

        $synced = [];
        $failed = [];

        foreach ($data['tickets'] as $ticketData) {
            $existing = Ticket::where('uuid', $ticketData['uuid'])->first();
            if ($existing) {
                $synced[] = $ticketData['uuid'];

                continue;
            }

            try {
                DB::transaction(function () use ($ticketData, $request) {
                    $this->saleService->sellFromMobile(
                        user: $request->user(),
                        drawId: $ticketData['draw_id'],
                        details: $ticketData['details'],
                        uuid: $ticketData['uuid'],
                        soldAt: $ticketData['sold_at'] ?? null,
                        device: $request->attributes->get('device') instanceof Device ? $request->attributes->get('device') : null,
                    );
                });
                $synced[] = $ticketData['uuid'];
            } catch (\Exception $e) {
                $failed[] = ['uuid' => $ticketData['uuid'], 'reason' => $e->getMessage()];
            }
        }

        return response()->json(['synced' => $synced, 'failed' => $failed]);
    }

    // GET /api/mobile/tickets
    public function tickets(Request $request): JsonResponse
    {
        $user = $request->user();
        $tickets = Ticket::where('company_id', $user->company_id)
            ->when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->orderByDesc('sold_at')
            ->paginate($request->integer('per_page', 50))
            ->getCollection()
            ->map(fn ($t) => $this->formatTicket($t));

        return response()->json($tickets);
    }

    // GET /api/mobile/tickets/{uuid}
    public function ticket(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        $ticket = Ticket::where('uuid', $uuid)
            ->where('company_id', $user->company_id)
            ->with('details.betType')
            ->when($user->branch_id, fn ($query) => $query->where('branch_id', $user->branch_id))
            ->firstOrFail();

        return response()->json($this->formatTicket($ticket, withDetails: true));
    }

    // GET /api/mobile/cash/status
    public function cashStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $branch = $user->branch;

        if (! $branch) {
            return response()->json([
                'cash_control_enabled' => false,
                'session' => null,
                'message' => 'Sin sucursal asignada.',
            ]);
        }

        if (! $branch->cash_control_enabled) {
            return response()->json([
                'cash_control_enabled' => false,
                'session' => null,
                'branch' => ['id' => $branch->id, 'name' => $branch->name, 'company_id' => $branch->company_id],
            ]);
        }

        $session = $this->cashService->getActiveSession($branch->id, $user->id);
        if ($session) {
            $session->recalculateExpectedCash();
        }

        $movements = [];
        if ($session) {
            $movements = CashMovement::query()
                ->where('cash_session_id', $session->id)
                ->orderByDesc('created_at')
                ->limit(30)
                ->get()
                ->map(fn (CashMovement $m) => $this->formatCashMovement($m))
                ->all();
        }

        return response()->json([
            'cash_control_enabled' => true,
            'branch' => ['id' => $branch->id, 'name' => $branch->name, 'company_id' => $branch->company_id],
            'session' => $session ? $this->formatCashSession($session) : null,
            'movements' => $movements,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function formatCashMovement(CashMovement $m): array
    {
        return [
            'id' => $m->id,
            'type' => $m->type,
            'direction' => $m->direction,
            'amount' => (string) $m->amount,
            'payment_method' => $m->payment_method,
            'description' => $m->description,
            'reference_type' => $m->reference_type,
            'reference_id' => $m->reference_id,
            'created_at' => optional($m->created_at)->toIso8601String(),
        ];
    }

    // POST /api/mobile/cash/open
    public function cashOpen(Request $request): JsonResponse
    {
        $data = $request->validate([
            'opening_amount' => 'required|numeric|min:0|max:99999999.99',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $branch = $user->branch;

        if (! $branch) {
            return response()->json(['message' => 'Sin sucursal asignada.'], 422);
        }

        if (! $branch->cash_control_enabled) {
            return response()->json(['message' => 'La sucursal no usa control de caja.'], 422);
        }

        try {
            $session = $this->cashService->open(
                branch: $branch,
                user: $user,
                openingAmount: $data['opening_amount'],
                notes: $data['notes'] ?? null,
            );

            app(AuditService::class)->record(
                module: 'Cash',
                action: 'opened',
                auditable: $session,
                description: "Caja abierta desde Android en {$branch->name} con RD\$ ".number_format((float) $session->opening_amount, 2),
            );

            return response()->json([
                'session' => $this->formatCashSession($session),
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // POST /api/mobile/cash/close
    public function cashClose(Request $request): JsonResponse
    {
        $data = $request->validate([
            'counted_cash' => 'nullable|numeric|min:0|max:99999999.99',
            'denominations' => 'nullable|array',
            'denominations.*' => 'nullable|integer|min:0|max:100000',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $branch = $user->branch;

        if (! $branch || ! $branch->cash_control_enabled) {
            return response()->json(['message' => 'La sucursal no usa control de caja.'], 422);
        }

        $session = $this->cashService->getActiveSession($branch->id, $user->id);
        if (! $session) {
            return response()->json(['message' => 'No hay caja abierta para este usuario.'], 422);
        }

        try {
            $session = $this->cashService->close(
                session: $session,
                closedBy: $user,
                countedCash: $data['counted_cash'] ?? null,
                notes: $data['notes'] ?? null,
                denominations: $data['denominations'] ?? [],
            );

            app(AuditService::class)->record(
                module: 'Cash',
                action: 'closed',
                auditable: $session,
                description: 'Caja cerrada desde Android. Esperado: RD$ '.number_format((float) $session->expected_cash, 2)
                    .', Contado: RD$ '.number_format((float) $session->counted_cash, 2),
            );

            return response()->json([
                'session' => $this->formatCashSession($session),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function formatCashSession(CashSession $session): array
    {
        return [
            'id' => $session->id,
            'uuid' => $session->uuid,
            'status' => $session->status,
            'opening_amount' => (string) $session->opening_amount,
            'expected_cash' => (string) $session->expected_cash,
            'counted_cash' => $session->counted_cash !== null ? (string) $session->counted_cash : null,
            'sales_total' => (string) $session->sales_total,
            'cancellations_total' => (string) $session->cancellations_total,
            'prizes_paid_total' => (string) $session->prizes_paid_total,
            'cash_in_total' => (string) $session->cash_in_total,
            'cash_out_total' => (string) $session->cash_out_total,
            'expenses_total' => (string) $session->expenses_total,
            'shortage_amount' => (string) $session->shortage_amount,
            'surplus_amount' => (string) $session->surplus_amount,
            'opened_at' => optional($session->opened_at)->toIso8601String(),
            'closed_at' => optional($session->closed_at)->toIso8601String(),
            'notes' => $session->notes,
        ];
    }

    // GET /api/mobile/tickets/{uuid}/winners
    public function ticketWinners(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();

        $ticket = Ticket::query()
            ->where('uuid', $uuid)
            ->where('company_id', $user->company_id)
            ->when($user->branch_id, fn ($query) => $query->where('branch_id', $user->branch_id))
            ->firstOrFail();

        $winners = WinnerTicket::query()
            ->where('ticket_id', $ticket->id)
            ->with(['betType', 'draw.lottery'])
            ->orderByDesc('prize_amount')
            ->get()
            ->map(fn (WinnerTicket $w) => [
                'id' => $w->id,
                'number_value' => $w->number_value,
                'matched_position' => $w->matched_position,
                'amount_played' => (string) $w->amount_played,
                'payout_multiplier' => (string) $w->payout_multiplier,
                'prize_amount' => (string) $w->prize_amount,
                'status' => $w->status,
                'paid_at' => optional($w->paid_at)->toIso8601String(),
                'bet_type_name' => $w->betType->name ?? '',
                'draw_name' => $w->draw->name ?? '',
                'lottery_name' => $w->draw?->lottery?->name ?? '',
            ]);

        $totalReleased = $winners
            ->where('status', 'RELEASED')
            ->sum(fn ($w) => (float) $w['prize_amount']);

        return response()->json([
            'ticket' => [
                'uuid' => $ticket->uuid,
                'ticket_number' => $ticket->ticket_number,
                'status' => $ticket->status,
                'paid_at' => optional($ticket->paid_at)->toIso8601String(),
            ],
            'winners' => $winners,
            'total_released' => number_format($totalReleased, 2, '.', ''),
            'has_releasable_prizes' => $winners->where('status', 'RELEASED')->isNotEmpty(),
        ]);
    }

    // POST /api/mobile/tickets/{uuid}/pay-prize
    public function payTicketPrize(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        $branch = $user->branch;

        if (! $branch) {
            return response()->json(['message' => 'Sin sucursal asignada.'], 422);
        }

        $ticket = Ticket::query()
            ->where('uuid', $uuid)
            ->where('company_id', $user->company_id)
            ->when($user->branch_id, fn ($query) => $query->where('branch_id', $user->branch_id))
            ->firstOrFail();

        // Si la sucursal exige control de caja, debe haber caja abierta del cajero.
        $cashSession = null;
        if ($branch->cash_control_enabled) {
            $cashSession = $this->cashService->getActiveSession($branch->id, $user->id);
            if (! $cashSession) {
                return response()->json(['message' => 'Debe abrir caja antes de pagar premios.'], 422);
            }
        }

        try {
            $payments = $this->prizeService->payReleasedWinnersForTicket(
                ticket: $ticket,
                paidBy: $user,
                cashSession: $cashSession,
            );

            $totalPaid = collect($payments)->sum(fn ($p) => (float) $p->amount);

            return response()->json([
                'ticket_uuid' => $ticket->uuid,
                'payments_count' => count($payments),
                'total_paid' => number_format($totalPaid, 2, '.', ''),
                'payments' => collect($payments)->map(fn ($p) => [
                    'id' => $p->id,
                    'winner_ticket_id' => $p->winner_ticket_id,
                    'amount' => (string) $p->amount,
                    'paid_at' => optional($p->paid_at)->toIso8601String(),
                ])->values(),
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function formatTicket(Ticket $ticket, bool $withDetails = false): array
    {
        $result = [
            'id' => $ticket->id,
            'uuid' => $ticket->uuid,
            'ticket_number' => $ticket->ticket_number,
            'total_amount' => $ticket->total_amount,
            'status' => $ticket->status,
            'sold_at' => $ticket->sold_at,
        ];

        if ($withDetails) {
            $ticket->loadMissing(['details.betType', 'details.draw.lottery']);
            $firstDetail = $ticket->details->first();

            $result['draw_id'] = $firstDetail?->draw_id;
            $result['draw_name'] = $firstDetail?->draw?->name;
            $result['lottery_name'] = $firstDetail?->draw?->lottery?->name;

            $result['details'] = $ticket->details->map(fn ($d) => [
                'id' => $d->id,
                'number_value' => $d->number_value,
                'bet_type_name' => $d->betType->name ?? '',
                'bet_type_id' => $d->bet_type_id,
                'amount' => $d->amount,
                'potential_prize' => $d->possible_prize,
            ]);
        }

        return $result;
    }
}
