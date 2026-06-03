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
            El conector es una app de Windows que imprime los tickets en la impresora local. Se instala
            una vez por caja y se autoconfigura pegando el codigo de esta sucursal.
        </p>
        <ol class="small text-secondary mb-3">
            <li><strong>Descarga el instalador</strong> y ejecutalo en la PC de la caja (doble clic).</li>
            <li>Abre <strong>BSolutions Print Connector</strong> &rarr; <strong>Configuracion</strong> &rarr;
                <strong>Aprovisionar con codigo</strong> y pega el codigo de abajo.</li>
            <li>Queda conectado e imprimiendo. El mismo codigo sirve para todas las cajas de esta sucursal.</li>
        </ol>
        <div class="mb-3">
            <a class="btn btn-primary" href="{{ asset('downloads/BSolutionsPrintConnectorSetup.exe') }}">
                <i class="bi bi-box-arrow-down me-1"></i>Descargar instalador (.exe)
            </a>
        </div>

        @if ($provisionCode)
            <label class="form-label small mb-1"><strong>Codigo de aprovisionamiento</strong> (sucursal activa)</label>
            <div class="input-group mb-2">
                <input id="provisionCode" type="text" class="form-control font-monospace" readonly value="{{ $provisionCode }}">
                <button class="btn btn-outline-secondary" type="button"
                        onclick="navigator.clipboard.writeText(document.getElementById('provisionCode').value); this.innerHTML='<i class=\'bi bi-check\'></i> Copiado';">
                    <i class="bi bi-clipboard me-1"></i>Copiar
                </button>
            </div>
            <p class="small text-secondary mb-3">
                Contiene la URL del sistema y el token de esta sucursal. No requiere usuario/clave.
                Cualquiera con este codigo puede registrar una caja en esta sucursal: tratalo como una contrasena.
            </p>
            @if (auth()->user()->hasPermission('printers.configure'))
                <form method="POST" action="{{ route('admin.printers.connector.regenerate-token') }}"
                      onsubmit="return confirm('Regenerar invalida el codigo anterior. Continuar?')">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary" type="submit">
                        <i class="bi bi-key me-1"></i>Regenerar codigo (sucursal)
                    </button>
                </form>
            @endif
        @else
            <div class="alert alert-warning small mb-0">
                Selecciona una <strong>sucursal</strong> (arriba) para ver su codigo de aprovisionamiento.
            </div>
        @endif
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
                                @if (auth()->user()->hasPermission('printers.configure'))
                                    <form method="POST" action="{{ route('admin.printers.destroy', $printer) }}"
                                          onsubmit="return confirm('Eliminar la impresora &quot;{{ $printer->name }}&quot;? Si una caja la sigue usando, se volvera a crear al guardar en el conector.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Eliminar impresora">
                                            <i class="bi bi-trash"></i>
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
