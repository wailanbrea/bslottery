<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CashFundingTransferRequest;
use App\Models\CashFundingTransfer;
use App\Models\CashSession;
use App\Services\Audit\AuditService;
use App\Services\Cash\CashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashFundingTransferController extends Controller
{
    public function __construct(
        private CashService $cashService,
    ) {}

    public function index(Request $request): View
    {
        $companyId = (int) session('active_company_id');
        $branchId = session('active_branch_id');

        $transfers = CashFundingTransfer::with(['branch', 'cashSession.user', 'createdBy'])
            ->where('company_id', $companyId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.cash.funding.index', compact('transfers'));
    }

    public function create(): View
    {
        $companyId = (int) session('active_company_id');

        $sessions = CashSession::with(['branch', 'user'])
            ->where('company_id', $companyId)
            ->where('status', 'OPEN')
            ->orderByDesc('opened_at')
            ->get();

        return view('admin.cash.funding.create', compact('sessions'));
    }

    public function store(CashFundingTransferRequest $request, AuditService $audit): RedirectResponse
    {
        $companyId = (int) session('active_company_id');
        $session = CashSession::query()
            ->where('company_id', $companyId)
            ->where('status', 'OPEN')
            ->findOrFail($request->integer('cash_session_id'));

        try {
            $transfer = $this->cashService->fundCashSession(
                session: $session,
                user: $request->user(),
                amount: $request->input('amount'),
                source: $request->input('source'),
                reference: $request->input('reference'),
                notes: $request->input('notes'),
            );

            $audit->record(
                module: 'Cash',
                action: 'funding_transfer',
                auditable: $transfer,
                description: "Refuerzo de caja #{$session->id} por RD$ ".number_format((float) $transfer->amount, 2).'.',
                newValues: $transfer->only(['company_id', 'branch_id', 'cash_session_id', 'amount', 'source', 'reference']),
            );

            return redirect()
                ->route('admin.cash.funding.index')
                ->with('status', 'Refuerzo de caja registrado.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors($e->getMessage());
        }
    }
}
