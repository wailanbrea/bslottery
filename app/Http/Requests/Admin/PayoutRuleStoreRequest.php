<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PayoutRuleStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'branch_id' => 'nullable|exists:branches,id',
            'lottery_id' => 'nullable|exists:lotteries,id',
            'draw_id' => 'nullable|exists:draws,id',
            'bet_type_id' => 'required|exists:bet_types,id',
            'position' => 'nullable|in:FIRST,SECOND,THIRD,ANY,EXACT',
            'match_type' => 'required|in:DIRECT,COMBINATION,EXACT_ORDER,ANY_ORDER',
            'payout_multiplier' => 'required|numeric|min:0.01|max:99999.99',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'status' => 'sometimes|in:DRAFT,ACTIVE,INACTIVE',
        ];
    }
}
