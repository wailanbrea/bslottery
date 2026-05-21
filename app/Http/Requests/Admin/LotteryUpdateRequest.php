<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LotteryUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $companyId = session('active_company_id');
        $lotteryId = $this->route('lottery')->id;

        return [
            'name' => 'required|string|max:150',
            'code' => ['required', 'string', 'max:50', "unique:lotteries,code,{$lotteryId},id,company_id,{$companyId}"],
            'country' => 'sometimes|string|max:80',
            'status' => 'sometimes|in:ACTIVE,INACTIVE',
        ];
    }
}
