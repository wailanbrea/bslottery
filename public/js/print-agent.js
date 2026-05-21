/**
 * BSLotery Print Agent Bridge v1.0
 * Connects the web frontend with the local Java Print Agent (127.0.0.1:8765).
 *
 * Usage:
 *   window.PrintAgent.check()          — ping agent, update UI indicator
 *   window.PrintAgent.print(job)       — print a job object {uuid,content,printer_name,connection_type}
 *   window.PrintAgent.processPending() — fetch + print all pending jobs from Laravel API
 */
(function () {
    'use strict';

    const AGENT_URL    = window.bsPrintAgentUrl   || 'http://127.0.0.1:8765';
    const AGENT_TOKEN  = window.bsPrintAgentToken || '';
    const API_BASE     = window.bsApiBase          || '/api';

    let agentAvailable = false;

    function headers(includeAuth) {
        const h = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
        if (includeAuth && AGENT_TOKEN) h['Authorization'] = 'Bearer ' + AGENT_TOKEN;
        return h;
    }

    async function agentFetch(path, options = {}) {
        return fetch(AGENT_URL + path, {
            ...options,
            headers: { ...headers(true), ...(options.headers || {}) },
            signal: AbortSignal.timeout(4000),
        });
    }

    async function laravelFetch(path, options = {}) {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const h = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };
        if (csrfMeta) h['X-CSRF-TOKEN'] = csrfMeta.content;
        return fetch(path, { ...options, headers: { ...h, ...(options.headers || {}) } });
    }

    /** Check if the agent is running and update the status indicator. */
    async function check() {
        try {
            const res = await fetch(AGENT_URL + '/api/status', {
                signal: AbortSignal.timeout(2000),
            });
            agentAvailable = res.ok;
        } catch {
            agentAvailable = false;
        }
        updateIndicator();
        return agentAvailable;
    }

    /** Send a single print job to the agent. */
    async function printJob(job) {
        if (!agentAvailable) {
            console.warn('[PrintAgent] Agente no disponible. Job:', job.uuid);
            return { success: false, error: 'Agente no disponible' };
        }

        try {
            const res = await agentFetch('/api/print', {
                method: 'POST',
                body: JSON.stringify({
                    job_uuid:        job.uuid,
                    printer_name:    job.printer_name || '',
                    connection_type: job.connection_type || 'USB',
                    content:         job.content,
                    paper_width:     job.paper_width || '58MM',
                }),
            });

            const data = await res.json();
            await ackJob(job.uuid, data.success ? 'PRINTED' : 'FAILED', data.error || null);
            return data;
        } catch (err) {
            await ackJob(job.uuid, 'FAILED', err.message);
            return { success: false, error: err.message };
        }
    }

    /** Report print result back to Laravel. */
    async function ackJob(uuid, status, errorMessage) {
        try {
            await laravelFetch(API_BASE + '/print-jobs/' + uuid + '/ack', {
                method: 'POST',
                body: JSON.stringify({ status, error_message: errorMessage }),
            });
        } catch (err) {
            console.warn('[PrintAgent] Error reportando ack:', err);
        }
    }

    /** Fetch all pending print jobs from Laravel and dispatch them to the agent. */
    async function processPending() {
        if (!agentAvailable) return;

        try {
            const res = await laravelFetch(API_BASE + '/print-jobs/pending');
            if (!res.ok) return;

            const jobs = await res.json();
            if (!Array.isArray(jobs) || jobs.length === 0) return;

            console.log('[PrintAgent] Procesando', jobs.length, 'job(s) pendiente(s).');
            for (const job of jobs) {
                await printJob(job);
            }
        } catch (err) {
            console.warn('[PrintAgent] Error procesando pendientes:', err);
        }
    }

    /** Update the status indicator in the layout. */
    function updateIndicator() {
        const el = document.getElementById('printAgentIndicator');
        if (!el) return;

        if (agentAvailable) {
            el.innerHTML = '<i class="bi bi-printer-fill text-success me-1"></i><span class="text-success small">Agente</span>';
            el.title = 'Print Agent activo';
        } else {
            el.innerHTML = '<i class="bi bi-printer text-danger me-1"></i><span class="text-danger small">Sin agente</span>';
            el.title = 'Print Agent no encontrado. Inicie bslottery-print-agent.jar';
        }
    }

    /** Print a job directly (called from inline JS after sale/reprint). */
    async function printDirect(job) {
        const ok = await check();
        if (ok) {
            return printJob(job);
        }
        return { success: false, error: 'Agente no disponible' };
    }

    // Public API
    window.PrintAgent = { check, print: printJob, printDirect, processPending };

    // Auto-initialize on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', async () => {
        await check();
        await processPending();
    });

})();
