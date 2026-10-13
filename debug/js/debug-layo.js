/* ============================================================
   Ruta: debug/js/debug-layo.js
   Función: Coordinador raíz independiente del DEBUG.

   RESPONSABILIDAD:
   - Activar el DEBUG raíz.
   - Escuchar errores JavaScript.
   - Escuchar promesas rechazadas.
   - Escuchar fetch() del sistema.
   - Resumir respuestas y errores HTTP/PHP/SQL.
   - Cargar los coordinadores DEBUG secundarios.

   El DEBUG nunca modifica la lógica funcional del sistema.
============================================================ */
(() => {
    'use strict';

    const key = '__NURBAK_DEBUG_LAYO';
    const endpoint = new URL('debug/php/debug-layo.php', document.baseURI).href;

    const archivos = [
        'debug/js/confi/debug-confi.js',
        'debug/js/contr/debug-contr.js',
        'debug/js/datos/debug-datos.js',
        'debug/js/modul/debug-modul.js',
        'debug/js/vista/debug-vista.js',
        'debug/js/publi/debug-publi.js',
        'debug/js/debug-categ.js',
        'debug/js/dash/debug-dash.js',
        'debug/js/admin/debug-admin.js',
        'debug/js/core/debug-toast.js'
    ];

    if (window[key]) return;
    window[key] = true;

    /* ========================================================
       NIVEL 1
       Enviar un evento al receptor raíz.
    ======================================================== */

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

    /* ========================================================
       NIVEL 5
       Errores JavaScript globales.
    ======================================================== */

    window.addEventListener('error', evento => {
        enviar('LAYO_JSER', {
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

        enviar('LAYO_PROM', {
            mensaje: razon.message,
            archivo: razon.stack || ''
        });
    }, true);

    /* ========================================================
       NIVEL 6
       Cargar los coordinadores DEBUG secundarios.
    ======================================================== */

    const cargar = ruta => new Promise((resolver, rechazar) => {
        if (document.querySelector(`[data-debug-js="${ruta}"]`)) {
            resolver();
            return;
        }

        const script = document.createElement('script');
        script.src = ruta;
        script.dataset.debugJs = ruta;

        script.addEventListener('load', resolver, { once: true });

        script.addEventListener('error', () => {
            rechazar(new Error(`No se pudo cargar DEBUG: ${ruta}`));
        }, { once: true });

        (document.head || document.documentElement).appendChild(script);
    });

    enviar('LAYO_ACTI', {
        archivo: 'debug-layo.js'
    });

    (async () => {
        for (const ruta of archivos) {
            try {
                await cargar(ruta);

                enviar('LAYO_CARG', {
                    ruta
                });
            } catch (error) {
                enviar('LAYO_CARG_ERR', {
                    ruta,
                    mensaje: error.message
                });
            }
        }
    })();
})();
