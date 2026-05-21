<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function results(): View
    {
        $companyId = (int) session('active_company_id');

        return view('admin.settings.results', [
            'requiresConfirmation' => SystemSetting::getBoolean($companyId, 'results.require_confirmation', true),
        ]);
    }

    public function updateResults(Request $request): RedirectResponse
    {
        $companyId = (int) session('active_company_id');

        $data = $request->validate([
            'requires_confirmation' => ['nullable', 'boolean'],
        ]);

        $requiresConfirmation = (bool) ($data['requires_confirmation'] ?? false);

        $setting = SystemSetting::setBoolean($companyId, 'results.require_confirmation', $requiresConfirmation);

        app(AuditService::class)->record(
            module: 'Settings',
            action: 'update_results_settings',
            auditable: $setting,
            description: 'Configuracion de confirmacion de resultados actualizada.',
            newValues: [
                'results.require_confirmation' => $requiresConfirmation,
            ],
        );

        return redirect()
            ->route('admin.settings.results')
            ->with('status', 'Configuracion de resultados actualizada.');
    }
}
