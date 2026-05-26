<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PayoutRuleStoreRequest;
use App\Http\Requests\Admin\PayoutRuleUpdateRequest;
use App\Models\BetType;
use App\Models\Branch;
use App\Models\Draw;
use App\Models\Lottery;
use App\Models\PayoutRule;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PayoutRuleController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', PayoutRule::class);

        $companyId = session('active_company_id');
        $search = $request->query('search', '');

        $rules = PayoutRule::with(['betType', 'lottery', 'branch', 'creator'])
            ->where('company_id', $companyId)
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->whereHas('betType', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->orWhereHas('lottery', fn ($q) => $q->where('name', 'like', "%{$search}%"))))
            ->orderBy('status')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        $rules->through(function (PayoutRule $rule) {
            $rule->example = $this->buildExample($rule);

            return $rule;
        });

        return view('admin.payout-rules.index', compact('rules', 'search'));
    }

    public function create(): View
    {
        Gate::authorize('create', PayoutRule::class);

        $companyId = session('active_company_id');
        $branches = Branch::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();
        $lotteries = Lottery::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();
        $betTypes = BetType::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();
        $draws = Draw::with('lottery')->where('company_id', $companyId)->where('status', 'OPEN')->orderBy('draw_date')->get();

        return view('admin.payout-rules.form', [
            'rule' => new PayoutRule,
            'branches' => $branches,
            'lotteries' => $lotteries,
            'betTypes' => $betTypes,
            'draws' => $draws,
        ]);
    }

    public function store(PayoutRuleStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', PayoutRule::class);

        $data = $request->validated();
        $data['company_id'] = session('active_company_id');
        $data['created_by'] = auth()->id();
        $data['effective_from'] ??= now();

        $rule = PayoutRule::create($data);

        app(AuditService::class)->record(
            module: 'PayoutRule',
            action: 'created',
            auditable: $rule,
            description: "Regla de pago creada. Multiplicador: {$rule->payout_multiplier}x.",
            newValues: $rule->toArray(),
        );

        return redirect()->route('admin.payout-rules.index')->with('status', 'Regla de pago creada.');
    }

    public function edit(PayoutRule $rule): View
    {
        Gate::authorize('update', $rule);

        $companyId = session('active_company_id');
        $branches = Branch::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();
        $lotteries = Lottery::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();
        $betTypes = BetType::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();
        $draws = Draw::with('lottery')->where('company_id', $companyId)->orderBy('draw_date')->get();

        return view('admin.payout-rules.form', compact('rule', 'branches', 'lotteries', 'betTypes', 'draws'));
    }

    public function update(PayoutRuleUpdateRequest $request, PayoutRule $rule): RedirectResponse
    {
        Gate::authorize('update', $rule);

        $oldValues = $rule->toArray();
        $rule->update($request->validated());

        app(AuditService::class)->record(
            module: 'PayoutRule',
            action: 'updated',
            auditable: $rule,
            description: "Regla de pago actualizada. Multiplicador: {$rule->payout_multiplier}x.",
            oldValues: $oldValues,
            newValues: $rule->toArray(),
        );

        return redirect()->route('admin.payout-rules.index')->with('status', 'Regla de pago actualizada.');
    }

    public function approve(PayoutRule $rule): RedirectResponse
    {
        Gate::authorize('update', $rule);

        $rule->update([
            'status' => 'ACTIVE',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        app(AuditService::class)->record(
            module: 'PayoutRule',
            action: 'approved',
            auditable: $rule,
            description: "Regla de pago aprobada. Multiplicador: {$rule->payout_multiplier}x.",
        );

        return redirect()->route('admin.payout-rules.index')->with('status', 'Regla de pago aprobada.');
    }

    public function copyBranch(Request $request): RedirectResponse
    {
        Gate::authorize('create', PayoutRule::class);

        $request->validate([
            'source_branch_id' => 'required|exists:branches,id',
            'target_branch_id' => 'required|exists:branches,id|different:source_branch_id',
        ]);

        $companyId = session('active_company_id');
        $sourceId = $request->input('source_branch_id');
        $targetId = $request->input('target_branch_id');

        $sourceRules = PayoutRule::where('company_id', $companyId)
            ->where('branch_id', $sourceId)
            ->where('status', 'ACTIVE')
            ->get();

        $copied = 0;
        foreach ($sourceRules as $sourceRule) {
            PayoutRule::create([
                'company_id' => $companyId,
                'branch_id' => $targetId,
                'lottery_id' => $sourceRule->lottery_id,
                'draw_id' => $sourceRule->draw_id,
                'bet_type_id' => $sourceRule->bet_type_id,
                'position' => $sourceRule->position,
                'match_type' => $sourceRule->match_type,
                'payout_multiplier' => $sourceRule->payout_multiplier,
                'effective_from' => now(),
                'status' => 'ACTIVE',
                'created_by' => auth()->id(),
            ]);
            $copied++;
        }

        app(AuditService::class)->record(
            module: 'PayoutRule',
            action: 'copy_branch',
            description: "{$copied} reglas de pago copiadas de sucursal #{$sourceId} a #{$targetId}.",
        );

        return redirect()->route('admin.payout-rules.index')->with('status', "{$copied} reglas copiadas.");
    }

    private function buildExample(PayoutRule $rule): array
    {
        $betCode = strtoupper((string) $rule->betType?->code);
        $amount = 10.00;
        $multiplier = (float) $rule->payout_multiplier;
        $payout = number_format($amount * $multiplier, 2);
        $position = $this->positionLabel($rule->position);

        return match ($betCode) {
            'QUINIELA' => [
                'trigger' => "Apuesta RD$ 10.00 al numero 25 y sale en {$position}.",
                'result' => match ($rule->position) {
                    'SECOND' => 'Resultado ejemplo: 11, 25, 90.',
                    'THIRD' => 'Resultado ejemplo: 11, 90, 25.',
                    default => 'Resultado ejemplo: 25, 11, 90.',
                },
                'payout' => "Paga RD$ {$payout}.",
            ],
            'PALE' => [
                'trigger' => match ($rule->position) {
                    'FIRST' => 'Apuesta RD$ 10.00 al pale 10-20 y ambas cifras hacen match entre primera y tercera.',
                    'SECOND' => 'Apuesta RD$ 10.00 al pale 12-34 y ambas cifras hacen match entre segunda y tercera.',
                    default => "Apuesta RD$ 10.00 al pale 12-34 y ambas cifras hacen match segun {$position}.",
                },
                'result' => match ($rule->position) {
                    'SECOND' => 'Resultado ejemplo: 88, 12, 34.',
                    'FIRST' => 'Resultado ejemplo: 10, 30, 20. Si jugaste 10-20, gana igual que un pale en primera y segunda.',
                    default => 'Resultado ejemplo: 12, 34, 77.',
                },
                'note' => match ($rule->position) {
                    'FIRST' => 'Excepcion: si jugaste 25-10 y sale 25, 10, 10, no cobra dos veces. Se paga una sola vez aunque haga match en primera-segunda y primera-tercera.',
                    default => null,
                },
                'payout' => "Paga RD$ {$payout}.",
            ],
            'TRIPLETA' => [
                'trigger' => 'Apuesta RD$ 10.00 a la tripleta 01-02-03.',
                'result' => match ($rule->position) {
                    'ANY' => 'Resultado ejemplo: 01, 02, 77. En pata hace match parcial.',
                    'EXACT' => 'Resultado ejemplo: 01, 02, 03. Hace match completo.',
                    default => 'Resultado ejemplo: 01, 02, 03.',
                },
                'payout' => "Paga RD$ {$payout}.",
            ],
            'SUPER_PALE' => [
                'trigger' => 'Apuesta RD$ 10.00 al super pale 12-34 entre dos loterias.',
                'result' => 'Resultado ejemplo: primera de loteria A = 12 y primera de loteria B = 34.',
                'payout' => "Paga RD$ {$payout}.",
            ],
            default => [
                'trigger' => "Apuesta RD$ 10.00 a esta jugada en posicion {$position}.",
                'result' => 'Si el resultado coincide con la regla configurada, la apuesta hace match.',
                'payout' => "Paga RD$ {$payout}.",
            ],
        };
    }

    private function positionLabel(?string $position): string
    {
        return match ($position) {
            'FIRST' => 'primera',
            'SECOND' => 'segunda',
            'THIRD' => 'tercera',
            'ANY' => 'cualquier posicion valida',
            'EXACT' => 'orden exacto',
            default => 'la posicion configurada',
        };
    }
}
