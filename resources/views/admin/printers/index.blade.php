@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-printer me-2" style="color: var(--argon-info);"></i>Impresoras</h1>
        <p class="text-secondary mb-0">Impresoras termicas gestionadas por BSolutions Print Connector.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        @if (auth()->user()->hasPermission('printers.configure'))
            <a class="btn btn-primary" href="{{ route('admin.printers.create') }}">
                <i class="bi bi-plus-lg me-1"></i>Agregar impresora
            </a>
        @endif
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white">
        <h2 class="h6 mb-0"><i class="bi bi-download me-2"></i>BSolutions Print Connector</h2>
    </div>
    <div class="card-body">
        <p class="text-secondary small mb-3">
            El conector es una pequena app de Windows que imprime los tickets en la impresora
            termica local. Instalala una vez por caja y selecciona la impresora dentro del conector.
        </p>
        <div class="d-flex gap-2 flex-wrap mb-3">
            <a class="btn btn-primary" href="{{ route('admin.printers.connector.script') }}">
                <i class="bi bi-magic me-1"></i>Descargar e instalar (automatico)
            </a>
            <a class="btn btn-outline-secondary" href="{{ asset('downloads/BSolutionsPrintConnectorSetup.exe') }}">
                <i class="bi bi-box-arrow-down me-1"></i>Descargar instalador (.exe)
            </a>
        </div>
        <ol class="small text-secondary mb-0">
            <li>Descarga e instala el conector en la PC de la caja (boton automatico: ejecuta el .bat descargado).</li>
            <li>Abre <strong>BSolutions Print Connector</strong> y entra a <strong>Configuracion</strong>.</li>
            <li>Escribe la URL del sistema, usuario/clave y la <strong>terminal key</strong> de esta caja.</li>
            <li>Selecciona la <strong>impresora</strong> de la lista y pulsa <strong>Guardar</strong>.</li>
            <li>Autoriza el dispositivo en <a href="{{ route('admin.devices.index') }}">Dispositivos</a> y pulsa <strong>Iniciar conector</strong>.</li>
        </ol>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Terminal</th>
                    <th>Tipo</th>
                    <th>Conexion</th>
                    <th>Papel</th>
                    <th>Identificador</th>
                    <th>Sucursal</th>
                    <th>Ult. prueba</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($printers as $printer)
                    <tr>
                        <td class="fw-semibold">{{ $printer->name }}</td>
                        <td>
                            <div class="small fw-semibold">{{ $printer->terminal_name ?: 'Sin terminal' }}</div>
                            @if ($printer->terminal_key)
                                <code class="small">{{ $printer->terminal_key }}</code>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $printer->printer_type === 'THERMAL' ? 'bg-info' : 'bg-secondary' }}">{{ $printer->printer_type }}</span>
                        </td>
                        <td>
                            @php
                                $connIcon = match($printer->connection_type) {
                                    'PRINT_CONNECTOR' => 'bi-printer',
                                    'USB' => 'bi-usb-plug',
                                    'NETWORK' => 'bi-ethernet',
                                    'WINDOWS_SHARED' => 'bi-windows',
                                    'BLUETOOTH' => 'bi-bluetooth',
                                    default => 'bi-plug',
                                };
                            @endphp
                            <i class="bi {{ $connIcon }} me-1 text-secondary"></i>{{ $printer->connection_type }}
                        </td>
                        <td>{{ $printer->paper_width }}</td>
                        <td><code class="small">{{ $printer->printer_identifier }}</code></td>
                        <td>{{ $printer->branch?->name ?: 'Todas' }}</td>
                        <td class="small">{{ $printer->last_test_at?->format('Y-m-d H:i') ?: '—' }}</td>
                        <td>
                            <x-status-badge :status="$printer->status" />
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                @if (auth()->user()->hasPermission('printers.configure'))
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.printers.edit', $printer) }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                                @if (auth()->user()->hasPermission('printers.test'))
                                    <form method="POST" action="{{ route('admin.printers.test', $printer) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success" type="submit" title="Enviar prueba a la cola de impresion">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-secondary py-4">No hay impresoras configuradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $printers->links() }}</div>
@endsection
