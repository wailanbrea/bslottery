@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-printer me-2" style="color: var(--argon-info);"></i>Impresoras</h1>
        <p class="text-secondary mb-0">Configuracion de impresoras termicas y asignacion por terminal web con QZ Tray.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <div id="qzStatusCard" class="card card-body py-1 px-3 d-flex flex-row align-items-center gap-2 small">
            <span id="qzStatusDot" class="badge bg-secondary">Verificando...</span>
            <span id="qzStatusText" class="text-secondary">QZ Tray</span>
        </div>
        @if (auth()->user()->hasPermission('printers.configure'))
            <a class="btn btn-primary" href="{{ route('admin.printers.create') }}">
                <i class="bi bi-plus-lg me-1"></i>Agregar impresora
            </a>
        @endif
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white">
        <h2 class="h6 mb-0">Configuracion rapida de esta terminal</h2>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-lg-3">
                <label class="form-label">Terminal key</label>
                <input id="terminalKeyInput" class="form-control" type="text" readonly data-terminal-key-input>
                <div class="form-text">Identificador local persistente del navegador para esta caja.</div>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Nombre de terminal</label>
                <input id="terminalNameInput" class="form-control" type="text" maxlength="150" data-terminal-name-input placeholder="Caja principal SUC32-03">
            </div>
            <div class="col-lg-4">
                <label class="form-label">Impresora detectada por QZ Tray</label>
                <select id="qzPrinterSelect" class="form-select">
                    <option value="">Detecta impresoras primero</option>
                </select>
                <div id="qzPrinterHelp" class="form-text">QZ Tray debe estar instalado y ejecutandose en la PC. Instalacion sugerida: <code>{{ config('print.qz_install_command') }}</code></div>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Papel</label>
                <select id="qzPaperWidth" class="form-select">
                    <option value="80MM">80mm</option>
                    <option value="58MM">58mm</option>
                    <option value="88MM">88mm</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Corte automatico</label>
                <select id="qzAutoCut" class="form-select">
                    <option value="1">Si</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="col-lg-10 d-flex align-items-end gap-2 flex-wrap">
                <button id="detectPrintersBtn" class="btn btn-outline-secondary" type="button">
                    <i class="bi bi-arrow-clockwise me-1"></i>Detectar impresoras
                </button>
                <button id="testQzBtn" class="btn btn-outline-success" type="button">
                    <i class="bi bi-printer me-1"></i>Imprimir prueba
                </button>
                @if (auth()->user()->hasPermission('printers.configure'))
                    <button id="saveQzBtn" class="btn btn-primary" type="button">
                        <i class="bi bi-check-lg me-1"></i>Guardar en esta terminal
                    </button>
                @endif
            </div>
            <div class="col-12">
                <div id="qzTerminalMessage" class="small text-secondary">Esperando conexion con QZ Tray.</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">QZ silencioso</h2>
        <span class="badge {{ $qzSilentReady ? 'bg-success' : 'bg-warning text-dark' }}">
            {{ $qzSilentReady ? 'Configurado' : 'Pendiente' }}
        </span>
    </div>
    <div class="card-body">
        <p class="text-secondary small mb-3">
            Para evitar los mensajes repetidos de <strong>Allow / Block</strong>, esta PC debe usar el
            <code>digital-certificate.txt</code> y el <code>private-key.pem</code> generados por QZ Tray en la misma máquina.
        </p>
        <ol class="small text-secondary mb-3">
            <li>En la PC abre <strong>QZ Tray &gt; Advanced &gt; Site Manager</strong>.</li>
            <li>Pulsa <strong>+</strong> y luego <strong>Create New</strong>.</li>
            <li>Responde <strong>Yes</strong> a las preguntas para generar e instalar las claves demo.</li>
            <li>En el escritorio se crea la carpeta <strong>QZ Tray Demo Cert</strong>.</li>
            <li>Desde esa carpeta sube aquí <code>digital-certificate.txt</code> y <code>private-key.pem</code>.</li>
        </ol>

        @if (auth()->user()->hasPermission('printers.configure'))
            <div class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label">digital-certificate.txt</label>
                    <input id="qzDigitalCertificateInput" class="form-control" type="file" accept=".txt">
                </div>
                <div class="col-lg-4">
                    <label class="form-label">private-key.pem</label>
                    <input id="qzPrivateKeyInput" class="form-control" type="file" accept=".pem">
                </div>
                <div class="col-lg-4">
                    <button id="uploadQzCredentialsBtn" class="btn btn-outline-primary w-100" type="button">
                        <i class="bi bi-shield-lock me-1"></i>Subir credenciales QZ
                    </button>
                </div>
            </div>
            <div id="qzCredentialMessage" class="small text-secondary mt-3">
                {{ $qzSilentReady ? 'La firma silenciosa de QZ ya esta cargada en esta instalación.' : 'Aún no se han cargado las credenciales de QZ para esta instalación.' }}
            </div>
            <div class="d-flex gap-2 flex-wrap mt-3">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.printers.qz.credentials.download-certificate') }}">
                    <i class="bi bi-download me-1"></i>Descargar digital-certificate.txt
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.printers.qz.credentials.download-script') }}">
                    <i class="bi bi-download me-1"></i>Descargar confiar-qz.bat
                </a>
            </div>
            <p class="small text-secondary mt-2 mb-0">
                Después de subir las credenciales, descarga esos dos archivos en la misma carpeta de la PC,
                ejecuta <code>confiar-qz.bat</code> una sola vez y luego reinicia QZ Tray.
            </p>
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
                                    'QZ_TRAY' => 'bi-browser-edge',
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
                                        <input type="hidden" name="terminal_key" data-terminal-key-input>
                                        <input type="hidden" name="terminal_name" data-terminal-name-input>
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

