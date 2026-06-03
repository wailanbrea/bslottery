@extends('layouts.app')

@section('content')
    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h4 mb-2">Licencia bloqueada</h1>
                        <p class="text-secondary mb-4">El sistema no puede continuar porque la licencia no es válida.</p>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        @if ($state && $state->last_validation_success && $state->offline_grace_expires_at)
                            <div class="alert alert-warning">
                                La última validación exitosa sigue guardada. Si la API madre falla temporalmente, intenta revalidar para recuperar el acceso.
                            </div>
                        @endif

                        <dl class="row mb-4">
                            <dt class="col-sm-4">Motivo</dt>
                            <dd class="col-sm-8">{{ $reason }}</dd>

                            @if ($state)
                                <dt class="col-sm-4">Estado</dt>
                                <dd class="col-sm-8">{{ $state->status }}</dd>

                                <dt class="col-sm-4">Licencia</dt>
                                <dd class="col-sm-8">{{ $state->license_key ?: 'No disponible' }}</dd>
                            @endif
                        </dl>

                        <div class="d-flex gap-2 flex-wrap">
                            <form method="POST" action="{{ route('license.revalidate') }}">
                                @csrf
                                <button class="btn btn-primary" type="submit">Revalidar licencia</button>
                            </form>

                            <a class="btn btn-outline-primary" href="{{ route('license.activate') }}">Revisar activación</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
