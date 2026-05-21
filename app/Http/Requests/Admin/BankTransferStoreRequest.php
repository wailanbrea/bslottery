<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankTransferStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'movement_type' => ['required', Rule::in(['SALE', 'PRIZE_PAYMENT', 'CASH_IN', 'CASH_OUT', 'EXPENSE'])],
            'bank_name' => ['required', 'string', 'max:120'],
            'reference' => [
                'required',
                'string',
                'max:120',
                Rule::unique('bank_transfers', 'reference')->where('company_id', session('active_company_id')),
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'transferred_at' => ['nullable', 'date'],
            'evidence_path' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
