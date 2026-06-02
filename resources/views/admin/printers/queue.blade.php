@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-list-task me-2" style="color: var(--argon-info);"></i>Cola de impresión</h1>
        <p class="text-secondary mb-0">Estado actual de la cola para la terminal web y últimos mensajes de QZ Tray.</p>
    </div>
    <div class="d-flex gap-2">
        <button id="queueRefreshBtn" class="btn btn-outline-secondary" type="button">
            <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
        </button>
        <button id="queueClearBtn" class="btn btn-outline-danger" type="button">
            <i class="bi bi-stop-circle me-1"></i>Detener pendientes
        </button>
        <button id="queuePurgeBtn" class="btn btn-danger" type="button">
            <i class="bi bi-trash me-1"></i>Limpiar cola
        </button>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0">Terminal actual</h2>
            </div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-4 text-secondary">Key</dt>
                    <dd class="col-8"><code id="queueTerminalKey">—</code></dd>
                    <dt class="col-4 text-secondary">Nombre</dt>
                    <dd class="col-8" id="queueTerminalName">—</dd>
                    <dt class="col-4 text-secondary">Impresora</dt>
                    <dd class="col-8" id="queuePrinterName">—</dd>
                    <dt class="col-4 text-secondary">Papel</dt>
                    <dd class="col-8" id="queuePaperWidth">—</dd>
                    <dt class="col-4 text-secondary">Últ. prueba</dt>
                    <dd class="col-8" id="queueLastTest">—</dd>
                </dl>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0">Mensajes de QZ</h2>
            </div>
            <div class="card-body">
                <div id="qzQueueMessage" class="small text-secondary mb-3">Sin mensajes.</div>
                <ul id="qzMessageLog" class="list-group list-group-flush small"></ul>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0">Trabajos de impresión</h2>
                <span id="queueCountBadge" class="badge bg-secondary">0</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ticket</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Intentos</th>
                            <th>Mensaje</th>
                            <th>Creado</th>
                            <th>Impreso</th>
                        </tr>
                    </thead>
                    <tbody id="queueJobsBody">
                        <tr><td colspan="8" class="text-center text-secondary py-4">Cargando cola...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const queueEls = {
        refresh: document.getElementById('queueRefreshBtn'),
        clear: document.getElementById('queueClearBtn'),
        purge: document.getElementById('queuePurgeBtn'),
        terminalKey: document.getElementById('queueTerminalKey'),
        terminalName: document.getElementById('queueTerminalName'),
        printerName: document.getElementById('queuePrinterName'),
        paperWidth: document.getElementById('queuePaperWidth'),
        lastTest: document.getElementById('queueLastTest'),
        count: document.getElementById('queueCountBadge'),
        body: document.getElementById('queueJobsBody'),
        msg: document.getElementById('qzQueueMessage'),
        log: document.getElementById('qzMessageLog'),
    };

    const queueLabels = {
        statuses: { PENDING: 'Pendiente', PROCESSING: 'Procesando', FAILED: 'Fallido', PRINTED: 'Impreso' },
        types: { TICKET: 'Ticket', REPRINT: 'Reimpresión', TEST: 'Prueba' },
    };

    function renderMessages() {
        const messages = window.BSQZ?.getMessages?.() || [];
        queueEls.log.innerHTML = '';
        if (!messages.length) {
            queueEls.msg.textContent = 'Sin mensajes recientes de QZ Tray.';
            return;
        }

        queueEls.msg.textContent = messages[0].message;
        messages.forEach((entry) => {
            const item = document.createElement('li');
            item.className = 'list-group-item px-0';
            item.innerHTML = '<div class="fw-semibold">' + entry.message + '</div><div class="text-secondary">' + entry.at + '</div>';
            queueEls.log.appendChild(item);
        });
    }

    function renderJobs(payload) {
        const printer = payload.printer;
        queueEls.terminalKey.textContent = printer?.terminal_key || (window.BSLotteryTerminal?.get?.().key || '—');
        queueEls.terminalName.textContent = printer?.terminal_name || (window.BSLotteryTerminal?.get?.().name || '—');
        queueEls.printerName.textContent = printer?.printer_name || 'Sin impresora guardada';
        queueEls.paperWidth.textContent = printer?.paper_width || '—';
        queueEls.lastTest.textContent = printer?.last_test_at || '—';

        const jobs = payload.jobs || [];
        queueEls.count.textContent = jobs.length;
        queueEls.body.innerHTML = '';

        if (!jobs.length) {
            queueEls.body.innerHTML = '<tr><td colspan="8" class="text-center text-secondary py-4">No hay trabajos para esta terminal.</td></tr>';
            return;
        }

        jobs.forEach((job) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${job.id}</td>
                <td>${job.ticket_number || '—'}</td>
                <td>${queueLabels.types[job.type] || job.type}</td>
                <td>${queueLabels.statuses[job.status] || job.status}</td>
                <td>${job.attempts}</td>
                <td class="small text-secondary">${job.error_message || '—'}</td>
                <td class="small">${job.created_at || '—'}</td>
                <td class="small">${job.printed_at || '—'}</td>
            `;
            queueEls.body.appendChild(tr);
        });
    }

    async function loadQueue() {
        const terminal = window.BSLotteryTerminal?.get?.();
        if (!terminal?.key) {
            queueEls.body.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">No se encontró terminal_key en este navegador.</td></tr>';
            return;
        }

        const response = await fetch('{{ route('admin.printers.qz.queue-data', [], false) }}?terminal_key=' + encodeURIComponent(terminal.key), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const payload = await response.json();
        renderJobs(payload);
        renderMessages();
    }

    async function clearQueue() {
        const terminal = window.BSLotteryTerminal?.get?.();
        if (!terminal?.key) {
            return;
        }

        queueEls.clear.disabled = true;

        try {
            const response = await fetch('{{ route('admin.printers.qz.queue.clear', [], false) }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ terminal_key: terminal.key }),
            });

            const payload = await response.json();
            queueEls.msg.textContent = payload.message || 'Cola actualizada.';
            await loadQueue();
        } finally {
            queueEls.clear.disabled = false;
        }
    }

    async function purgeQueue() {
        const terminal = window.BSLotteryTerminal?.get?.();
        if (!terminal?.key) {
            return;
        }

        if (!confirm('Esto eliminara todos los trabajos visibles de esta terminal.')) {
            return;
        }

        queueEls.purge.disabled = true;

        try {
            const response = await fetch('{{ route('admin.printers.qz.queue.purge', [], false) }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ terminal_key: terminal.key }),
            });

            const payload = await response.json();
            queueEls.msg.textContent = payload.message || 'Cola eliminada.';
            await loadQueue();
        } finally {
            queueEls.purge.disabled = false;
        }
    }

    queueEls.refresh.addEventListener('click', loadQueue);
    queueEls.clear.addEventListener('click', clearQueue);
    queueEls.purge.addEventListener('click', purgeQueue);
    window.addEventListener('qztray:status', renderMessages);
    loadQueue();
    setInterval(loadQueue, 7000);
</script>
@endpush
@endsection
