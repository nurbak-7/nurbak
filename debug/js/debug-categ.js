/* ============================================================
   Ruta: debug/js/debug-categ.js
   Función: Coordinador de las categorías del DEBUG.
============================================================ */
(() => {
    'use strict';

    const key = '__NURBAK_DEBUG_CATEG_CATEG';
    const endpoint = new URL('debug/php/debug-categ.php', document.baseURI).href;
    const archivos = [
        'debug/js/categ/catal/debug-produ.js',
        'debug/js/categ/catal/debug-vehic.js',
        'debug/js/categ/catal/debug-equiv.js',
        'debug/js/categ/catal/artic/debug-artic.js',
        'debug/js/categ/catal/asinc/debug-asinc.js',
        'debug/js/categ/catal/imext/debug-imext.js',
        'debug/js/categ/compr/debug-prove.js',
        'debug/js/categ/compr/debug-comex.js',
        'debug/js/categ/compr/debug-orcex.js',
        'debug/js/categ/compr/debug-recep.js',
        'debug/js/categ/inven/debug-almac.js',
        'debug/js/categ/inven/debug-stock.js',
        'debug/js/categ/inven/debug-movim.js',
        'debug/js/categ/inven/debug-ordco.js',
        'debug/js/categ/pagos/debug-pagos.js',
        'debug/js/categ/pagos/debug-caja.js',
        'debug/js/categ/pagos/debug-credi.js',
        'debug/js/categ/repor/debug-infor.js',
        'debug/js/categ/repor/debug-audit.js',
        'debug/js/categ/factu/debug-factu.js',
        'debug/js/categ/factu/debug-bolet.js',
        'debug/js/categ/factu/debug-ncred.js',
        'debug/js/categ/factu/debug-ndebi.js',
        'debug/js/categ/factu/debug-gremi.js',
        'debug/js/categ/factu/debug-sunat.js',
        'debug/js/categ/venta/debug-clien.js',
        'debug/js/categ/venta/debug-cotiz.js',
        'debug/js/categ/venta/debug-nvent.js',
        'debug/js/categ/venta/debug-devol.js',
        'debug/js/categ/venta/debug-opera.js'
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
        script.addEventListener('error', () => {
            rechazar(new Error(`No se pudo cargar DEBUG: ${ruta}`));
        }, { once: true });
        document.body.appendChild(script);
    });

    enviar('CATEG_ACTI', { archivo: 'debug-categ.js' });

    window.addEventListener('error', evento => {
        enviar('CATEG_JSER', {
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
        enviar('CATEG_PROM', {
            mensaje: razon.message,
            archivo: razon.stack || ''
        });
    }, true);

    (async () => {
        for (const ruta of archivos) {
            try {
                await cargar(ruta);
                enviar('CATEG_CARG', { ruta });
            } catch (error) {
                enviar('CATEG_CARG_ERR', {
                    ruta,
                    mensaje: error.message
                });
            }
        }
    })();
})();
