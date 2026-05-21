@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-printer me-2" style="color: var(--argon-info);"></i>Impresoras</h1>
        <p class="text-secondary mb-0">Configuración de impresoras térmicas USB, red, Bluetooth o compartidas.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <div id="agentStatusCard" class="card card-body py-1 px-3 d-flex flex-row align-items-center gap-2 small">
            <span id="agentStatusDot" class="badge bg-secondary">Verificando…</span>
            <span id="agentStatusText" class="text-secondary">Print Agent</span>
            <button id="agentPrintersBtn" class="btn btn-sm btn-outline-secondary d-none" onclick="loadAgentPrinters()">
                <i class="bi bi-arrow-clockwise"></i> Impresoras del sistema
            </button>
        </div>
        @if (auth()->user()->hasPermission('printers.configure'))
            <a class="btn btn-primary" href="{{ route('admin.printers.create') }}">
                <i class="bi bi-plus-lg me-1"></i>Agregar impresora
            </a>
        @endif
    </div>
</div>

<div id="agentPrintersList" class="alert alert-info d-none mb-3">
    <strong><i class="bi bi-info-circle me-1"></i>Impresoras detectadas por el agente:</strong>
    <ul id="agentPrintersUl" class="mb-0 mt-1"></ul>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Conexión</th>
                    <th>Papel</th>
                    <th>Identificador</th>
                    <th>Sucursal</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($printers as $printer)
                    <tr>
                        <td class="fw-semibold">{{ $printer->name }}</td>
                        <td>
                            <span class="badge {{ $printer->printer_type === 'THERMAL' ? 'bg-info' : 'bg-secondary' }}">{{ $printer->printer_type }}</span>
                        </td>
                        <td>
                            @php
                                $connIcon = match($printer->connection_type) {
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
                                    @if (in_array($printer->connection_type, ['USB','NETWORK','WINDOWS_SHARED']))
                                        <button class="btn btn-sm btn-outline-success"
                                            title="Probar impresión (via Print Agent)"
                                            onclick="agentTestPrint('{{ addslashes($printer->printer_identifier) }}','{{ $printer->connection_type }}','{{ $printer->paper_width }}')">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    @else
                                        <form method="POST" action="{{ route('admin.printers.test', $printer) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success" type="submit" title="Probar (Android/Bluetooth)">
                                                <i class="bi bi-send"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-secondary py-4">No hay impresoras configuradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $printers->links() }}</div>

@push('scripts')
<script>
    const AGENT_URL   = window.bsPrintAgentUrl   || 'http://127.0.0.1:8765';
    const AGENT_TOKEN = window.bsPrintAgentToken || '';

    async function checkAgent() {
        try {
            const res = await fetch(AGENT_URL + '/api/status', { signal: AbortSignal.timeout(2000) });
            const dot  = document.getElementById('agentStatusDot');
            const text = document.getElementById('agentStatusText');
            const btn  = document.getElementById('agentPrintersBtn');
            if (res.ok) {
                const data = await res.json();
                dot.className  = 'badge bg-success';
                dot.textContent = 'Activo';
                text.textContent = 'Print Agent v' + (data.version || '?') + ' — ' + (data.os || '');
                text.className = 'text-success';
                btn.classList.remove('d-none');
            } else {
                setOffline();
            }
        } catch { setOffline(); }
    }

    function setOffline() {
        document.getElementById('agentStatusDot').className = 'badge bg-danger';
        document.getElementById('agentStatusDot').textContent = 'No disponible';
        document.getElementById('agentStatusText').textContent = 'Inicie bslottery-print-agent.jar';
        document.getElementById('agentStatusText').className = 'text-danger';
    }

    async function loadAgentPrinters() {
        try {
            const res = await fetch(AGENT_URL + '/api/printers', {
                headers: { 'Authorization': 'Bearer ' + AGENT_TOKEN },
                signal: AbortSignal.timeout(3000),
            });
            if (!res.ok) return;
            const data = await res.json();
            const ul = document.getElementById('agentPrintersUl');
            ul.innerHTML = '';
            (data.printers || []).forEach(p => {
                const li = document.createElement('li');
                li.textContent = p.name + (p.is_default ? ' (predeterminada)' : '');
                ul.appendChild(li);
            });
            document.getElementById('agentPrintersList').classList.remove('d-none');
        } catch (e) {
            alert('Error al cargar impresoras: ' + e.message);
        }
    }

    async function agentTestPrint(printerName, connType, paperWidth) {
        const btn = event.currentTarget;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';

        try {
            const res = await fetch(AGENT_URL + '/api/test', {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + AGENT_TOKEN, 'Content-Type': 'application/json' },
                body: JSON.stringify({ printer_name: printerName, connection_type: connType, paper_width: paperWidth }),
                signal: AbortSignal.timeout(8000),
            });
            const data = await res.json();
            if (data.success) {
                btn.className = 'btn btn-sm btn-success';
                btn.innerHTML = '<i class="bi bi-check-lg"></i>';
                btn.title = 'Impresión de prueba enviada';
            } else {
                btn.className = 'btn btn-sm btn-danger';
                btn.innerHTML = '<i class="bi bi-x-lg"></i>';
                btn.title = 'Error: ' + (data.error || 'desconocido');
            }
        } catch (e) {
            btn.className = 'btn btn-sm btn-danger';
            btn.innerHTML = '<i class="bi bi-x-lg"></i>';
            btn.title = 'Print Agent no disponible: ' + e.message;
        } finally {
            setTimeout(() => {
                btn.disabled = false;
                btn.className = 'btn btn-sm btn-outline-success';
                btn.innerHTML = '<i class="bi bi-send"></i>';
                btn.title = 'Probar impresión (via Print Agent)';
            }, 4000);
        }
    }

    checkAgent();
</script>
@endpush
@endsection
