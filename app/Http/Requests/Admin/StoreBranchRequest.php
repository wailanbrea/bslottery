<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('branches.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()?->isSuperAdmin()
            ? $this->input('company_id')
            : $this->user()?->company_id;

        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('branches')->where('company_id', $companyId)],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'in:ACTIVE,INACTIVE,SUSPENDED'],
            'can_sell_online' => ['nullable', 'boolean'],
            'can_sell_offline' => ['nullable', 'boolean'],
            'offline_max_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'offline_total_limit' => ['required', 'numeric', 'min:0'],
            'cash_control_enabled' => ['nullable', 'boolean'],
            'accounting_enabled' => ['nullable', 'boolean'],
            'payroll_enabled' => ['nullable', 'boolean'],
        ];
    }
}