@push('scripts')
<script>
    const qzEls = {
        dot: document.getElementById('qzStatusDot'),
        text: document.getElementById('qzStatusText'),
        message: document.getElementById('qzTerminalMessage'),
        terminalName: document.getElementById('terminalNameInput'),
        printerSelect: document.getElementById('qzPrinterSelect'),
        paperWidth: document.getElementById('qzPaperWidth'),
        autoCut: document.getElementById('qzAutoCut'),
        detectBtn: document.getElementById('detectPrintersBtn'),
        testBtn: document.getElementById('testQzBtn'),
        saveBtn: document.getElementById('saveQzBtn'),
        credentialMessage: document.getElementById('qzCredentialMessage'),
        digitalCertificateInput: document.getElementById('qzDigitalCertificateInput'),
        privateKeyInput: document.getElementById('qzPrivateKeyInput'),
        uploadCredentialsBtn: document.getElementById('uploadQzCredentialsBtn'),
    };

    function setQzStatus(online, extra) {
        qzEls.dot.className = 'badge ' + (online ? 'bg-success' : 'bg-danger');
        qzEls.dot.textContent = online ? 'Conectado' : 'No disponible';
        qzEls.text.className = online ? 'text-success' : 'text-danger';
        qzEls.text.textContent = online ? 'QZ Tray listo' : 'Instala o abre QZ Tray';
        if (extra) {
            qzEls.message.textContent = extra;
        }
    }

    function fillPrinterOptions(printers, preferred) {
        qzEls.printerSelect.innerHTML = '';

        if (!printers.length) {
            qzEls.printerSelect.innerHTML = '<option value="">No se detectaron impresoras</option>';
            return;
        }

        printers.forEach((printer) => {
            const option = document.createElement('option');
            option.value = printer;
            option.textContent = printer;
            if (preferred && preferred === printer) {
                option.selected = true;
            }
            qzEls.printerSelect.appendChild(option);
        });
    }

    async function detectPrinters() {
        qzEls.detectBtn.disabled = true;
        qzEls.message.textContent = 'Buscando impresoras via QZ Tray...';

        try {
            const printers = await window.BSQZ.getAvailablePrinters();
            const defaultPrinter = await window.BSQZ.getDefaultPrinter();
            const preferred = qzEls.printerSelect.dataset.preferred || qzEls.printerSelect.value || defaultPrinter;
            fillPrinterOptions(printers, preferred);
            setQzStatus(true, 'Impresoras detectadas: ' + printers.length + '.');
        } catch (error) {
            fillPrinterOptions([], '');
            setQzStatus(false, error.message || 'No se pudo conectar con QZ Tray.');
        } finally {
            qzEls.detectBtn.disabled = false;
        }
    }

    async function loadCurrentTerminal() {
        const terminal = window.BSLotteryTerminal.get();
        qzEls.terminalName.value = terminal.name;

        const response = await fetch('{{ route('admin.printers.qz.current-terminal', [], false) }}?terminal_key=' + encodeURIComponent(terminal.key), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        if (!payload.printer) {
            return;
        }

        qzEls.terminalName.value = payload.printer.terminal_name || terminal.name;
        qzEls.paperWidth.value = payload.printer.paper_width || '80MM';
        qzEls.autoCut.value = payload.printer.auto_cut ? '1' : '0';
        qzEls.printerSelect.dataset.preferred = payload.printer.printer_name || '';
        qzEls.message.textContent = payload.printer.last_test_at
            ? 'Ultima prueba: ' + payload.printer.last_test_at
            : 'Terminal con impresora guardada.';
    }

    async function saveCurrentTerminal() {
        const printerName = qzEls.printerSelect.value;
        if (!printerName) {
            alert('Selecciona una impresora primero.');
            return;
        }

        const terminal = window.BSLotteryTerminal.setName(qzEls.terminalName.value);
        qzEls.saveBtn.disabled = true;

        try {
            const response = await fetch('{{ route('admin.printers.qz.save-current-terminal', [], false) }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    terminal_key: terminal.key,
                    terminal_name: terminal.name,
                    printer_name: printerName,
                    paper_width: qzEls.paperWidth.value,
                    printing_mode: 'RAW_ESCPOS',
                    auto_cut: qzEls.autoCut.value === '1',
                }),
            });

            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'No se pudo guardar la impresora de esta terminal.');
            }

            qzEls.message.textContent = payload.message;
        } catch (error) {
            alert(error.message || 'No se pudo guardar la configuracion.');
        } finally {
            qzEls.saveBtn.disabled = false;
        }
    }

    async function testCurrentTerminal() {
        const printerName = qzEls.printerSelect.value;
        if (!printerName) {
            alert('Selecciona una impresora primero.');
            return;
        }

        const terminal = window.BSLotteryTerminal.setName(qzEls.terminalName.value);
        qzEls.testBtn.disabled = true;
        qzEls.message.textContent = 'Enviando prueba a ' + printerName + '...';

        try {
            await window.BSQZ.printTestReceipt({
                printerName,
                paperWidth: qzEls.paperWidth.value,
                autoCut: qzEls.autoCut.value === '1',
            });

            await fetch('{{ route('admin.printers.qz.mark-tested', [], false) }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ terminal_key: terminal.key }),
            });

            qzEls.message.textContent = 'Prueba enviada correctamente a ' + printerName + '.';
        } catch (error) {
            qzEls.message.textContent = 'Error de impresion: ' + (error.message || 'QZ Tray no respondio.');
            alert(error.message || 'No se pudo imprimir la prueba.');
        } finally {
            qzEls.testBtn.disabled = false;
        }
    }

    async function uploadQzCredentials() {
        if (!qzEls.digitalCertificateInput?.files?.length || !qzEls.privateKeyInput?.files?.length) {
            alert('Selecciona digital-certificate.txt y private-key.pem.');
            return;
        }

        qzEls.uploadCredentialsBtn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('digital_certificate', qzEls.digitalCertificateInput.files[0]);
            formData.append('private_key', qzEls.privateKeyInput.files[0]);

            const response = await fetch('{{ route('admin.printers.qz.credentials.upload', [], false) }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: formData,
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'No se pudieron cargar las credenciales de QZ.');
            }

            if (qzEls.credentialMessage) {
                qzEls.credentialMessage.textContent = payload.message;
            }
            alert('Credenciales QZ cargadas. Reinicia QZ Tray para aplicar el cambio.');
        } catch (error) {
            alert(error.message || 'No se pudieron cargar las credenciales de QZ.');
        } finally {
            qzEls.uploadCredentialsBtn.disabled = false;
        }
    }

    window.addEventListener('qztray:status', function (event) {
        const detail = event.detail || {};
        setQzStatus(!!detail.online, detail.error || qzEls.message.textContent);
    });

    qzEls.detectBtn?.addEventListener('click', detectPrinters);
    qzEls.testBtn?.addEventListener('click', testCurrentTerminal);
    qzEls.saveBtn?.addEventListener('click', saveCurrentTerminal);
    qzEls.uploadCredentialsBtn?.addEventListener('click', uploadQzCredentials);

    loadCurrentTerminal().catch(() => {});
    detectPrinters();
</script>
@endpush
@endsection
