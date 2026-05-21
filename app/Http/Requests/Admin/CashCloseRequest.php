<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CashCloseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'counted_cash' => 'nullable|numeric|min:0|max:99999999.99',
            'denominations' => 'nullable|array',
            'denominations.*' => 'nullable|integer|min:0|max:100000',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
