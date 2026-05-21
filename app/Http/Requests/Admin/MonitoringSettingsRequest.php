<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MonitoringSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('monitoring.configure') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*.branch_id' => ['nullable', 'integer'],
            'settings.*.alert_enabled' => ['nullable', 'boolean'],
            'settings.*.loss_threshold' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'settings.*.minimum_expected_cash' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'settings.*.top_play_alert_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
        ];
    }
}
