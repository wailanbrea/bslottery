@extends('layouts.app')

@section('content')
    @php
        $mustChangePassword = auth()->user()?->must_change_password ?? false;
    @endphp

    <div class="argon-auth-page d-flex align-items-center justify-content-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-5">
                    <div class="argon-auth-card">
                        <div class="argon-auth-header">
                            <div class="brand-icon">BS</div>
                            <h1 class="h4 text-white" style="color: #525f7f !important;">Cambiar contrase&ntilde;a</h1>
                            <p class="text-secondary mb-0">
                                {{ $mustChangePassword ? 'Actualiza tu clave inicial para continuar usando el sistema.' : 'Actualiza tu clave de acceso cuando lo necesites.' }}
                            </p>
                        </div>

                        <div class="px-4 pb-4">
                            @if (session('warning'))
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    {{ session('warning') }}
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('password.update') }}" novalidate>
                                @csrf
                                @method('PUT')

                                <div class="mb-3">
                                    <label class="form-label" for="current_password">Contrase&ntilde;a actual</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input
                                            class="form-control"
                                            id="current_password"
                                            name="current_password"
                                            type="password"
                                            maxlength="255"
                                            required
                                            autocomplete="current-password"
                                        >
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="password">Nueva contrase&ntilde;a</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                        <input
                                            class="form-control"
                                            id="password"
                                            name="password"
                                            type="password"
                                            maxlength="255"
                                            required
                                            autocomplete="new-password"
                                        >
                                    </div>
                                    <div class="form-text">Minimo 10 caracteres, con letras y numeros.</div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="password_confirmation">Confirmar nueva contrase&ntilde;a</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                                        <input
                                            class="form-control"
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            type="password"
                                            maxlength="255"
                                            required
                                            autocomplete="new-password"
                                        >
                                    </div>
                                </div>

                                <button class="btn btn-primary w-100" type="submit">
                                    <i class="bi bi-check2-circle me-2"></i>Guardar nueva contrase&ntilde;a
                                </button>
                            </form>

                            @if ($mustChangePassword)
                                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                                    @csrf
                                    <button class="btn btn-outline-secondary w-100" type="submit">
                                        <i class="bi bi-box-arrow-left me-2"></i>Cerrar sesion
                                    </button>
                                </form>
                            @else
                                <a class="btn btn-outline-secondary w-100 mt-3" href="{{ route('dashboard') }}">
                                    <i class="bi bi-arrow-left me-2"></i>Volver al dashboard
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
