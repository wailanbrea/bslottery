@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-sliders me-2" style="color: var(--argon-primary);"></i>Umbrales de monitoreo</h1>
            <p class="text-secondary mb-0">Configura alertas por perdida, caja minima y jugadas acumuladas por sucursal.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.monitoring.index') }}">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.monitoring.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header fw-semibold">Configuracion por defecto de la empresa</div>
            <div class="card-body">
                <input type="hidden" name="settings[0][branch_id]" value="">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="settings[0][alert_enabled]" value="1" @checked(old('settings.0.alert_enabled', $defaultSetting->alert_enabled))>
                            <label class="form-check-label">Alertas activas</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Perdida critica desde</label>
                        <input class="form-control" name="settings[0][loss_threshold]" value="{{ old('settings.0.loss_threshold', $defaultSetting->loss_threshold ?? '0.00') }}" inputmode="decimal">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Caja minima esperada</label>
                        <input class="form-control" name="settings[0][minimum_expected_cash]" value="{{ old('settings.0.minimum_expected_cash', $defaultSetting->minimum_expected_cash) }}" inputmode="decimal">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Alerta jugada acumulada</label>
                        <input class="form-control" name="settings[0][top_play_alert_amount]" value="{{ old('settings.0.top_play_alert_amount', $defaultSetting->top_play_alert_amount) }}" inputmode="decimal">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-semibold">Sobrescritura por sucursal</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Sucursal</th>
                            <th>Activa</th>
                            <th>Perdida critica desde</th>
                            <th>Caja minima esperada</th>
                            <th>Alerta jugada acumulada</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($branches as $index => $branch)
                            @php
                                $key = $index + 1;
                                $setting = $settings[$branch->id] ?? $defaultSetting;
                            @endphp
                            <tr>
                                <td>
                                    <input type="hidden" name="settings[{{ $key }}][branch_id]" value="{{ $branch->id }}">
                                    <div class="fw-semibold">{{ $branch->name }}</div>
                                    <small class="text-secondary">{{ $branch->code }}</small>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="settings[{{ $key }}][alert_enabled]" value="1" @checked(old("settings.$key.alert_enabled", $setting->alert_enabled))>
                                    </div>
                                </td>
                                <td>
                                    <input class="form-control" name="settings[{{ $key }}][loss_threshold]" value="{{ old("settings.$key.loss_threshold", $setting->loss_threshold ?? '0.00') }}" inputmode="decimal">
                                </td>
                                <td>
                                    <input class="form-control" name="settings[{{ $key }}][minimum_expected_cash]" value="{{ old("settings.$key.minimum_expected_cash", $setting->minimum_expected_cash) }}" inputmode="decimal">
                                </td>
                                <td>
                                    <input class="form-control" name="settings[{{ $key }}][top_play_alert_amount]" value="{{ old("settings.$key.top_play_alert_amount", $setting->top_play_alert_amount) }}" inputmode="decimal">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-save me-1"></i>Guardar umbrales
                </button>
            </div>
        </div>
    </form>
@endsection
