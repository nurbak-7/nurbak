/* ============================================================
   Ruta: debug/js/dash/modal/debug-modal.js
   Función: Coordinar exclusivamente el DEBUG de su nivel.

   RESPONSABILIDAD ÚNICA:
   - Registrar su propia actividad DEBUG.
   - Cargar únicamente los DEBUG que pertenecen a este nivel.
   - Evitar cargas duplicadas.

   INDEPENDENCIA:
   - No incluye archivos funcionales.
   - No modifica APIs del navegador.
   - No altera la lógica funcional.
============================================================ */
(() => {
    'use strict';

    const key = '__NURBAK_DEBUG_DASH_MODAL';
    const endpoint = new URL('debug/php/dash/modal/debug-modal.php', document.baseURI).href;
    const archivos = [
        'debug/js/dash/modal/debug-accio.js',
        'debug/js/dash/modal/debug-carga.js',
        'debug/js/dash/modal/debug-event.js',
        'debug/js/dash/modal/debug-movim.js',
        'debug/js/dash/modal/debug-recup.js',
        'debug/js/dash/modal/debug-rutas.js',
    ];

    if (window[key]) return;
    window[key] = true;

    const enviar = (paso, contexto = {}) => {
        try {
            const dat = new FormData();
            dat.append('paso', paso);
            Object.entries(contexto).forEach(([nom, val]) => {
                dat.append(`contexto[${nom}]`, String(val ?? ''));
            });
            fetch(endpoint, {
                method: 'POST',
                body: dat,
                keepalive: true,
                cache: 'no-store',
                credentials: 'same-origin'
            }).catch(() => {});
        } catch (_) {}
    };

    const cargar = ruta => new Promise((resolver, rechazar) => {
        if (document.querySelector(`[data-debug-js="${ruta}"]`)) {
            resolver();
            return;
        }

        const script = document.createElement('script');
        script.src = ruta;
        script.dataset.debugJs = ruta;
        script.addEventListener('load', resolver, { once: true });
        script.addEventListener('error', () =>
            rechazar(new Error(`No se pudo cargar DEBUG: ${ruta}`)),
            { once: true }
        );
        document.body.appendChild(script);
    });

    enviar('MODAL_ACTI', {
        archivo: 'debug-modal.js'
    });

    window.addEventListener('error', evento => {
        enviar('MODAL_JSER', {
            mensaje: evento.message || 'Error JavaScript.',
            archivo: evento.filename || '',
            linea: evento.lineno || 0,
            columna: evento.colno || 0
        });
    }, true);

    window.addEventListener('unhandledrejection', evento => {
        const razon = evento.reason instanceof Error
            ? evento.reason
            : new Error(String(evento.reason ?? 'Promise rechazada.'));
        enviar('MODAL_PROM', {
            mensaje: razon.message,
            archivo: razon.stack || ''
        });
    }, true);

    

    (async () => {
        for (const ruta of archivos) {
            try {
                await cargar(ruta);
                enviar('MODAL_CARG', { ruta });
            } catch (error) {
                enviar('MODAL_CARG_ERR', {
                    ruta,
                    mensaje: error.message
                });
            }
        }
    })();
})();
