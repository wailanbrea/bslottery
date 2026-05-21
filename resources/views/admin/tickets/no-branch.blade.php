@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1"><i class="bi bi-exclamation-triangle me-2" style="color: var(--argon-warning);"></i>Sin sucursal activa</h1>
        <p class="text-secondary mb-0">Selecciona una sucursal en el header para poder vender.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('dashboard') }}">Volver al dashboard</a>
@endsection
