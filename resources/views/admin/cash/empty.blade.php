@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <h1 class="h4 mb-1"><i class="bi bi-cash-register me-2" style="color: var(--argon-success);"></i>Caja</h1>
        <p class="text-secondary mb-0">No hay caja abierta en esta sucursal.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.cash.open') }}">
        <i class="bi bi-plus-circle me-1"></i>Abrir caja
    </a>
    <a class="btn btn-outline-secondary ms-2" href="{{ route('admin.cash.index') }}">
        Historial
    </a>
@endsection
