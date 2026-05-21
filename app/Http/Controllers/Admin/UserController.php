<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Licensing\LicenseLimitManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->with(['company', 'branch', 'role'])->orderBy('name');
        $this->applyScope($request, $query);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return view('admin.users.index', [
            'users' => $query->paginate(15)->withQueryString(),
            'search' => $search ?? '',
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.form', [
            'targetUser' => new User(['status' => 'ACTIVE']),
            'companies' => $this->companiesFor($request),
            'branches' => $this->branchesFor($request),
            'roles' => $this->rolesFor($request),
        ]);
    }

    public function store(StoreUserRequest $request, LicenseLimitManager $limits, AuditService $audit): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $data = $request->validated();
        $data['company_id'] = $request->user()->isSuperAdmin() ? $data['company_id'] : $request->user()->company_id;

        $this->assertBranchBelongsToCompany($data['branch_id'] ?? null, $data['company_id']);
        $this->assertUserLimit($data['company_id'], $limits);

        $user = DB::transaction(function () use ($data): User {
            $employee = Employee::query()->create([
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'name' => $data['name'],
                'position' => 'Staff',
                'status' => 'ACTIVE',
            ]);

            $user = User::query()->create([
                ...$data,
                'employee_id' => $employee->id,
                'must_change_password' => true,
            ]);

            $employee->forceFill(['user_id' => $user->id])->save();

            return $user;
        });

        $audit->record('users', 'create', 'Usuario creado.', $user, newValues: $user->toArray());

        return redirect()->route('admin.users.index')->with('status', 'Usuario creado.');
    }

    public function edit(Request $request, User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.form', [
            'targetUser' => $user,
            'companies' => $this->companiesFor($request),
            'branches' => $this->branchesFor($request),
            'roles' => $this->rolesFor($request),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, AuditService $audit): RedirectResponse
    {
        Gate::authorize('update', $user);

        $data = $request->validated();
        $this->assertBranchBelongsToCompany($data['branch_id'] ?? null, $user->company_id);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['must_change_password'] = true;
            $data['password_changed_at'] = null;
        }

        $old = $user->toArray();
        $user->update($data);
        $user->employee?->update([
            'branch_id' => $data['branch_id'] ?? null,
            'name' => $data['name'],
        ]);

        $audit->record('users', 'update', 'Usuario actualizado.', $user, oldValues: $old, newValues: $user->fresh()->toArray());

        return redirect()->route('admin.users.index')->with('status', 'Usuario actualizado.');
    }

    private function applyScope(Request $request, $query): void
    {
        if (! $request->user()->isSuperAdmin()) {
            $query->where('company_id', $request->user()->company_id);
        }

        if ($request->user()->branch_id) {
            $query->where('branch_id', $request->user()->branch_id);
        }
    }

    private function companiesFor(Request $request)
    {
        $query = Company::query()->orderBy('name');

        if (! $request->user()->isSuperAdmin()) {
            $query->whereKey($request->user()->company_id);
        }

        return $query->get();
    }

    private function branchesFor(Request $request)
    {
        $query = Branch::query()->orderBy('code');

        if (! $request->user()->isSuperAdmin()) {
            $query->where('company_id', $request->user()->company_id);
        }

        if ($request->user()->branch_id) {
            $query->whereKey($request->user()->branch_id);
        }

        return $query->get();
    }

    private function rolesFor(Request $request)
    {
        $query = Role::query()->orderByDesc('level')->orderBy('name');

        if (! $request->user()->isSuperAdmin()) {
            $query->where('slug', '!=', 'SUPER_ADMIN');
        }

        return $query->get();
    }

    private function assertBranchBelongsToCompany(?int $branchId, int $companyId): void
    {
        if (! $branchId) {
            return;
        }

        $valid = Branch::query()
            ->whereKey($branchId)
            ->where('company_id', $companyId)
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'branch_id' => 'La sucursal no pertenece a la empresa seleccionada.',
            ]);
        }
    }

    private function assertUserLimit(int $companyId, LicenseLimitManager $limits): void
    {
        $maxUsers = $limits->integer('max_users', 0);

        if ($maxUsers <= 0) {
            return;
        }

        $currentUsers = User::query()->where('company_id', $companyId)->count();

        if ($currentUsers >= $maxUsers) {
            throw ValidationException::withMessages([
                'name' => 'La licencia alcanzó el límite de usuarios permitidos.',
            ]);
        }
    }
}

