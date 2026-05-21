<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CalculateWinnersJob;
use App\Jobs\ReleasePaymentsJob;
use App\Models\Draw;
use App\Models\Lottery;
use App\Models\PaymentAuthorization;
use App\Models\Result;
use App\Models\SystemSetting;
use App\Models\WinnerTicket;
use App\Services\Audit\AuditService;
use App\Services\Results\WinnerCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function __construct(
        private WinnerCalculationService $winnerCalculator,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Result::class);

        $companyId = session('active_company_id');

        $results = Result::with(['draw.lottery', 'registeredBy', 'confirmedBy'])
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.results.index', compact('results'));
    }

    public function create(): View
    {
        Gate::authorize('create', Result::class);

        $companyId = session('active_company_id');
        $lotteries = Lottery::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();

        return view('admin.results.form', [
            'result' => new Result,
            'lotteries' => $lotteries,
            'draws' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Result::class);

        $data = $request->validate([
            'lottery_id' => 'required|exists:lotteries,id',
            'draw_id' => 'required|exists:draws,id',
            'first_number' => 'required|string|max:10',
            'second_number' => 'nullable|string|max:10',
            'third_number' => 'nullable|string|max:10',
        ]);

        $draw = Draw::findOrFail($data['draw_id']);

        if ($draw->lottery_id != $data['lottery_id']) {
            return back()->withErrors('El sorteo no pertenece a esa lotería.');
        }

        $existing = Result::where('draw_id', $draw->id)->first();
        if ($existing) {
            return back()->withErrors('Ya existe un resultado para este sorteo.');
        }

        $requiresConfirmation = SystemSetting::getBoolean($draw->company_id, 'results.require_confirmation', true);
        $now = now();

        $result = Result::create([
            'company_id' => $draw->company_id,
            'lottery_id' => $draw->lottery_id,
            'draw_id' => $draw->id,
            'first_number' => $data['first_number'],
            'second_number' => $data['second_number'] ?? null,
            'third_number' => $data['third_number'] ?? null,
            'status' => $requiresConfirmation ? 'REGISTERED' : 'CONFIRMED',
            'registered_by' => auth()->id(),
            'registered_at' => $now,
            'confirmed_by' => $requiresConfirmation ? null : auth()->id(),
            'confirmed_at' => $requiresConfirmation ? null : $now,
            'confirmation_notes' => $requiresConfirmation ? null : 'Confirmacion automatica por configuracion de empresa.',
        ]);

        $draw->update([
            'status' => $requiresConfirmation ? 'RESULT_REGISTERED' : 'RESULT_CONFIRMED',
            'result_registered_at' => $now,
            'result_confirmed_at' => $requiresConfirmation ? null : $now,
        ]);

        app(AuditService::class)->record(
            module: 'Results',
            action: 'registered',
            auditable: $result,
            description: "Resultado registrado: {$result->first_number} {$result->second_number} {$result->third_number}",
        );

        return redirect()
            ->route('admin.results.index')
            ->with('status', $requiresConfirmation ? 'Resultado registrado pendiente de confirmacion.' : 'Resultado registrado y confirmado automaticamente.');
    }

    public function confirm(Result $result): RedirectResponse
    {
        Gate::authorize('update', $result);

        if ($result->status !== 'REGISTERED') {
            return back()->withErrors('Solo se puede confirmar un resultado en estado REGISTERED.');
        }

        if ($result->registered_by === auth()->id()) {
            return back()->withErrors('El mismo usuario no puede registrar y confirmar el resultado.');
        }

        $result->update([
            'status' => 'CONFIRMED',
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);

        $result->draw->update(['status' => 'RESULT_CONFIRMED', 'result_confirmed_at' => now()]);

        app(AuditService::class)->record(
            module: 'Results',
            action: 'confirmed',
            auditable: $result,
            description: "Resultado confirmado: {$result->first_number} {$result->second_number} {$result->third_number}",
        );

        return redirect()->route('admin.results.index')->with('status', 'Resultado confirmado.');
    }

    public function calculateWinners(Draw $draw): RedirectResponse
    {
        Gate::authorize('calculateWinners', Result::class);

        try {
            $previousStatus = $draw->status;
            $draw->update(['status' => 'CALCULATING_WINNERS']);
            CalculateWinnersJob::dispatch($draw->id, auth()->id());

            return redirect()->route('admin.results.index')
                ->with('status', 'Cálculo de ganadores enviado a cola.');
        } catch (\RuntimeException $e) {
            $draw->refresh();
            if ($draw->status === 'CALCULATING_WINNERS') {
                $draw->update(['status' => $previousStatus ?? 'RESULT_CONFIRMED']);
            }

            return back()->withErrors($e->getMessage());
        }
    }

    public function authorizePayments(Draw $draw, Request $request): RedirectResponse
    {
        Gate::authorize('authorizePayments', Result::class);

        // Validamos sincronamente que existe una autorizacion pendiente antes
        // de poner el job en cola; asi devolvemos el error 4xx inmediatamente
        // en lugar de fallar silenciosamente en el worker.
        $hasPending = PaymentAuthorization::where('draw_id', $draw->id)
            ->where('status', 'PENDING')
            ->exists();

        if (! $hasPending) {
            return back()->withErrors('No hay autorización pendiente para este sorteo.');
        }

        ReleasePaymentsJob::dispatch($draw->id, auth()->id(), $request->input('notes'));

        return redirect()->route('admin.results.index')
            ->with('status', 'Autorización enviada a cola. Los pagos se liberarán en segundos.');
    }
}
