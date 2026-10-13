/* ============================================================
   Ruta: debug/js/admin/tiend/debug-tiend.js
   Función: Escuchar exclusivamente evidencias del módulo TIEND.

   NIVELES:
   1. Inicio DEBUG
   2. Envío al DEBUG PHP
   3. Escuchar interacción
   4. Observar presencia de la ventana
============================================================ */
(() => {
    'use strict';

    const key = '__NURBAK_DEBUG_ADMIN_TIEND';
    const ruta = new URL(
        'debug/php/admin/tiend/debug-tiend.php',
        document.baseURI
    ).href;
    const selector =
        '[data-admi-tiend],[data-tiend-form],[data-tiend-alma],[data-tiend-asignar-form],[data-tiend-usuarios-mini]';

    if (window[key]) return;
    window[key] = true;

    /* ========================================================
       NIVEL 1 — ENVÍO
    ======================================================== */

    const envi = (paso, contexto = {}) => {
        try {
            const dat = new FormData();
            dat.append('paso', paso);
            Object.entries(contexto).forEach(([nom, val]) => {
                dat.append(
                    `contexto[${nom}]`,
                    String(val ?? '')
                );
            });

            fetch(ruta, {
                method: 'POST',
                body: dat,
                keepalive: true,
                cache: 'no-store',
                credentials: 'same-origin'
            }).catch(() => {});
        } catch (_) {
        }
    };

    envi('TIEND_ACTI', { archivo: 'tiend.js' });

    /* ========================================================
       NIVEL 2 — ERRORES
    ======================================================== */

    window.addEventListener('error', evento => {
        envi('TIEND_JSER', {
            mensaje: evento.message || 'Error JavaScript.',
            archivo: evento.filename || '',
            linea: evento.lineno || 0,
            columna: evento.colno || 0
        });
    }, true);

    window.addEventListener('unhandledrejection', evento => {
        const razon = evento.reason instanceof Error
            ? evento.reason
            : new Error(String(
                evento.reason ?? 'Promise rechazada.'
            ));

        envi('TIEND_PROM', {
            mensaje: razon.message,
            archivo: razon.stack || ''
        });
    }, true);

    /* ========================================================
       NIVEL 3 — ESCUCHAR INTERACCIÓN
    ======================================================== */

    const pertenece = elemento =>
        elemento instanceof Element &&
        !!elemento.closest(selector);

    const datos = elemento => ({
        tag: elemento?.tagName || '',
        id: elemento?.id || '',
        nombr: elemento?.getAttribute?.('name') || '',
        tipo: elemento?.getAttribute?.('type') || '',
        texto: String(elemento?.textContent || '').trim().slice(0, 120)
    });

    document.addEventListener('click', evento => {
        if (!pertenece(evento.target)) return;
        envi('TIEND_CLIC', datos(evento.target));
    }, true);

    document.addEventListener('input', evento => {
        if (!pertenece(evento.target)) return;
        envi('TIEND_INPU', datos(evento.target));
    }, true);

    document.addEventListener('change', evento => {
        if (!pertenece(evento.target)) return;
        envi('TIEND_CHAN', datos(evento.target));
    }, true);

    document.addEventListener('submit', evento => {
        if (!pertenece(evento.target)) return;
        envi('TIEND_SUBM', datos(evento.target));
    }, true);

    /* ========================================================
       NIVEL 4 — PRESENCIA DE LA VENTANA
    ======================================================== */

    const observar = () => {
        const ventana = document.querySelector(
            '[data-admi-tiend]'
        );

        if (!ventana) return;

        envi('TIEND_VENTANA_ACTIVA', {
            modulo: 'admi-tiend'
        });
    };

    const observer = new MutationObserver(observar);
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            observar,
            { once: true }
        );
    } else {
        observar();
    }
})();
