<?php

declare(strict_types=1);

namespace App\Http\Requests\Setup;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CompleteInitialSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'rnc' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'branch_code' => ['required', 'string', 'max:50', 'alpha_dash'],
            'branch_name' => ['required', 'string', 'max:150'],
            'admin_name' => ['required', 'string', 'max:150'],
            'admin_username' => ['required', 'string', 'max:80', 'alpha_dash'],
            'admin_email' => ['nullable', 'email', 'max:150'],
            'admin_password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ];
    }
}
