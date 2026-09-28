(function () {
    'use strict';

    const STORAGE_KEY = 'bslottery.print_terminal';
    const AGENT_URL = 'http://127.0.0.1:8765';
    const POLL_INTERVAL_MS = 2200;
    let terminal = readTerminal();
    let polling = false;

    function readTerminal() {
        try {
            const value = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || 'null');
            return value && typeof value === 'object' ? value : null;
        } catch (_) {
            return null;
        }
    }

    function saveTerminal(value) {
        terminal = value && value.key ? value : null;
        if (terminal) {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(terminal));
        }
    }

    async function requestJson(url, options) {
        const response = await fetch(url, options);
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(payload.message || ('Solicitud fallida: ' + response.status));
        }
        return payload;
    }

    async function discoverTerminal() {
        try {
            const payload = await requestJson('/api/print-agent/config', {
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (payload.terminal?.key) {
                saveTerminal(payload.terminal);
            }
        } catch (_) {
            // The user may be on a public page or logged out; retry on the next page.
        }
    }

    async function agentIsAvailable() {
        try {
            await requestJson(AGENT_URL + '/api/status', {
                headers: { Accept: 'application/json' },
            });
            return true;
        } catch (_) {
            return false;
        }
    }

    async function acknowledge(uuid, status, errorMessage) {
        return requestJson('/api/print-jobs/' + encodeURIComponent(uuid) + '/ack', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ status, error_message: errorMessage || null }),
        });
    }

    async function printJob(job) {
        const response = await requestJson(AGENT_URL + '/api/print', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ printer: job.printer_name || null, content: job.content || '' }),
        });

        if (response.status !== 'printed') {
            throw new Error(response.message || 'El agente no confirmó la impresión.');
        }
    }

    async function poll() {
        if (polling || !terminal?.key || !(await agentIsAvailable())) {
            return;
        }

        polling = true;
        try {
            const payload = await requestJson(
                '/api/print-jobs/pending?terminal_key=' + encodeURIComponent(terminal.key),
                {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Terminal-Key': terminal.key,
                    },
                },
            );

            for (const job of Array.isArray(payload) ? payload : []) {
                try {
                    await printJob(job);
                    await acknowledge(job.uuid, 'PRINTED');
                } catch (error) {
                    await acknowledge(job.uuid, 'FAILED', error.message || 'Error de impresión local.');
                }
            }
        } catch (_) {
            // A disconnected agent or network is retried by the next cycle.
        } finally {
            polling = false;
        }
    }

    window.BSLotteryTerminal = {
        get: function () { return terminal; },
        refresh: discoverTerminal,
        agentUrl: AGENT_URL,
    };

    discoverTerminal().then(poll);
    window.setInterval(poll, POLL_INTERVAL_MS);
}());
