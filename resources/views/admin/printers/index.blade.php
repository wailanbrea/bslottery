@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-printer me-2" style="color: var(--argon-info);"></i>Impresoras</h1>
        <p class="text-secondary mb-0">Impresoras térmicas gestionadas por BSolutions Print Connector.</p>
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
            una vez por caja y se autoconfigura pegando el código de esta sucursal.
        </p>
        <ol class="small text-secondary mb-3">
            <li><strong>Descarga el instalador</strong> y ejecútalo en la PC de la caja.</li>
            <li>Abre <strong>BSolutions Print Connector</strong> → <strong>Configuración</strong> →
                <strong>Aprovisionar con código</strong> y pega el código de abajo.</li>
            <li>La caja quedará conectada e imprimiendo en la impresora que selecciones desde el conector.</li>
        </ol>
        <div class="mb-3">
            <a class="btn btn-primary" href="{{ asset('downloads/BSolutionsPrintConnectorSetup.exe') }}">
                <i class="bi bi-box-arrow-down me-1"></i>Descargar instalador (.exe)
            </a>
        </div>

        @if ($provisionCode)
            <label class="form-label small mb-1"><strong>Código de aprovisionamiento</strong> (sucursal activa)</label>
            <div class="input-group mb-2">
                <input id="provisionCode" type="text" class="form-control font-monospace" readonly value="{{ $provisionCode }}">
                <button class="btn btn-outline-secondary" type="button"
                        onclick="navigator.clipboard.writeText(document.getElementById('provisionCode').value); this.innerHTML='<i class=\'bi bi-check\'></i> Copiado';">
                    <i class="bi bi-clipboard me-1"></i>Copiar
                </button>
            </div>
            <p class="small text-secondary mb-3">
                Contiene la URL del sistema y el token de esta sucursal. No requiere usuario/clave.
                Trátalo como una contraseña.
            </p>
            @if (auth()->user()->hasPermission('printers.configure'))
                <form method="POST" action="{{ route('admin.printers.connector.regenerate-token') }}"
                      onsubmit="return confirm('Regenerar invalida el código anterior. ¿Continuar?')">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary" type="submit">
                        <i class="bi bi-key me-1"></i>Regenerar código (sucursal)
                    </button>
                </form>
            @endif
        @else
            <div class="alert alert-warning small mb-0">
                Selecciona una <strong>sucursal</strong> para ver su código de aprovisionamiento.
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
                    <th>Conexión</th>
                    <th>Papel</th>
                    <th>Identificador</th>
                    <th>Sucursal</th>
                    <th>Principal</th>
                    <th>Últ. prueba</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($printers as $printer)
                    @php
                        $isBranchDefault = $printer->branch_id && optional($printer->branch)->default_printer_id === $printer->id;
                        $isTerminalDefault = $printer->terminal_key && $printer->is_default;
                        $connIcon = match($printer->connection_type) {
                            'PRINT_CONNECTOR' => 'bi-printer',
                            'USB' => 'bi-usb-plug',
                            'NETWORK' => 'bi-ethernet',
                            'WINDOWS_SHARED' => 'bi-windows',
                            'BLUETOOTH' => 'bi-bluetooth',
                            default => 'bi-plug',
                        };
                    @endphp
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
                        <td><i class="bi {{ $connIcon }} me-1 text-secondary"></i>{{ $printer->connection_type }}</td>
                        <td>{{ $printer->paper_width }}</td>
                        <td><code class="small">{{ $printer->printer_identifier }}</code></td>
                        <td>{{ $printer->branch?->name ?: 'Todas' }}</td>
                        <td>
                            @if ($isTerminalDefault)
                                <span class="badge bg-primary">Terminal</span>
                            @endif
                            @if ($isBranchDefault)
                                <span class="badge bg-success">Sucursal</span>
                            @endif
                            @if (! $isTerminalDefault && ! $isBranchDefault)
                                <span class="text-secondary small">No</span>
                            @endif
                        </td>
                        <td class="small">{{ $printer->last_test_at?->format('Y-m-d H:i') ?: '—' }}</td>
                        <td><x-status-badge :status="$printer->status" /></td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                @if (auth()->user()->hasPermission('printers.configure'))
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.printers.edit', $printer) }}" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                                @if (auth()->user()->hasPermission('printers.test'))
                                    <form method="POST" action="{{ route('admin.printers.test', $printer) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success" type="submit" title="Enviar prueba a la cola de impresión">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    </form>
                                @endif
                                @if (auth()->user()->hasPermission('printers.configure') && $printer->status === 'ACTIVE')
                                    <form method="POST" action="{{ route('admin.printers.make-default', $printer) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-dark" type="submit" title="Establecer como principal">
                                            <i class="bi bi-star"></i>
                                        </button>
                                    </form>
                                @endif
                                @if (auth()->user()->hasPermission('printers.configure'))
                                    <form method="POST" action="{{ route('admin.printers.destroy', $printer) }}"
                                          onsubmit="return confirm('Eliminar la impresora &quot;{{ $printer->name }}&quot;? Si una caja la sigue usando, se volverá a crear al guardar en el conector.')">
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
                    <tr><td colspan="11" class="text-center text-secondary py-4">No hay impresoras configuradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $printers->links() }}</div>
@endsection
