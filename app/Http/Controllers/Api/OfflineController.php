<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BetType;
use App\Models\Draw;
use App\Models\Lottery;
use App\Models\OfflineSession;
use App\Models\PayoutRule;
use App\Models\SyncConflict;
use App\Services\Offline\OfflineSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfflineController extends Controller
{
    public function __construct(private readonly OfflineSyncService $syncService) {}

    /**
     * Bootstrap data for online operation (lotteries, draws, bet types, payout rules).
     */
    public function bootstrap(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $companyId = $user->company_id;

        $lotteries = Lottery::where('status', 'ACTIVE')
            ->where(fn ($q) => $q->where('company_id', $companyId)->orWhereNull('company_id'))
            ->get(['id', 'name', 'code', 'status']);

        $draws = Draw::with('lottery:id,name,code')
            ->where('company_id', $companyId)
            ->where('status', 'OPEN')
            ->where('draw_date', '>=', now()->toDateString())
            ->orderBy('draw_date')
            ->orderBy('scheduled_time')
            ->get(['id', 'lottery_id', 'name', 'draw_date', 'scheduled_time', 'close_time', 'status']);

        $betTypes = BetType::where('status', 'ACTIVE')
            ->where(fn ($q) => $q->where('company_id', $companyId)->orWhereNull('company_id'))
            ->get(['id', 'name', 'code', 'digits_count', 'min_numbers', 'max_numbers', 'numbers_count', 'requires_position', 'is_cross_lottery', 'max_amount']);

        $payoutRules = PayoutRule::where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->get(['id', 'bet_type_id', 'lottery_id', 'branch_id', 'multiplier', 'position']);

        return response()->json([
            'lotteries'    => $lotteries,
            'draws'        => $draws,
            'bet_types'    => $betTypes,
            'payout_rules' => $payoutRules,
            'server_time'  => now()->toISOString(),
        ]);
    }

    /**
     * Open an offline session for the device.
     */
    public function openSession(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id'      => 'required|exists:branches,id',
            'ticket_limit'   => 'nullable|integer|min:1|max:500',
            'amount_limit'   => 'nullable|numeric|min:0.01',
            'expires_hours'  => 'nullable|integer|min:1|max:24',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $branch = Branch::findOrFail($data['branch_id']);

        if ($branch->company_id !== $user->company_id) {
            return response()->json(['message' => 'Sucursal no pertenece a su empresa.'], 403);
        }

        $device = $request->attributes->get('device');

        $session = $this->syncService->openSession(
            user: $user,
            branch: $branch,
            deviceId: $device?->id,
            ticketLimit: $data['ticket_limit'] ?? 100,
            amountLimit: isset($data['amount_limit']) ? (string) $data['amount_limit'] : null,
            expiresInHours: $data['expires_hours'] ?? 8,
        );

        return response()->json([
            'session' => $session,
            'bootstrap' => $this->syncService->buildBootstrap($session),
        ], 201);
    }

    /**
     * Returns full offline data package for a session.
     * Requires ?session_uuid=xxx query parameter.
     */
    public function sessionBootstrap(Request $request): JsonResponse
    {
        $session = $this->resolveSession($request);

        if (! $session->isActive()) {
            return response()->json(['message' => 'Sesión offline expirada o cerrada.'], 410);
        }

        return response()->json($this->syncService->buildBootstrap($session));
    }

    /**
     * Sync offline tickets to the server.
     * Requires session_uuid in the request body.
     */
    public function sync(Request $request): JsonResponse
    {
        $session = $this->resolveSession($request);

        if (! $session->isActive()) {
            return response()->json(['message' => 'Sesión offline expirada o cerrada.'], 410);
        }

        $data = $request->validate([
            'session_uuid'  => 'required|uuid',
            'tickets'       => 'required|array|min:1|max:200',
            'tickets.*.uuid'    => 'required|uuid',
            'tickets.*.draw_id' => 'required|integer',
            'tickets.*.sold_at' => 'nullable|date',
            'tickets.*.plays'   => 'required|array|min:1',
            'tickets.*.plays.*.bet_type_id'  => 'required|integer',
            'tickets.*.plays.*.number_value' => 'required|string|max:20',
            'tickets.*.plays.*.amount'       => 'required|numeric|min:0.01',
            'tickets.*.plays.*.position'     => 'nullable|string',
            'payload_hash'  => 'nullable|string|max:64',
        ]);

        $batch = $this->syncService->processBatch($session, $data['tickets'] ?? [], $data['payload_hash'] ?? null);

        return response()->json([
            'batch_id'         => $batch->id,
            'status'           => $batch->status,
            'total_tickets'    => $batch->total_tickets,
            'accepted_tickets' => $batch->accepted_tickets,
            'rejected_tickets' => $batch->rejected_tickets,
            'accepted_uuids'   => $batch->getAttribute('accepted_uuids') ?? [],
            'failed'           => $batch->getAttribute('failed') ?? [],
            'conflicts'        => $batch->conflicts->map(fn ($c) => [
                'id'             => $c->id,
                'ticket_uuid'    => $c->ticket_data['uuid'] ?? null,
                'conflict_type'  => $c->conflict_type,
                'conflict_reason' => $c->conflict_reason,
                'status'         => $c->status,
            ]),
        ]);
    }

    /**
     * List unresolved conflicts for the authenticated user's company.
     */
    public function conflicts(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $conflicts = SyncConflict::with(['syncBatch', 'offlineSession.user', 'branch'])
            ->where('company_id', $user->company_id)
            ->when($user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->where('status', 'PENDING')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json($conflicts);
    }

    /**
     * Resolve a conflict (accept or reject the offline ticket).
     */
    public function resolveConflict(Request $request, SyncConflict $conflict): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($conflict->company_id !== $user->company_id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $data = $request->validate([
            'action'     => 'required|in:accept,reject',
            'resolution' => 'nullable|string|max:500',
        ]);

        $conflict = $this->syncService->resolveConflict($conflict, $user, $data['action'], $data['resolution'] ?? null);

        return response()->json($conflict);
    }

    private function resolveSession(Request $request): OfflineSession
    {
        $uuid = $request->query('session_uuid') ?? $request->input('session_uuid');

        if (! $uuid) {
            abort(422, 'Se requiere session_uuid.');
        }

        /** @var \App\Models\User $user */
        $user = $request->user();

        $session = OfflineSession::where('uuid', $uuid)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        return $session;
    }
}
