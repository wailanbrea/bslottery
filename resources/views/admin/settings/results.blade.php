@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1"><i class="bi bi-gear me-2" style="color: var(--argon-primary);"></i>Configuración de resultados</h1>
        <p class="text-secondary mb-0">Controla si los números ganadores requieren validación por un segundo administrador.</p>
    </div>

    <form method="POST" action="{{ route('admin.settings.results.update') }}" class="card">
        @csrf
        @method('PUT')

        <div class="card-body">
            <div class="form-check form-switch">
                <input type="hidden" name="requires_confirmation" value="0">
                <input
                    class="form-check-input"
                    id="requires_confirmation"
                    name="requires_confirmation"
                    type="checkbox"
                    value="1"
                    @checked(old('requires_confirmation', $requiresConfirmation))
                >
                <label class="form-check-label fw-semibold" for="requires_confirmation">
                    Requerir confirmación de resultados
                </label>
            </div>
            <p class="text-secondary small mt-2 mb-0">
                Si está activo, el usuario que registra los números ganadores no puede confirmarlos. Otro administrador con permiso de confirmación debe validarlos.
                Si está inactivo, el resultado queda confirmado automáticamente al registrarse.
            </p>
        </div>

        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('dashboard') }}">Cancelar</a>
            @if (auth()->user()->hasPermission('settings.update'))
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-save me-1"></i>Guardar configuración
                </button>
            @endif
        </div>
    </form>
@endsection
