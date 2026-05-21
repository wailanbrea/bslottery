<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BankTransferRejectRequest;
use App\Http\Requests\Admin\BankTransferStoreRequest;
use App\Models\BankTransfer;
use App\Services\Audit\AuditService;
use App\Services\Cash\BankTransferService;
use App\Services\Cash\CashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankTransferController extends Controller
{
    public function __construct(
        private BankTransferService $bankTransferService,
        private CashService $cashService,
    ) {}

    public function index(Request $request): View
    {
        $companyId = session('active_company_id');
        $branchId = session('active_branch_id');

        $transfers = BankTransfer::with(['branch', 'user', 'verifiedBy', 'cashSession'])
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', strtoupper((string) $request->input('status'))))
            ->when($request->filled('movement_type'), fn ($q) => $q->where('movement_type', strtoupper((string) $request->input('movement_type'))))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.cash.transfers.index', [
            'transfers' => $transfers,
            'movementTypes' => BankTransferService::MOVEMENT_TYPES,
        ]);
    }

    public function create(): View|RedirectResponse
    {
        $branchId = session('active_branch_id');

        if (! $branchId) {
            return redirect()->route('admin.cash.current')->withErrors('Selecciona una sucursal activa.');
        }

        $session = $this->cashService->getActiveSession($branchId, auth()->id());

        if (! $session) {
            return redirect()->route('admin.cash.current')->withErrors('No hay caja abierta para registrar transferencias.');
        }

        return view('admin.cash.transfers.create', [
            'session' => $session,
            'movementTypes' => BankTransferService::MOVEMENT_TYPES,
        ]);
    }

    public function store(BankTransferStoreRequest $request): RedirectResponse
    {
        $branchId = session('active_branch_id');
        $session = $this->cashService->getActiveSession($branchId, auth()->id());

        if (! $session) {
            return back()->withErrors('No hay caja abierta para registrar transferencias.');
        }

        try {
            $transfer = $this->bankTransferService->createPending($session, auth()->user(), $request->validated());

            app(AuditService::class)->record(
                module: 'Cash',
                action: 'bank_transfer_created',
                auditable: $transfer,
                description: "Transferencia pendiente {$transfer->bank_name} / Ref. {$transfer->reference} por RD$ ".number_format((float) $transfer->amount, 2),
            );

            return redirect()->route('admin.cash.transfers.index')->with('status', 'Transferencia registrada como pendiente.');
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function confirm(BankTransfer $transfer): RedirectResponse
    {
        $this->ensureTransferScope($transfer);

        try {
            $transfer = $this->bankTransferService->confirm($transfer, auth()->user());

            app(AuditService::class)->record(
                module: 'Cash',
                action: 'bank_transfer_confirmed',
                auditable: $transfer,
                description: "Transferencia confirmada {$transfer->bank_name} / Ref. {$transfer->reference}.",
            );

            return redirect()->route('admin.cash.transfers.index')->with('status', 'Transferencia confirmada.');
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function reject(BankTransferRejectRequest $request, BankTransfer $transfer): RedirectResponse
    {
        $this->ensureTransferScope($transfer);

        try {
            $transfer = $this->bankTransferService->reject($transfer, auth()->user(), $request->input('notes'));

            app(AuditService::class)->record(
                module: 'Cash',
                action: 'bank_transfer_rejected',
                auditable: $transfer,
                description: "Transferencia rechazada {$transfer->bank_name} / Ref. {$transfer->reference}.",
            );

            return redirect()->route('admin.cash.transfers.index')->with('status', 'Transferencia rechazada.');
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    private function ensureTransferScope(BankTransfer $transfer): void
    {
        abort_unless($transfer->company_id === session('active_company_id'), 404);

        $branchId = session('active_branch_id');

        if ($branchId) {
            abort_unless($transfer->branch_id === $branchId, 404);
        }
    }
}
