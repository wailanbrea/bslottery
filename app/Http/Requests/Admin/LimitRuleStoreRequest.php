<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LimitRuleStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'branch_id' => 'nullable|exists:branches,id',
            'lottery_id' => 'nullable|exists:lotteries,id',
            'draw_id' => 'nullable|exists:draws,id',
            'bet_type_id' => 'nullable|exists:bet_types,id',
            'rule_type' => 'required|in:GLOBAL,SINGLE_NUMBER,NUMBER_RANGE,NUMBER_LIST',
            'scope' => 'required|in:PER_BRANCH,COMPANY',
            'number_value' => 'nullable|string|max:10|required_if:rule_type,SINGLE_NUMBER',
            'number_from' => 'nullable|string|max:10|required_if:rule_type,NUMBER_RANGE',
            'number_to' => 'nullable|string|max:10|required_if:rule_type,NUMBER_RANGE',
            'numbers_input' => 'nullable|string|required_if:rule_type,NUMBER_LIST',
            'max_amount_per_number' => 'required|numeric|min:0.01|max:9999999999.99',
            'max_total_amount' => 'nullable|numeric|min:0.01|max:9999999999.99',
            'policy' => 'required|in:BLOCK_FULL,ALLOW_AVAILABLE,REQUEST_AUTHORIZATION',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'status' => 'sometimes|in:ACTIVE,INACTIVE',
        ];
    }
}
