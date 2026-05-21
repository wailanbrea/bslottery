<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CashOpenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'opening_amount' => 'required|numeric|min:0|max:99999999.99',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
