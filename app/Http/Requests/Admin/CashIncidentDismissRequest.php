<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CashIncidentDismissRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'dismiss_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
