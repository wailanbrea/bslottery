<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LimitRuleStoreRequest;
use App\Http\Requests\Admin\LimitRuleUpdateRequest;
use App\Models\BetType;
use App\Models\Branch;
use App\Models\Draw;
use App\Models\LimitConsumption;
use App\Models\LimitRule;
use App\Models\Lottery;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LimitRuleController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', LimitRule::class);

        $companyId = session('active_company_id');
        $search = $request->query('search', '');

        $rules = LimitRule::with(['betType', 'lottery', 'branch', 'creator'])
            ->where('company_id', $companyId)
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('number_value', 'like', "%{$search}%")->orWhere('number_from', 'like', "%{$search}%")->orWhereHas('branch', fn ($q) => $q->where('name', 'like', "%{$search}%"))))
            ->orderBy('status')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.limit-rules.index', compact('rules', 'search'));
    }

    public function create(): View
    {
        Gate::authorize('create', LimitRule::class);

        $companyId = session('active_company_id');
        $branches = Branch::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();
        $lotteries = Lottery::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();
        $betTypes = BetType::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();
        $draws = Draw::with('lottery')->where('company_id', $companyId)->where('status', 'OPEN')->orderBy('draw_date')->get();

        return view('admin.limit-rules.form', [
            'rule' => new LimitRule,
            'branches' => $branches,
            'lotteries' => $lotteries,
            'betTypes' => $betTypes,
            'draws' => $draws,
        ]);
    }

    public function store(LimitRuleStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', LimitRule::class);

        $data = $request->validated();
        $data['company_id'] = session('active_company_id');
        $data['created_by'] = auth()->id();
        $data['effective_from'] ??= now();

        if ($data['rule_type'] === 'NUMBER_LIST') {
            $data['numbers_json'] = array_filter(array_map('trim', explode(',', $request->input('numbers_input', ''))));
        }

        $rule = LimitRule::create($data);

        app(AuditService::class)->record(
            module: 'LimitRule',
            action: 'created',
            auditable: $rule,
            description: "Regla de límite creada. Tipo: {$rule->rule_type}, máximo: RD\${$rule->max_amount_per_number}.",
            newValues: $rule->toArray(),
        );

        return redirect()->route('admin.limit-rules.index')->with('status', 'Regla de límite creada.');
    }

    public function edit(LimitRule $rule): View
    {
        Gate::authorize('update', $rule);

        $companyId = session('active_company_id');
        $branches = Branch::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();
        $lotteries = Lottery::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();
        $betTypes = BetType::where('company_id', $companyId)->where('status', 'ACTIVE')->orderBy('name')->get();
        $draws = Draw::with('lottery')->where('company_id', $companyId)->orderBy('draw_date')->get();

        return view('admin.limit-rules.form', compact('rule', 'branches', 'lotteries', 'betTypes', 'draws'));
    }

    public function update(LimitRuleUpdateRequest $request, LimitRule $rule): RedirectResponse
    {
        Gate::authorize('update', $rule);

        $oldValues = $rule->toArray();
        $data = $request->validated();

        if ($data['rule_type'] === 'NUMBER_LIST') {
            $data['numbers_json'] = array_filter(array_map('trim', explode(',', $request->input('numbers_input', ''))));
        }

        $rule->update($data);

        app(AuditService::class)->record(
            module: 'LimitRule',
            action: 'updated',
            auditable: $rule,
            description: "Regla de límite actualizada. Tipo: {$rule->rule_type}.",
            oldValues: $oldValues,
            newValues: $rule->toArray(),
        );

        return redirect()->route('admin.limit-rules.index')->with('status', 'Regla de límite actualizada.');
    }

    public function copyBranch(Request $request): RedirectResponse
    {
        Gate::authorize('create', LimitRule::class);

        $request->validate([
            'source_branch_id' => 'required|exists:branches,id',
            'target_branch_id' => 'required|exists:branches,id|different:source_branch_id',
        ]);

        $companyId = session('active_company_id');
        $sourceId = $request->input('source_branch_id');
        $targetId = $request->input('target_branch_id');

        $sourceRules = LimitRule::where('company_id', $companyId)
            ->where('branch_id', $sourceId)
            ->where('status', 'ACTIVE')
            ->get();

        $copied = 0;
        foreach ($sourceRules as $sourceRule) {
            LimitRule::create([
                'company_id' => $companyId,
                'branch_id' => $targetId,
                'lottery_id' => $sourceRule->lottery_id,
                'draw_id' => $sourceRule->draw_id,
                'bet_type_id' => $sourceRule->bet_type_id,
                'rule_type' => $sourceRule->rule_type,
                'number_value' => $sourceRule->number_value,
                'number_from' => $sourceRule->number_from,
                'number_to' => $sourceRule->number_to,
                'numbers_json' => $sourceRule->numbers_json,
                'max_amount_per_number' => $sourceRule->max_amount_per_number,
                'max_total_amount' => $sourceRule->max_total_amount,
                'policy' => $sourceRule->policy,
                'effective_from' => now(),
                'status' => 'ACTIVE',
                'created_by' => auth()->id(),
            ]);
            $copied++;
        }

        app(AuditService::class)->record(
            module: 'LimitRule',
            action: 'copy_branch',
            description: "{$copied} reglas de límite copiadas de sucursal #{$sourceId} a #{$targetId}.",
        );

        return redirect()->route('admin.limit-rules.index')->with('status', "{$copied} reglas copiadas.");
    }

    public function consumptions(Request $request): View
    {
        Gate::authorize('viewAny', LimitRule::class);

        $companyId = session('active_company_id');
        $search = $request->query('search', '');

        $consumptions = LimitConsumption::with(['draw', 'betType', 'branch'])
            ->where('company_id', $companyId)
            ->when($search, fn ($q) => $q->where('number_value', 'like', "%{$search}%"))
            ->orderBy('draw_id', 'desc')
            ->orderBy('number_value')
            ->paginate(30)
            ->appends($request->query());

        return view('admin.limit-rules.consumptions', compact('consumptions', 'search'));
    }
}
