/* ============================================================
   Ruta: debug/js/categ/catal/asinc/imext/debug-imext.js
   Función: Escuchar exclusivamente evidencias del DEBUG correspondiente.
============================================================ */
(() => {
    'use strict';

    const key = '__NURBAK_DEBUG_CATAL_ASINC_IMEXT';
    const ruta = new URL('debug/php/categ/catal/asinc/imext/debug-imext.php', document.baseURI).href;
    const selector = '[data-asinc-imext]';
    const archivos = [
        'debug/js/categ/catal/asinc/imext/impor/debug-impor.js',
        'debug/js/categ/catal/asinc/imext/expor/debug-expor.js'
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
                method: 'POST', body: dat, keepalive: true,
                cache: 'no-store', credentials: 'same-origin'
            }).catch(() => {});
        } catch (_) {}
    };

    const pertenece = el => !selector || !(el instanceof Element) || Boolean(el.closest(selector));
    envi('IMEXT_ACTI', { archivo: 'debug-imext.js' });

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

    (async () => {
        for (const archivo of archivos) {
            try {
                await cargar(archivo);
                envi('IMEXT_CARG', { archivo });
            } catch (error) {
                envi('IMEXT_CARG_ERR', {
                    archivo,
                    mensaje: error.message
                });
            }
        }
    })();

    window.addEventListener('error', evento => {
        envi('IMEXT_JSER', {
            mensaje: evento.message || 'Error JavaScript.',
            archivo: evento.filename || '', linea: evento.lineno || 0, columna: evento.colno || 0
        });
    }, true);

    window.addEventListener('unhandledrejection', evento => {
        const razon = evento.reason instanceof Error ? evento.reason : new Error(String(evento.reason ?? 'Promise rechazada.'));
        envi('IMEXT_PROM', { mensaje: razon.message, archivo: razon.stack || '' });
    }, true);

    document.addEventListener('click', evento => {
        if (!pertenece(evento.target) || !selector) return;
        envi('IMEXT_CLIC', { tag: evento.target?.tagName || '', id: evento.target?.id || '' });
    }, true);
})();
