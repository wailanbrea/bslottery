<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('branches.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branch = $this->route('branch');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('branches')->where('company_id', $branch?->company_id)->ignore($branch?->id),
            ],
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
