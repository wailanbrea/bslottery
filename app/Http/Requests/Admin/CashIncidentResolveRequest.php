<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CashIncidentResolveRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'resolution_notes' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
