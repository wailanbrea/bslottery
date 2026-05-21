<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $target = $this->route('user');

        return [
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:150'],
            'username' => [
                'required',
                'string',
                'max:80',
                'alpha_dash',
                Rule::unique('users')->where('company_id', $target?->company_id)->ignore($target?->id),
            ],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users')->where('company_id', $target?->company_id)->ignore($target?->id)],
            'password' => ['nullable', 'confirmed', Password::min(10)->letters()->numbers()],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'status' => ['required', 'in:ACTIVE,INACTIVE,BLOCKED'],
        ];
    }
}
