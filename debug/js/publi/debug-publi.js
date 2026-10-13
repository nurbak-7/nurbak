/* ============================================================
   Ruta: debug/js/publi/debug-publi.js
   Función: Coordinador independiente del DEBUG de publi.

   RESPONSABILIDAD:
   - Registrar la activación de su capa.
   - Escuchar errores JavaScript generales sin alterar el sistema.
   - No ejecutar lógica funcional.
============================================================ */
(() => {
    'use strict';

    const key = '__NURBAK_DEBUG_PUBLI_PUBLI';
    const ruta = new URL('debug/php/publi/debug-publi.php', document.baseURI).href;

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

    envi('PUBLI_ACTI', { archivo: 'debug-publi.js' });

    window.addEventListener('error', evento => {
        envi('PUBLI_JSER', {
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
        envi('PUBLI_PROM', {
            mensaje: razon.message,
            archivo: razon.stack || ''
        });
    }, true);
})();
