/* ============================================================
   Ruta: debug/js/dash/logi/debug-logi.js
   Función: Coordinar DEBUG del login interno del dashboard.
============================================================ */
(() => {
    'use strict';

    const key = '__NURBAK_DEBUG_DASH_LOGI';
    const ruta = new URL('debug/php/dash/logi/debug-logi.php', document.baseURI).href;
    const archivos = [
        'debug/js/dash/logi/debug-acces.js'
    ];

    if (window[key]) return;
    window[key] = true;

    const envi = (paso, contexto = {}) => {
        try {
            const dat = new FormData();
            dat.append('paso', paso);
            Object.entries(contexto).forEach(([nom, val]) => {
                dat.append(`contexto[${nom}]`, String(val ?? ''));
            });
            fetch(ruta, {
                method: 'POST',
                body: dat,
                keepalive: true,
                cache: 'no-store',
                credentials: 'same-origin'
            }).catch(() => {});
        } catch (_) {}
    };

    const cargar = archivo => new Promise((resolver, rechazar) => {
        if (document.querySelector(`[data-debug-js="${archivo}"]`)) {
            resolver();
            return;
        }
        const script = document.createElement('script');
        script.src = archivo;
        script.dataset.debugJs = archivo;
        script.addEventListener('load', resolver, { once: true });
        script.addEventListener('error', () => {
            rechazar(new Error(`No se pudo cargar DEBUG: ${archivo}`));
        }, { once: true });
        document.body.appendChild(script);
    });

    envi('LOGI_ACTI', { archivo: 'debug-logi.js' });

    (async () => {
        for (const archivo of archivos) {
            try {
                await cargar(archivo);
                envi('LOGI_CARG', { archivo });
            } catch (error) {
                envi('LOGI_CARG_ERR', {
                    archivo,
                    mensaje: error.message
                });
            }
        }
    })();

    window.addEventListener('error', evento => {
        envi('LOGI_JSER', {
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
        envi('LOGI_PROM', {
            mensaje: razon.message,
            archivo: razon.stack || ''
        });
    }, true);
})();
