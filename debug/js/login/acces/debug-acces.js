/* ============================================================
   Ruta: debug/js/login/acces/debug-acces.js
   Función: Escuchar exclusivamente evidencias del DEBUG correspondiente.
============================================================ */
(() => {
    'use strict';

    const key = '__NURBAK_DEBUG_LOGIN_ACCES';
    const ruta = new URL('debug/php/login/acces/debug-acces.php', document.baseURI).href;
    const selector = '[data-acces],[data-login]';

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
    envi('ACCES_ACTI', { archivo: 'debug-acces.js' });

    window.addEventListener('error', evento => {
        envi('ACCES_JSER', {
            mensaje: evento.message || 'Error JavaScript.',
            archivo: evento.filename || '', linea: evento.lineno || 0, columna: evento.colno || 0
        });
    }, true);

    window.addEventListener('unhandledrejection', evento => {
        const razon = evento.reason instanceof Error ? evento.reason : new Error(String(evento.reason ?? 'Promise rechazada.'));
        envi('ACCES_PROM', { mensaje: razon.message, archivo: razon.stack || '' });
    }, true);

    document.addEventListener('click', evento => {
        if (!pertenece(evento.target) || !selector) return;
        envi('ACCES_CLIC', { tag: evento.target?.tagName || '', id: evento.target?.id || '' });
    }, true);
})();
