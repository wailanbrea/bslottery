<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketPreviewRequest;
use App\Http\Requests\Admin\TicketSaleRequest;
use App\Models\BetType;
use App\Models\Branch;
use App\Models\Draw;
use App\Models\Ticket;
use App\Services\Cash\CashService;
use App\Services\Lottery\DrawGenerationService;
use App\Services\Lottery\LimitValidationService;
use App\Services\Sales\TicketSaleService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(
        private TicketSaleService $saleService,
        private CashService $cashService,
        private LimitValidationService $limitValidator,
        private DrawGenerationService $drawGenerationService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Ticket::class);

        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');
        $search = $request->query('search', '');

        $tickets = Ticket::with(['user', 'branch', 'details.betType', 'details.draw.lottery'])
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($search, fn ($q) => $q->where('ticket_number', 'like', "%{$search}%"))
            ->orderBy('sold_at', 'desc')
            ->paginate(25)
            ->appends($request->query());

        return view('admin.tickets.index', compact('tickets', 'search'));
    }

    public function create(): View
    {
        Gate::authorize('create', Ticket::class);

        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');

        if (! $branchId) {
            return view('admin.tickets.no-branch');
        }

        $branch = Branch::findOrFail($branchId);
        $this->drawGenerationService->generateForCompany((int) $companyId, days: 2);

        $session = $this->cashService->getActiveSession($branchId, auth()->id());
        $draws = Draw::with('lottery')
            ->where('company_id', $companyId)
            ->where('status', 'OPEN')
            ->whereHas('lottery', fn ($q) => $q->where('status', 'ACTIVE'))
            ->whereDate('draw_date', now()->toDateString())
            ->orderBy('draw_date')
            ->orderBy('scheduled_time')
            ->get();

        return view('admin.tickets.sale', compact('branch', 'session', 'draws'));
    }

    public function preview(TicketPreviewRequest $request): View
    {
        Gate::authorize('create', Ticket::class);

        $branch = Branch::findOrFail($request->input('branch_id'));
        $draw = Draw::with('lottery')->findOrFail($request->input('draw_id'));

        $plays = $request->input('plays', []);

        try {
            $preview = $this->saleService->preview($branch, $draw, $plays);
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage())->withInput();
        }

        return view('admin.tickets.preview', compact('branch', 'draw', 'preview', 'plays'));
    }

    public function store(TicketSaleRequest $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', Ticket::class);

        $branch = Branch::findOrFail($request->input('branch_id'));
        $draw = Draw::with('lottery')->findOrFail($request->input('draw_id'));
        $user = auth()->user();
        $plays = $request->input('plays', []);

        $branchId = $branch->id;
        $session = $branch->cash_control_enabled
            ? $this->cashService->getActiveSession($branchId, $user->id)
            : null;

        try {
            $ticket = $this->saleService->sell(
                $branch,
                $draw,
                $user,
                $plays,
                null,
                $session,
                $request->input('terminal_key')
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Ticket #{$ticket->ticket_number} vendido.",
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'show_url' => route('admin.tickets.show', $ticket),
                    'total_amount' => (string) $ticket->total_amount,
                ], 201);
            }

            return redirect()->route('admin.tickets.show', $ticket)
                ->with('status', "Ticket #{$ticket->ticket_number} vendido. Total: RD\$ ".number_format($ticket->total_amount, 2));
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors($e->getMessage())->withInput();
        } catch (\Throwable $e) {
            report($e);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Ocurrio un error inesperado al vender el ticket.',
                    'detail' => app()->isLocal() ? $e->getMessage() : null,
                ], 500);
            }

            return back()->withErrors('Ocurrio un error inesperado al vender el ticket.')->withInput();
        }
    }

    public function show(Ticket $ticket): View
    {
        Gate::authorize('view', $ticket);

        $ticket->load([
            'details.betType',
            'details.draw.lottery',
            'winnerTickets.ticketDetail.betType',
            'winnerTickets.draw.lottery',
            'user',
            'branch',
            'cancelledBy',
            'printJobs' => fn ($q) => $q->orderBy('created_at', 'desc'),
        ]);

        return view('admin.tickets.show', compact('ticket'));
    }

    /**
     * Consulta disponibilidad de límite para un número antes de agregar la jugada.
     */
    public function checkLimit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'draw_id' => 'required|exists:draws,id',
            'bet_type_id' => 'required|exists:bet_types,id',
            'number_value' => 'required|string|max:20',
        ]);

        $branchId = session('active_branch_id');
        if (! $branchId) {
            return response()->json(['available' => null, 'max_bet' => null, 'blocked' => false]);
        }

        $draw = Draw::findOrFail($data['draw_id']);
        $betType = BetType::findOrFail($data['bet_type_id']);
        $branch = Branch::findOrFail($branchId);
        $numberValue = str_pad($data['number_value'], $betType->digits_count, '0', STR_PAD_LEFT);

        $available = $this->limitValidator->getAvailableForNumber($branch, $draw, $betType, $numberValue);

        return response()->json([
            'available' => $available,
            'max_bet' => $betType->max_amount !== null ? (float) $betType->max_amount : null,
            'blocked' => $available !== null && $available <= 0,
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Ticket::class);

        $data = $request->validate([
            'token' => 'required|string|max:255',
        ]);

        $token = $this->normalizeLookupToken($data['token']);
        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');

        $ticket = Ticket::with([
            'branch',
            'user',
            'details.betType',
            'details.draw.lottery',
            'winnerTickets.ticketDetail.betType',
            'winnerTickets.draw.lottery',
        ])
            ->where('company_id', $companyId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where(function ($query) use ($token): void {
                $query->where('ticket_number', $token)
                    ->orWhere('uuid', $token);
            })
            ->first();

        if (! $ticket) {
            return response()->json([
                'message' => 'Ticket no encontrado en la empresa/sucursal activa.',
            ], 404);
        }

        Gate::authorize('view', $ticket);

        $releasedPrizeTotal = '0.00';
        foreach ($ticket->winnerTickets->where('status', 'RELEASED') as $winner) {
            $releasedPrizeTotal = Money::add($releasedPrizeTotal, $winner->prize_amount);
        }

        return response()->json([
            'ticket' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'uuid' => $ticket->uuid,
                'status' => $ticket->status,
                'branch_name' => $ticket->branch?->name,
                'cashier' => $ticket->user?->username,
                'sold_at' => $ticket->sold_at?->format('Y-m-d H:i:s'),
                'total_amount' => (string) $ticket->total_amount,
                'total_possible_prize' => (string) $ticket->total_possible_prize,
                'show_url' => route('admin.tickets.show', $ticket),
                'pay_url' => route('admin.tickets.pay-released-prizes', $ticket),
                'released_prize_total' => $releasedPrizeTotal,
                'can_pay_released_prizes' => Money::isPositive($releasedPrizeTotal) && $request->user()?->hasPermission('prizes.pay'),
            ],
            'copy_plays' => $ticket->details
                ->whereNotIn('status', ['CANCELLED'])
                ->map(fn ($detail): array => [
                    'bet_type_id' => $detail->bet_type_id,
                    'bet_type_code' => $detail->betType?->code,
                    'number_value' => $detail->number_value,
                    'amount' => (float) $detail->amount,
                    'lottery_name' => $detail->draw?->lottery?->name,
                    'draw_name' => $detail->draw?->name,
                ])
                ->values(),
            'winners' => $ticket->winnerTickets
                ->map(fn ($winner): array => [
                    'id' => $winner->id,
                    'status' => $winner->status,
                    'number_value' => $winner->number_value,
                    'bet_type' => $winner->betType?->name,
                    'draw' => trim(($winner->draw?->lottery?->name ?? '').' '.($winner->draw?->name ?? '')),
                    'prize_amount' => (string) $winner->prize_amount,
                    'can_pay' => $winner->status === 'RELEASED' && $request->user()?->hasPermission('prizes.pay'),
                    'pay_url' => route('admin.prizes.pay', $winner),
                ])
                ->values(),
        ]);
    }

    public function cancel(Ticket $ticket, Request $request): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        $request->validate(['cancel_reason' => 'required|string|max:500']);

        try {
            $this->saleService->cancel($ticket, auth()->user(), $request->input('cancel_reason'));

            return redirect()->route('admin.tickets.show', $ticket)
                ->with('status', 'Ticket anulado.');
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function reprint(Request $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $ticket);

        try {
            $job = $this->saleService->reprint($ticket, auth()->user(), $request->input('terminal_key'));

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Reimpresión solicitada.',
                    'job' => [
                        'id' => $job->id,
                        'uuid' => $job->uuid,
                        'status' => $job->status,
                    ],
                ]);
            }

            return redirect()->route('admin.tickets.show', $ticket)
                ->with('status', 'Reimpresión solicitada.');
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors($e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Ocurrio un error inesperado al reimprimir el ticket.',
                    'detail' => app()->isLocal() ? $e->getMessage() : null,
                ], 500);
            }

            return back()->withErrors('Ocurrio un error inesperado al reimprimir el ticket.');
        }
    }

    private function normalizeLookupToken(string $token): string
    {
        $token = trim($token);

        if (str_starts_with($token, '{')) {
            $decoded = json_decode($token, true);

            if (is_array($decoded)) {
                $token = (string) ($decoded['ticket_number'] ?? $decoded['ticket'] ?? $decoded['uuid'] ?? $token);
            }
        }

        if (preg_match('/(?:ticket_number|ticket|uuid)=([^&\s]+)/i', $token, $matches)) {
            return urldecode($matches[1]);
        }

        if (preg_match('/(?:tickets\/|ticket:|uuid:)([A-Za-z0-9\-]+)/i', $token, $matches)) {
            return $matches[1];
        }

        return $token;
    }
}
