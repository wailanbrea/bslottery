(function () {
    const storageKey = 'bslottery_terminal_v1';
    const messagesKey = 'bslottery_qz_messages_v1';
    const qzConfig = window.bsQz || {};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const terminalPrefix = 'WEBTERM-';
    let qzConfigured = false;
    let pollingTimer = null;
    let processingQueue = false;
    const recentlyProcessed = new Map();

    function randomKey() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        let output = '';
        for (let i = 0; i < 10; i += 1) {
            output += chars[Math.floor(Math.random() * chars.length)];
        }

        return terminalPrefix + output;
    }

    function readTerminal() {
        try {
            const raw = window.localStorage.getItem(storageKey);
            const parsed = raw ? JSON.parse(raw) : null;

            if (parsed && parsed.key) {
                if (!parsed.name) {
                    parsed.name = parsed.key;
                }

                return parsed;
            }
        } catch (_error) {}

        const created = {
            key: randomKey(),
            name: 'Caja web',
        };

        writeTerminal(created);

        return created;
    }

    function writeTerminal(terminal) {
        window.localStorage.setItem(storageKey, JSON.stringify(terminal));
        fillTerminalInputs();
    }

    function fillTerminalInputs() {
        const terminal = readTerminal();

        document.querySelectorAll('[data-terminal-key-input]').forEach((input) => {
            input.value = terminal.key;
        });

        document.querySelectorAll('[data-terminal-name-input]').forEach((input) => {
            input.value = terminal.name;
        });
    }

    function fireStatus(detail) {
        window.dispatchEvent(new CustomEvent('qztray:status', { detail }));
    }

    function readMessages() {
        try {
            return JSON.parse(window.localStorage.getItem(messagesKey) || '[]');
        } catch (_error) {
            return [];
        }
    }

    function writeMessages(messages) {
        window.localStorage.setItem(messagesKey, JSON.stringify(messages.slice(0, 20)));
    }

    function logMessage(message, extra) {
        const at = new Date().toLocaleString('es-DO');
        const current = readMessages();
        current.unshift({
            at,
            message: extra ? (message + ' ' + extra) : message,
        });
        writeMessages(current);

        const indicator = document.getElementById('printAgentIndicator');
        if (indicator) {
            indicator.classList.remove('d-none');
            const text = indicator.querySelector('span');
            const icon = indicator.querySelector('i');
            if (text) {
                text.textContent = message;
                text.className = 'small ' + (/error|fall|no disponible|bloque/i.test(message) ? 'text-danger' : 'text-success');
            }
            if (icon) {
                icon.className = 'bi bi-printer me-1 ' + (/error|fall|no disponible|bloque/i.test(message) ? 'text-danger' : 'text-success');
            }
        }
    }

    function rememberProcessed(uuid) {
        if (!uuid) {
            return;
        }

        const now = Date.now();
        recentlyProcessed.set(uuid, now);

        for (const [key, timestamp] of recentlyProcessed.entries()) {
            if ((now - timestamp) > 60000) {
                recentlyProcessed.delete(key);
            }
        }
    }

    function wasRecentlyProcessed(uuid) {
        if (!uuid) {
            return false;
        }

        const timestamp = recentlyProcessed.get(uuid);
        return typeof timestamp === 'number' && (Date.now() - timestamp) < 60000;
    }

    function setupQzSecurity() {
        if (qzConfigured || !window.qz) {
            return;
        }

        qz.security.setCertificatePromise(function (resolve) {
            fetch(qzConfig.certificateUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'text/plain' },
            })
                .then((response) => response.ok ? response.text() : '')
                .then((certificate) => resolve(certificate || ''))
                .catch(() => resolve(''));
        });

        qz.security.setSignaturePromise(function (toSign) {
            return function (resolve, reject) {
                fetch(qzConfig.signUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/plain',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ request: toSign }),
                })
                    .then((response) => response.ok ? response.text() : '')
                    .then((signature) => resolve(signature || ''))
                    .catch(() => resolve(''));
            };
        });

        qzConfigured = true;
    }

    async function connectQzTray() {
        if (!window.qz) {
            throw new Error('qz-tray.js no esta cargado.');
        }

        if (qz.websocket.isActive()) {
            fireStatus({ online: true });
            logMessage('QZ Tray conectado');
            return true;
        }

        setupQzSecurity();
        await qz.websocket.connect({
            retries: 0,
            delay: 0,
        });
        fireStatus({ online: true });
        logMessage('QZ Tray conectado');
        startPolling();

        return true;
    }

    async function disconnectQzTray() {
        if (window.qz && qz.websocket.isActive()) {
            await qz.websocket.disconnect();
        }

        stopPolling();
        fireStatus({ online: false });
        logMessage('QZ Tray desconectado');
    }

    async function getAvailablePrinters() {
        await connectQzTray();
        const printers = await qz.printers.find();

        return Array.isArray(printers) ? printers : [printers].filter(Boolean);
    }

    async function getDefaultPrinter() {
        await connectQzTray();

        try {
            return await qz.printers.getDefault();
        } catch (_error) {
            return null;
        }
    }

    function bytesToString(bytes) {
        let out = '';
        for (let i = 0; i < bytes.length; i += 1) {
            out += String.fromCharCode(bytes[i]);
        }

        return out;
    }

    function buildQrCommand(data) {
        const encoder = new TextEncoder();
        const payload = encoder.encode(data);
        const storeLength = payload.length + 3;
        const pL = storeLength & 0xFF;
        const pH = (storeLength >> 8) & 0xFF;
        const bytes = [
            0x1D, 0x28, 0x6B, 0x04, 0x00, 0x31, 0x41, 0x32, 0x00,
            0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x43, 0x06,
            0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x45, 0x31,
            0x1D, 0x28, 0x6B, pL, pH, 0x31, 0x50, 0x30,
            ...payload,
            0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x51, 0x30,
            0x0A,
        ];

        return bytesToString(bytes);
    }

    function buildEscPosPayload(content, autoCut) {
        const lines = String(content || '').split('\n');
        let raw = '\x1B@\x1Ba\x00\x1DE\x00\x1D!\x00';

        lines.forEach((line) => {
            const trimmed = line.trim();
            const qrMatch = trimmed.match(/^\[\[QR:(.+?)]]$/);

            if (qrMatch) {
                raw += '\x1Ba\x01';
                raw += buildQrCommand(qrMatch[1]);
                raw += '\x1Ba\x00';
                return;
            }

            raw += line + '\r\n';
        });

        raw += '\n\n';
        if (autoCut) {
            raw += '\x1DV\x41\x05';
        }

        return [raw];
    }

    async function printRaw(job) {
        await connectQzTray();

        const config = qz.configs.create(job.printer_name, {
            encoding: 'Cp1252',
        });

        return qz.print(config, buildEscPosPayload(job.content, job.auto_cut !== false));
    }

    function buildTestReceipt(options) {
        const terminal = readTerminal();
        const width = String(options.paperWidth || '80MM').toUpperCase() === '58MM' ? 32 : 56;
        const divider = '-'.repeat(width);
        const center = function (text) {
            const clean = String(text || '').slice(0, width);
            const spaces = Math.max(0, width - clean.length);
            const left = Math.floor(spaces / 2);
            return ' '.repeat(left) + clean;
        };

        return [
            center('SISTEMA DE LOTERIA'),
            center('PRUEBA DE IMPRESION'),
            '',
            'Terminal: ' + terminal.name,
            'Key: ' + terminal.key,
            'Impresora:',
            options.printerName,
            divider,
            'Conexion con QZ Tray correcta',
            'Impresion termica configurada',
            divider,
            '',
            center('PRUEBA EXITOSA'),
        ].join('\n');
    }

    async function printTestReceipt(options) {
        const payload = {
            printer_name: options.printerName,
            content: buildTestReceipt(options),
            auto_cut: options.autoCut !== false,
        };

        await printRaw(payload);
        logMessage('Prueba enviada a impresora', options.printerName);
    }

    async function ackJob(uuid, status, errorMessage) {
        const response = await fetch(qzConfig.ackBaseUrl + '/' + encodeURIComponent(uuid) + '/ack', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                status,
                error_message: errorMessage || null,
            }),
        });

        if (!response.ok) {
            let message = 'No se pudo confirmar el trabajo en el servidor.';

            try {
                const payload = await response.json();
                if (payload?.message) {
                    message = payload.message;
                }
            } catch (_error) {}

            throw new Error(message);
        }
    }

    async function processPendingJobs() {
        if (processingQueue) {
            return;
        }

        if (!window.qz || !qz.websocket.isActive()) {
            return;
        }

        processingQueue = true;
        const terminal = readTerminal();

        try {
            const response = await fetch(qzConfig.pendingUrl, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Terminal-Key': terminal.key,
                },
            });

            if (!response.ok) {
                return;
            }

            const jobs = await response.json();
            for (const job of jobs) {
                if (wasRecentlyProcessed(job.uuid)) {
                    continue;
                }

                try {
                    await printRaw(job);
                    rememberProcessed(job.uuid);
                    await ackJob(job.uuid, 'PRINTED');
                    logMessage('Trabajo impreso', job.ticket_number || job.uuid);
                } catch (error) {
                    try {
                        await ackJob(job.uuid, 'FAILED', error?.message || 'No se pudo imprimir con QZ Tray.');
                    } catch (ackError) {
                        rememberProcessed(job.uuid);
                        logMessage('Error al confirmar cola', ackError?.message || job.uuid);
                    }

                    logMessage('Error en trabajo de impresión', error?.message || job.uuid);
                }
            }
        } catch (_error) {
            fireStatus({ online: false });
            logMessage('QZ no pudo consultar la cola');
        } finally {
            processingQueue = false;
        }
    }

    function startPolling() {
        if (pollingTimer) {
            return;
        }

        pollingTimer = window.setInterval(processPendingJobs, 7000);
        processPendingJobs();
    }

    function stopPolling() {
        if (!pollingTimer) {
            return;
        }

        window.clearInterval(pollingTimer);
        pollingTimer = null;
    }

    window.BSLotteryTerminal = {
        get: readTerminal,
        setName(name) {
            const terminal = readTerminal();
            terminal.name = String(name || '').trim() || terminal.key;
            writeTerminal(terminal);
            return terminal;
        },
        fillInputs: fillTerminalInputs,
    };

    window.BSQZ = {
        connectQzTray,
        disconnectQzTray,
        getAvailablePrinters,
        getDefaultPrinter,
        printTestReceipt,
        printRaw,
        processPendingJobs,
        buildTestReceipt,
        getMessages: readMessages,
    };

    document.addEventListener('DOMContentLoaded', function () {
        fillTerminalInputs();
        if (window.bsQzAutoConnect) {
            connectQzTray().catch(function (error) {
                fireStatus({ online: false, error: error?.message || 'QZ Tray no disponible.' });
                logMessage('QZ Tray no disponible', error?.message || '');
            });
        }
    });
})();
