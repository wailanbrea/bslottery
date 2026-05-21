<?php

namespace App\Http\Controllers;

use App\Models\BetType;
use App\Models\Branch;
use App\Models\Device;
use App\Models\Draw;
use App\Models\Lottery;
use App\Models\Ticket;
use App\Services\Cash\CashService;
use App\Services\Sales\TicketSaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    public function __construct(
        private TicketSaleService $saleService,
        private CashService $cashService,
    ) {}

    // Auth
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username'         => 'required|string',
            'password'         => 'required|string',
            'device_uuid'      => 'nullable|uuid',
            'device_name'      => 'nullable|string|max:100',
            'platform'         => 'nullable|string|max:50',
            'app_version'      => 'nullable|string|max:30',
            'device_fingerprint' => 'nullable|string|max:255',
        ]);

        if (! Auth::attempt(['username' => $data['username'], 'password' => $data['password']])) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $deviceStatus = null;

        if (! empty($data['device_uuid'])) {
            $device = Device::query()->firstOrNew([
                'uuid'       => $data['device_uuid'],
                'company_id' => $user->company_id,
            ]);

            if ($device->exists && $device->status === 'BLOCKED') {
                Auth::logout();

                return response()->json([
                    'message' => 'Dispositivo bloqueado. Contacte a su administrador.',
                    'code'    => 'DEVICE_BLOCKED',
                ], 403);
            }

            $device->forceFill([
                'branch_id'          => $user->branch_id,
                'user_id'            => $user->id,
                'name'               => $data['device_name'] ?? ($device->name ?? 'Android'),
                'device_type'        => 'ANDROID',
                'platform'           => $data['platform'] ?? 'ANDROID',
                'app_version'        => $data['app_version'] ?? $device->app_version,
                'device_fingerprint' => $data['device_fingerprint'] ?? $device->device_fingerprint,
                'last_seen_at'       => now(),
                'status'             => $device->exists ? $device->status : 'PENDING',
            ])->save();

            $deviceStatus = $device->status;
        }

        $token = $user->createToken('android-'.($data['device_uuid'] ?? 'web'))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'         => $user->id,
                'name'       => $user->name,
                'username'   => $user->username,
                'company_id' => $user->company_id,
                'branch_id'  => $user->branch_id,
            ],
            'device_status' => $deviceStatus,
        ]);
    }

    // Lotteries
    public function lotteries(): JsonResponse
    {
        $companyId = $this->resolveCompanyId();
        $lotteries = Lottery::when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'ACTIVE')
            ->get();

        return response()->json($lotteries);
    }

    // Draws
    public function draws(): JsonResponse
    {
        $companyId = $this->resolveCompanyId();
        $draws = Draw::with('lottery')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('draw_date', '>=', now()->toDateString())
            ->orderBy('draw_date')
            ->orderBy('scheduled_time')
            ->get();

        return response()->json($draws);
    }

    // Bet Types
    public function betTypes(): JsonResponse
    {
        $companyId = $this->resolveCompanyId();
        $types = BetType::when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'ACTIVE')
            ->get();

        return response()->json($types);
    }

    private function resolveCompanyId(): ?int
    {
        return session('active_company_id')
            ?? auth()->user()?->company_id
            ?? \App\Models\Company::first()?->id;
    }

    // Tickets list
    public function tickets(Request $request): JsonResponse
    {
        $companyId = auth()->user()?->company_id;
        $branchId = $request->query('branch_id');

        $tickets = Ticket::with('details.betType')
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('sold_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($tickets);
    }

    // Ticket detail
    public function ticket(Ticket $ticket): JsonResponse
    {
        $ticket->load('details.betType', 'details.draw.lottery');

        return response()->json($ticket);
    }

    // Preview
    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'draw_id' => 'required|exists:draws,id',
            'plays' => 'required|array|min:1',
            'plays.*.bet_type_id' => 'required|exists:bet_types,id',
            'plays.*.number_value' => 'required|string|max:20',
            'plays.*.amount' => 'required|numeric|min:0.01',
            'plays.*.position' => 'nullable|string',
        ]);

        $branch = Branch::findOrFail($data['branch_id']);
        $draw = Draw::with('lottery')->findOrFail($data['draw_id']);

        try {
            $preview = $this->saleService->preview($branch, $draw, $data['plays']);

            return response()->json($preview);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // Create ticket (sell)
    public function storeTicket(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'draw_id' => 'required|exists:draws,id',
            'plays' => 'required|array|min:1',
            'plays.*.bet_type_id' => 'required|exists:bet_types,id',
            'plays.*.number_value' => 'required|string|max:20',
            'plays.*.amount' => 'required|numeric|min:0.01',
            'plays.*.position' => 'nullable|string',
        ]);

        $branch = Branch::findOrFail($data['branch_id']);
        $draw = Draw::with('lottery')->findOrFail($data['draw_id']);
        $user = auth()->user() ?? \App\Models\User::where('company_id', $branch->company_id)->first();

        $session = $branch->cash_control_enabled
            ? $this->cashService->getActiveSession($branch->id, $user->id)
            : null;

        try {
            $ticket = $this->saleService->sell($branch, $draw, $user, $data['plays'], null, $session);
            $ticket->load('details.betType', 'details.draw.lottery');

            return response()->json($ticket, 201);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // Reprint
    public function reprint(Ticket $ticket): JsonResponse
    {
        try {
            $job = $this->saleService->reprint($ticket, auth()->user() ?? \App\Models\User::first());

            return response()->json(['message' => 'Reimpresión solicitada', 'print_job_id' => $job->id]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
