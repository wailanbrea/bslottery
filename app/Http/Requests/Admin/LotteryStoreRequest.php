<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LotteryStoreRequest extends FormRequest
{
    public function rules(): array
    {
        $companyId = session('active_company_id');

        return [
            'company_id' => 'sometimes|exists:companies,id',
            'name' => 'required|string|max:150',
            'code' => ['required', 'string', 'max:50', "unique:lotteries,code,NULL,id,company_id,{$companyId}"],
            'country' => 'sometimes|string|max:80',
            'status' => 'sometimes|in:ACTIVE,INACTIVE',
        ];
    }
}
