@extends('layouts.app')

@section('content')
<h1 class="h4 mb-4">
    <i class="bi bi-printer me-2" style="color: var(--argon-info);"></i>
    {{ $printer->exists ? 'Editar impresora' : 'Agregar impresora' }}
</h1>

<form method="POST" action="{{ $printer->exists ? route('admin.printers.update', $printer) : route('admin.printers.store') }}" class="card">
    @csrf
    @if ($printer->exists) @method('PUT') @endif

    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Nombre *</label>
                <input class="form-control" name="name" required maxlength="150"
                       value="{{ old('name', $printer->name) }}" placeholder="Ej: Impresora Caja 1">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Sucursal</label>
                <select class="form-select" name="branch_id">
                    <option value="">Todas</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((int) old('branch_id', $printer->branch_id) === $branch->id)>
                            {{ $branch->code }} — {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Tipo de impresora *</label>
                <select class="form-select" name="printer_type" required>
                    <option value="THERMAL" @selected(old('printer_type', $printer->printer_type) === 'THERMAL')>Térmica</option>
                    <option value="NORMAL" @selected(old('printer_type', $printer->printer_type) === 'NORMAL')>Normal</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Tipo de conexión *</label>
                <select class="form-select" name="connection_type" required>
                    @foreach (['USB' => 'USB', 'NETWORK' => 'Red (Ethernet)', 'WINDOWS_SHARED' => 'Compartida Windows', 'BLUETOOTH' => 'Bluetooth'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('connection_type', $printer->connection_type) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Ancho de papel *</label>
                <select class="form-select" name="paper_width" required>
                    <option value="80MM" @selected(old('paper_width', $printer->paper_width) === '80MM')>80mm</option>
                    <option value="88MM" @selected(old('paper_width', $printer->paper_width) === '88MM')>88mm</option>
                    <option value="58MM" @selected(old('paper_width', $printer->paper_width) === '58MM')>58mm</option>
                </select>
            </div>
            <div class="col-md-8 mb-3">
                <label class="form-label">Identificador *</label>
                <input class="form-control" name="printer_identifier" required maxlength="255"
                       value="{{ old('printer_identifier', $printer->printer_identifier) }}"
                       placeholder="USB: COM3 | Red: 192.168.1.50 | Bluetooth: 00:11:22:33:44:55 | Windows: \\PC\Impresora">
                <div class="form-text">
                    <strong>USB:</strong> Puerto COM (ej: COM3) &nbsp;|&nbsp;
                    <strong>Red:</strong> IP (ej: 192.168.1.50) &nbsp;|&nbsp;
                    <strong>Bluetooth:</strong> Dirección MAC &nbsp;|&nbsp;
                    <strong>Compartida:</strong> Ruta UNC (ej: \\PC\Impresora)
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Estado</label>
                <select class="form-select" name="status">
                    <option value="ACTIVE" @selected(old('status', $printer->status) === 'ACTIVE')>Activa</option>
                    <option value="INACTIVE" @selected(old('status', $printer->status) === 'INACTIVE')>Inactiva</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex gap-2">
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-check-lg me-1"></i>Guardar
        </button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.printers.index') }}">Cancelar</a>
    </div>
</form>
@endsection
