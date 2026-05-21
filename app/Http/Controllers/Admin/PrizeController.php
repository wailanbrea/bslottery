<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\WinnerTicket;
use App\Services\Cash\CashService;
use App\Services\Results\PrizePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PrizeController extends Controller
{
    public function __construct(
        private PrizePaymentService $prizeService,
        private CashService $cashService,
    ) {}

    public function pending(Request $request): View
    {
        Gate::authorize('viewAny', WinnerTicket::class);

        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');

        $winners = WinnerTicket::with(['ticket', 'ticketDetail.betType', 'ticketDetail.draw.lottery', 'branch'])
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereIn('status', ['RELEASED', 'PENDING_RELEASE'])
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->appends($request->query());

        return view('admin.prizes.pending', compact('winners'));
    }

    public function pay(WinnerTicket $winner): RedirectResponse
    {
        Gate::authorize('update', $winner);

        $branchId = $winner->branch_id;
        $session = $this->cashService->getActiveSession($branchId, auth()->id());

        try {
            $this->prizeService->pay($winner, auth()->user(), $session);

            $route = request('return_to') === 'sales' ? 'admin.tickets.create' : 'admin.prizes.pending';

            return redirect()->route($route)
                ->with('status', "Premio pagado: RD\$ " . number_format($winner->prize_amount, 2));
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function payTicket(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('pay', $ticket);

        $branchId = $ticket->branch_id;
        $session = $this->cashService->getActiveSession($branchId, auth()->id());

        try {
            $payments = $this->prizeService->payReleasedWinnersForTicket($ticket, auth()->user(), $session);
            $totalPaid = collect($payments)->sum(fn ($payment) => (float) $payment->amount);

            $route = request('return_to') === 'sales' ? 'admin.tickets.create' : 'admin.tickets.show';

            return redirect()->route($route, $route === 'admin.tickets.show' ? $ticket : [])
                ->with('status', "Ticket pagado: RD\$ ".number_format($totalPaid, 2));
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function history(Request $request): View
    {
        Gate::authorize('viewAny', WinnerTicket::class);

        $companyId = session('active_company_id');

        $payments = \App\Models\PrizePayment::with(['winnerTicket.ticketDetail.betType', 'winnerTicket.ticketDetail.draw.lottery', 'paidBy', 'branch'])
            ->where('company_id', $companyId)
            ->orderBy('paid_at', 'desc')
            ->paginate(25)
            ->appends($request->query());

        return view('admin.prizes.history', compact('payments'));
    }
}
