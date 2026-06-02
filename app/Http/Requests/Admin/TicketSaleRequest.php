<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TicketSaleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'draw_id' => 'required|exists:draws,id',
            'terminal_key' => 'nullable|string|max:100',
            'terminal_name' => 'nullable|string|max:150',
            'plays' => 'required|array|min:1',
            'plays.*.bet_type_id' => 'required|exists:bet_types,id',
            'plays.*.number_value' => 'required|string|max:20',
            'plays.*.amount' => 'required|numeric|min:0.01|max:999999.99',
            'plays.*.position' => 'nullable|string|max:30',
        ];
    }
}
