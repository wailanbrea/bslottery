<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DrawUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'lottery_id' => 'required|exists:lotteries,id',
            'name' => 'required|string|max:150',
            'draw_date' => 'required|date',
            'open_time' => 'required|date_format:H:i',
            'scheduled_time' => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i',
            'status' => 'sometimes|in:OPEN,CLOSING_SOON,CLOSED',
        ];
    }
}
