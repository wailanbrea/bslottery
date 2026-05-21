@extends('layouts.app')

@section('content')
    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h4 mb-2">Activar BSLottery</h1>
                        <p class="text-secondary mb-4">Ingresa el código de activación emitido por BSolutions.</p>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('license.activate.store') }}" novalidate>
                            @csrf

                            <div class="mb-3">
                                <label class="form-label" for="activation_code">Código de activación</label>
                                <input
                                    class="form-control"
                                    id="activation_code"
                                    name="activation_code"
                                    maxlength="120"
                                    required
                                    autocomplete="off"
                                    value=""
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="client_location_code">Código de sucursal</label>
                                <input
                                    class="form-control"
                                    id="client_location_code"
                                    name="client_location_code"
                                    maxlength="100"
                                    value="{{ old('client_location_code', $defaultLocationCode) }}"
                                >
                            </div>

                            <div class="small text-secondary mb-4">
                                Proyecto: <strong>{{ $projectCode }}</strong>
                            </div>

                            <button class="btn btn-primary w-100" type="submit">Activar licencia</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

