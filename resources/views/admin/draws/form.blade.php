@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-4">
        <i class="bi bi-calendar-event me-2" style="color: var(--argon-info);"></i>
        {{ $draw->exists ? 'Editar sorteo' : 'Crear sorteo' }}
    </h1>

    <form method="POST" action="{{ $draw->exists ? route('admin.draws.update', $draw) : route('admin.draws.store') }}" class="card">
        @csrf
        @if ($draw->exists)
            @method('PUT')
        @endif

        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Lotería *</label>
                    <select class="form-select" name="lottery_id" required>
                        <option value="">Seleccionar...</option>
                        @foreach ($lotteries as $lottery)
                            <option value="{{ $lottery->id }}" @selected((int) old('lottery_id', $draw->lottery_id) === $lottery->id)>{{ $lottery->name }} ({{ $lottery->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nombre *</label>
                    <input class="form-control" name="name" required maxlength="150" value="{{ old('name', $draw->name) }}" placeholder="Ej: Sorteo 12:30 PM">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Fecha *</label>
                    <input class="form-control" name="draw_date" type="date" required value="{{ old('draw_date', optional($draw->draw_date)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Hora de apertura *</label>
                    <input class="form-control" name="open_time" type="time" required value="{{ old('open_time', $draw->open_time ?? '08:00') }}">
                    <div class="form-text">Antes de esta hora el sorteo no acepta ventas.</div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Hora programada *</label>
                    <input class="form-control" name="scheduled_time" type="time" required value="{{ old('scheduled_time', $draw->scheduled_time) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Hora de cierre *</label>
                    <input class="form-control" name="close_time" type="time" required value="{{ old('close_time', $draw->close_time) }}">
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check-lg me-1"></i>Guardar
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.draws.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
