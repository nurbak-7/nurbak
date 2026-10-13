/* ============================================================
   Ruta: debug/js/dash/mini/debug-mini.js
   Función: Escuchar exclusivamente evidencias del área correspondiente.

   NIVELES:
   1. Inicio DEBUG
   2. Envío al DEBUG PHP
   3. Errores JavaScript
   4. Promesas rechazadas
   5. Escuchar interacción del área

   RESPONSABILIDAD ÚNICA:
   - Escuchar.
   - Registrar evidencia.
   - No ejecutar lógica funcional.

   INDEPENDENCIA:
   - No modifica archivos funcionales.
   - No reemplaza fetch ni otras API.
   - No consulta APIs funcionales.
   - No emite eventos DEBUG para el módulo.
============================================================ */
(() => {
    'use strict';

    /* ========================================================
       NIVEL 1 — INICIO DEBUG
    ======================================================== */

    const key = '__NURBAK_DEBUG_DASH_MINI_MINI';
    const ruta = new URL('debug/php/dash/mini/debug-mini.php', document.baseURI).href;
    const selector = "[data-mini-ventana]";

    if (window[key]) return;
    window[key] = true;


    /* ========================================================
       NIVEL 2 — ENVÍO AL DEBUG PHP
    ======================================================== */

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
        } catch (_) {
        }
    };


    /* ========================================================
       NIVEL 3 — ACTIVACIÓN
    ======================================================== */

    envi('MINI_ACTI', {
        archivo: 'mini.js'
    });


    /* ========================================================
       NIVEL 4 — ERRORES
    ======================================================== */

    window.addEventListener('error', evento => {
        envi('MINI_JSER', {
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

        envi('MINI_PROM', {
            mensaje: razon.message,
            archivo: razon.stack || ''
        });
    }, true);


    /* ========================================================
       NIVEL 5 — ESCUCHAR INTERACCIÓN

       DEBUG solo observa. No modifica valores ni detiene eventos.
    ======================================================== */

    const pertenece = elemento => {
        if (!selector) return true;
        if (!(elemento instanceof Element)) return false;
        return Boolean(elemento.closest(selector));
    };

    const datosElemento = elemento => {
        if (!(elemento instanceof Element)) return {};

        let valor = '';

        if ('value' in elemento) {
            const tipo = String(elemento.type || '').toLowerCase();
            if (tipo !== 'password') {
                valor = String(elemento.value ?? '');
            }
        }

        return {
            tag: elemento.tagName || '',
            id: elemento.id || '',
            nombr: elemento.getAttribute('name') || '',
            tipo: elemento.getAttribute('type') || '',
            valor: valor.slice(0, 200),
            texto: String(elemento.textContent || '').trim().slice(0, 200)
        };
    };

    document.addEventListener('click', evento => {
        if (!pertenece(evento.target)) return;
        envi('MINI_CLIC', datosElemento(evento.target));
    }, true);

    document.addEventListener('input', evento => {
        if (!pertenece(evento.target)) return;
        envi('MINI_INPU', datosElemento(evento.target));
    }, true);

    document.addEventListener('change', evento => {
        if (!pertenece(evento.target)) return;
        envi('MINI_CHAN', datosElemento(evento.target));
    }, true);

    document.addEventListener('submit', evento => {
        if (!pertenece(evento.target)) return;
        envi('MINI_SUBM', datosElemento(evento.target));
    }, true);

})();
