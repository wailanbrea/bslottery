@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-4">
        <i class="bi bi-trophy me-2" style="color: var(--argon-warning);"></i>Registrar resultado
    </h1>

    <form method="POST" action="{{ route('admin.results.store') }}" class="card" x-data="{ lotteryId: {{ old('lottery_id', 0) }} }">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Lotería *</label>
                    <select class="form-select" name="lottery_id" required x-model="lotteryId">
                        <option value="">Seleccionar...</option>
                        @foreach ($lotteries as $lottery)
                            <option value="{{ $lottery->id }}" @selected(old('lottery_id') == $lottery->id)>{{ $lottery->name }} ({{ $lottery->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Sorteo *</label>
                    <select class="form-select" name="draw_id" required>
                        <option value="">Seleccionar lotería primero...</option>
                        @php
                            $drawOptions = \App\Models\Draw::where('company_id', session('active_company_id'))
                                ->whereIn('status', ['OPEN', 'CLOSED', 'RESULT_PENDING', 'CLOSING_SOON'])
                                ->whereDoesntHave('result')
                                ->with('lottery')
                                ->orderBy('draw_date', 'desc')
                                ->get();
                        @endphp
                        @foreach ($drawOptions as $d)
                            <option value="{{ $d->id }}" @selected(old('draw_id') == $d->id) data-lottery="{{ $d->lottery_id }}">
                                {{ $d->lottery->name }} — {{ $d->name }} ({{ $d->draw_date->format('Y-m-d') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Primer número *</label>
                    <input class="form-control" name="first_number" required maxlength="10" value="{{ old('first_number') }}" placeholder="00">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Segundo número</label>
                    <input class="form-control" name="second_number" maxlength="10" value="{{ old('second_number') }}" placeholder="00">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tercer número</label>
                    <input class="form-control" name="third_number" maxlength="10" value="{{ old('third_number') }}" placeholder="00">
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check-lg me-1"></i>Registrar
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.results.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
