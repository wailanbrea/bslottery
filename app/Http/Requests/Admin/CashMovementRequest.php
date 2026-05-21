<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CashMovementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => 'required|in:EXPENSE,CASH_IN,CASH_OUT,ADJUSTMENT',
            'amount' => 'required|numeric|min:0.01|max:99999999.99',
            'direction' => 'required|in:IN,OUT',
            'description' => 'required|string|max:500',
        ];
    }
}
