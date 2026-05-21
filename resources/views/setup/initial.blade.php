@extends('layouts.app')

@section('content')
    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h4 mb-2">Setup inicial</h1>
                        <p class="text-secondary mb-4">Confirma los datos de la licencia y crea el primer administrador.</p>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        @if ($profile['missing'])
                            <div class="alert alert-warning">
                                Hay datos faltantes en metadata. Completa los campos antes de continuar.
                            </div>
                        @endif

                        <form method="POST" action="{{ route('setup.initial.store') }}" novalidate>
                            @csrf

                            <h2 class="h6 text-uppercase text-secondary mb-3">Empresa</h2>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="company_name">Nombre comercial</label>
                                    <input class="form-control" id="company_name" name="company_name" required maxlength="150" value="{{ old('company_name', $profile['company']['name']) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="legal_name">Razón social</label>
                                    <input class="form-control" id="legal_name" name="legal_name" maxlength="200" value="{{ old('legal_name', $profile['company']['legal_name']) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="rnc">RNC</label>
                                    <input class="form-control" id="rnc" name="rnc" maxlength="50" value="{{ old('rnc', $profile['company']['rnc']) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="phone">Teléfono</label>
                                    <input class="form-control" id="phone" name="phone" maxlength="50" value="{{ old('phone', $profile['company']['phone']) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="company_email">Email</label>
                                    <input class="form-control" id="company_email" name="company_email" type="email" maxlength="150" value="{{ old('company_email') }}">
                                </div>
                                <div class="col-12 mb-4">
                                    <label class="form-label" for="address">Dirección</label>
                                    <input class="form-control" id="address" name="address" maxlength="255" value="{{ old('address', $profile['company']['address']) }}">
                                </div>
                            </div>

                            <h2 class="h6 text-uppercase text-secondary mb-3">Sucursal / banca</h2>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="branch_code">Código</label>
                                    <input class="form-control" id="branch_code" name="branch_code" required maxlength="50" value="{{ old('branch_code', $profile['branch']['code']) }}">
                                </div>
                                <div class="col-md-8 mb-4">
                                    <label class="form-label" for="branch_name">Nombre</label>
                                    <input class="form-control" id="branch_name" name="branch_name" required maxlength="150" value="{{ old('branch_name', $profile['branch']['name']) }}">
                                </div>
                            </div>

                            <h2 class="h6 text-uppercase text-secondary mb-3">Primer administrador</h2>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="admin_name">Nombre</label>
                                    <input class="form-control" id="admin_name" name="admin_name" required maxlength="150" value="{{ old('admin_name') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="admin_username">Usuario</label>
                                    <input class="form-control" id="admin_username" name="admin_username" required maxlength="80" value="{{ old('admin_username') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="admin_email">Email</label>
                                    <input class="form-control" id="admin_email" name="admin_email" type="email" maxlength="150" value="{{ old('admin_email') }}">
                                </div>
                                <div class="col-md-6 mb-3"></div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="admin_password">Contraseña</label>
                                    <input class="form-control" id="admin_password" name="admin_password" type="password" required autocomplete="new-password">
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label" for="admin_password_confirmation">Confirmar contraseña</label>
                                    <input class="form-control" id="admin_password_confirmation" name="admin_password_confirmation" type="password" required autocomplete="new-password">
                                </div>
                            </div>

                            <button class="btn btn-primary" type="submit">Completar setup</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
