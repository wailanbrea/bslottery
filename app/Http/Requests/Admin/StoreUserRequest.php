<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.create') ?? false;
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
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:80', 'alpha_dash', Rule::unique('users')->where('company_id', $companyId)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users')->where('company_id', $companyId)],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'status' => ['required', 'in:ACTIVE,INACTIVE,BLOCKED'],
        ];
    }
}
