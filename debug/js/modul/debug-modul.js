/* ============================================================
   Ruta: debug/js/modul/debug-modul.js
   Función: Coordinar únicamente los DEBUG funcionales de MODUL.
============================================================ */
(() => {
    'use strict';

    const key = '__NURBAK_DEBUG_MODUL_MODUL';
    const endpoint = new URL('debug/php/modul/debug-modul.php', document.baseURI).href;
    const archivos = [
        'debug/js/login/acces/debug-acces.js',
        'debug/js/login/autor/debug-autor.js',
        'debug/js/login/recup/debug-recup.js'
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
            fetch(endpoint, {
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

    envi('MODUL_ACTI', { archivo: 'debug-modul.js' });

    (async () => {
        for (const archivo of archivos) {
            try {
                await cargar(archivo);
                envi('MODUL_CARG', { archivo });
            } catch (error) {
                envi('MODUL_CARG_ERR', {
                    archivo,
                    mensaje: error.message
                });
            }
        }
    })();
})();
