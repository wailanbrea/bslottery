<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BetTypeStoreRequest extends FormRequest
{
    public function rules(): array
    {
        $companyId = session('active_company_id');

        return [
            'company_id' => 'sometimes|exists:companies,id',
            'code' => ['required', 'string', 'max:50', "unique:bet_types,code,NULL,id,company_id,{$companyId}"],
            'name' => 'required|string|max:100',
            'digits_count' => 'required|integer|min:1|max:10',
            'min_numbers' => 'required|integer|min:1|max:100',
            'max_numbers' => 'required|integer|min:1|max:100|gte:min_numbers',
            'numbers_count' => 'required|integer|min:1|max:3',
            'requires_position' => 'sometimes|boolean',
            'is_cross_lottery' => 'sometimes|boolean',
            'status' => 'sometimes|in:ACTIVE,INACTIVE',
            'max_amount' => 'nullable|numeric|min:0.01|max:9999999999.99',
        ];
    }
}
