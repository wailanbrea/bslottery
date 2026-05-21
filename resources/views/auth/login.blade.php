@extends('layouts.app')

@section('content')
    <div class="argon-auth-page d-flex align-items-center justify-content-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-5">
                    <div class="argon-auth-card">
                        <div class="argon-auth-header">
                            <div class="brand-icon">BS</div>
                            <h1 class="h4 text-white" style="color: #525f7f !important;">BSLottery</h1>
                            <p class="text-secondary mb-0">Ingresa con tu usuario administrativo.</p>
                        </div>

                        <div class="px-4 pb-4">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('login.store') }}" novalidate>
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label" for="username">Usuario</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input
                                            class="form-control"
                                            id="username"
                                            name="username"
                                            maxlength="80"
                                            required
                                            autocomplete="username"
                                            value="{{ old('username') }}"
                                            placeholder="Tu nombre de usuario"
                                        >
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="password">Contraseña</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input
                                            class="form-control"
                                            id="password"
                                            name="password"
                                            type="password"
                                            maxlength="255"
                                            required
                                            autocomplete="current-password"
                                            placeholder="Tu contraseña"
                                        >
                                    </div>
                                </div>

                                <button class="btn btn-primary w-100" type="submit">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
