/* ============================================================
   Ruta: debug/js/categ/catal/asinc/imext/expor/debug-expor.js
   Función: Escuchar exclusivamente evidencias del DEBUG correspondiente.
============================================================ */
(() => {
    'use strict';

    const key = '__NURBAK_DEBUG_CATAL_ASINC_EXPOR';
    const ruta = new URL('debug/php/categ/catal/asinc/imext/expor/debug-expor.php', document.baseURI).href;
    const selector = '[data-asinc-expor]';

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
    envi('EXPOR_ACTI', { archivo: 'debug-expor.js' });

    window.addEventListener('error', evento => {
        envi('EXPOR_JSER', {
            mensaje: evento.message || 'Error JavaScript.',
            archivo: evento.filename || '', linea: evento.lineno || 0, columna: evento.colno || 0
        });
    }, true);

    window.addEventListener('unhandledrejection', evento => {
        const razon = evento.reason instanceof Error ? evento.reason : new Error(String(evento.reason ?? 'Promise rechazada.'));
        envi('EXPOR_PROM', { mensaje: razon.message, archivo: razon.stack || '' });
    }, true);

    document.addEventListener('click', evento => {
        if (!pertenece(evento.target) || !selector) return;
        envi('EXPOR_CLIC', { tag: evento.target?.tagName || '', id: evento.target?.id || '' });
    }, true);
})();
