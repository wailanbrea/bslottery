<?php

declare(strict_types=1);

namespace App\Http\Requests\Licensing;

use Illuminate\Foundation\Http\FormRequest;

class ActivateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'activation_code' => ['required', 'string', 'max:120'],
            'client_location_code' => ['nullable', 'string', 'max:100'],
        ];
    }
}
