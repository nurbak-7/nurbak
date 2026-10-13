/* ============================================================
   Ruta: debug/js/dash/logi/debug-acces.js
   Función: Escuchar exclusivamente evidencias del área correspondiente.

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

    const key = '__NURBAK_DEBUG_DASH_LOGI_ACCES';

    const ruta = new URL(
        'debug/php/dash/logi/debug-acces.php',
        document.baseURI
    ).href;

    const selector = null;

    if (window[key]) {
        return;
    }

    window[key] = true;


    /* ========================================================
       NIVEL 2 — ENVÍO AL DEBUG PHP
    ======================================================== */

    const envi = (paso, contexto = {}) => {
        try {
            const dat = new FormData();

            dat.append('paso', paso);

            Object.entries(contexto).forEach(([nom, val]) => {
                dat.append(
                    `contexto[${nom}]`,
                    String(val ?? '')
                );
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

    envi('ACCES_ACTI', {
        archivo: 'debug-acces.js'
    });


    /* ========================================================
       NIVEL 4 — ERRORES JAVASCRIPT
    ======================================================== */

    window.addEventListener('error', evento => {
        envi('ACCES_JSER', {
            mensaje: evento.message || 'Error JavaScript.',
            archivo: evento.filename || '',
            linea: evento.lineno || 0,
            columna: evento.colno || 0
        });
    }, true);


    /* ========================================================
       NIVEL 5 — PROMESAS RECHAZADAS
    ======================================================== */

    window.addEventListener('unhandledrejection', evento => {
        const razon = evento.reason instanceof Error
            ? evento.reason
            : new Error(
                String(
                    evento.reason ?? 'Promise rechazada.'
                )
            );

        envi('ACCES_PROM', {
            mensaje: razon.message,
            archivo: razon.stack || ''
        });
    }, true);


    /* ========================================================
       NIVEL 6 — DATOS DEL ELEMENTO
    ======================================================== */

    const pertenece = elemento => {
        if (!selector) {
            return true;
        }

        if (!(elemento instanceof Element)) {
            return false;
        }

        return Boolean(
            elemento.closest(selector)
        );
    };


    const datosElemento = elemento => {
        if (!(elemento instanceof Element)) {
            return {};
        }

        let valor = '';

        if ('value' in elemento) {
            const tipo = String(
                elemento.type || ''
            ).toLowerCase();

            if (tipo !== 'password') {
                valor = String(
                    elemento.value ?? ''
                );
            }
        }

        return {
            tag: elemento.tagName || '',
            id: elemento.id || '',
            nombr: elemento.getAttribute('name') || '',
            tipo: elemento.getAttribute('type') || '',
            valor: valor.slice(0, 200),
            texto: String(
                elemento.textContent || ''
            ).trim().slice(0, 200)
        };
    };


    /* ========================================================
       NIVEL 7 — INTERACCIÓN
    ======================================================== */

    document.addEventListener('click', evento => {
        if (!pertenece(evento.target)) {
            return;
        }

        envi(
            'ACCES_CLIC',
            datosElemento(evento.target)
        );
    }, true);


    document.addEventListener('input', evento => {
        if (!pertenece(evento.target)) {
            return;
        }

        envi(
            'ACCES_INPU',
            datosElemento(evento.target)
        );
    }, true);


    document.addEventListener('change', evento => {
        if (!pertenece(evento.target)) {
            return;
        }

        envi(
            'ACCES_CHAN',
            datosElemento(evento.target)
        );
    }, true);


    document.addEventListener('submit', evento => {
        if (!pertenece(evento.target)) {
            return;
        }

        envi(
            'ACCES_SUBM',
            datosElemento(evento.target)
        );
    }, true);


    /* ========================================================
       NIVEL 8 — ACTIVIDAD DE SESIÓN

       Solo observa las actividades que utiliza acces.js.
       No renueva la sesión.
       No cancela eventos.
       No ejecuta lógica funcional.
    ======================================================== */

    let ultimaActividad = 0;

    const observarActividad = evento => {
        const ahora = Date.now();

        if (ahora - ultimaActividad < 1000) {
            return;
        }

        ultimaActividad = ahora;

        envi('ACCES_ACTV', {
            evento: evento.type,
            momento: new Date().toISOString(),
            tag: evento.target?.tagName || '',
            id: evento.target?.id || ''
        });
    };


    document.addEventListener(
        'mousemove',
        observarActividad,
        true
    );

    document.addEventListener(
        'keydown',
        observarActividad,
        true
    );

    document.addEventListener(
        'touchstart',
        observarActividad,
        true
    );

    document.addEventListener(
        'scroll',
        observarActividad,
        true
    );


    /* ========================================================
       NIVEL 9 — OBSERVAR RECURSOS FETCH

       DEBUG solamente observa los recursos que el navegador
       ya registró.

       No reemplaza fetch.
       No ejecuta fetch funcional.
       No modifica la petición.
       No modifica la respuesta.

       Se excluye el propio receptor DEBUG para evitar
       registrar su propia petición.
    ======================================================== */

    if (
        'PerformanceObserver' in window &&
        PerformanceObserver.supportedEntryTypes?.includes('resource')
    ) {
        const observarRecursos = lista => {
            lista.getEntries().forEach(entrada => {
                const nombre = String(
                    entrada.name || ''
                );

                if (nombre === ruta) {
                    return;
                }

                if (
                    entrada.initiatorType !== 'fetch'
                ) {
                    return;
                }

                envi('ACCES_RECU', {
                    url: nombre,
                    tipo: entrada.initiatorType,
                    inicio: entrada.startTime,
                    duracion: entrada.duration,
                    transferencia: entrada.transferSize
                });
            });
        };

        const observador = new PerformanceObserver(
            observarRecursos
        );

        observador.observe({
            type: 'resource',
            buffered: true
        });
    }

})();