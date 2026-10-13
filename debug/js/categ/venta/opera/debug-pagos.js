/* ============================================================
   Ruta: debug/js/categ/venta/opera/debug-pagos.js
   Función: Escuchar exclusivamente evidencias de PAGOS.

   NIVELES:
   1. Inicio del DEBUG
   2. Envío al DEBUG PHP
   3. Errores JavaScript
   4. Promesas rechazadas
   5. Escuchar FORMA DE PAGO
   6. Escuchar MEDIO DE PAGO
   7. Escuchar ENTER DEL MEDIO
   8. Estado de los campos

   RESPONSABILIDAD ÚNICA:
   - Escuchar.
   - Registrar evidencia.
   - No ejecutar lógica funcional.

   INDEPENDENCIA:
   - No modifica pagos.js.
   - No modifica accio.js.
   - No modifica opera.js.
   - No reemplaza fetch ni otras API.
   - No consulta la base funcional.
============================================================ */

(() => {
    'use strict';


    /* ========================================================
       NIVEL 1 — INICIO DEL DEBUG
    ======================================================== */

    const key = '__NURBAK_DEBUG_CATEG_VENTA_OPERA_PAGOS';

    const ruta =
        new URL(
            'debug/php/categ/venta/opera/debug-pagos.php',
            document.baseURI
        ).href;


    if (window[key]) return;

    window[key] = true;


    /* ========================================================
       NIVEL 2 — ENVÍO AL DEBUG PHP
    ======================================================== */

    const envi = (paso, cont = {}) => {

        try {

            const dat = new FormData();

            dat.append(
                'paso',
                paso
            );

            Object.entries(cont).forEach(
                ([nom, val]) => {

                    dat.append(
                        `contexto[${nom}]`,
                        String(val ?? '')
                    );

                }
            );


            fetch(
                ruta,
                {
                    method: 'POST',
                    body: dat,
                    keepalive: true,
                    cache: 'no-store',
                    credentials: 'same-origin'
                }
            ).catch(() => {});

        } catch (_) {
        }

    };


    /* ========================================================
       NIVEL 3 — INICIO REGISTRADO
    ======================================================== */

    envi('PAGOS_ACTI', {
        archivo: 'pagos.js'
    });


    /* ========================================================
       NIVEL 4 — ERRORES JAVASCRIPT
    ======================================================== */

    window.addEventListener(
        'error',
        evento => {

            envi(
                'PAGOS_JSER',
                {
                    mensaje:
                        evento.message ||
                        'Error JavaScript.',

                    archivo:
                        evento.filename ||
                        '',

                    linea:
                        evento.lineno ||
                        0,

                    columna:
                        evento.colno ||
                        0
                }
            );

        },
        true
    );


    /* ========================================================
       NIVEL 5 — PROMESAS RECHAZADAS
    ======================================================== */

    window.addEventListener(
        'unhandledrejection',
        evento => {

            const razo =
                evento.reason instanceof Error
                    ? evento.reason
                    : new Error(
                        String(
                            evento.reason ??
                            'Promise rechazada.'
                        )
                    );


            envi(
                'PAGOS_PROM',
                {
                    mensaje:
                        razo.message,

                    archivo:
                        razo.stack ||
                        ''
                }
            );

        },
        true
    );


    /* ========================================================
       NIVEL 6 — ESCUCHAR FORMA DE PAGO
    ======================================================== */

    document.addEventListener(
        'change',
        evento => {

            const elem = evento.target;

            if (
                !(elem instanceof HTMLSelectElement) ||
                !elem.matches(
                    '[data-opera-forma-pago]'
                )
            ) {
                return;
            }


            envi(
                'PAGOS_FORM',
                {
                    valor:
                        elem.value
                }
            );

        },
        true
    );


    /* ========================================================
       NIVEL 7 — ESCUCHAR MEDIO DE PAGO
    ======================================================== */

    document.addEventListener(
        'input',
        evento => {

            const elem = evento.target;

            if (
                !(elem instanceof HTMLInputElement) ||
                !elem.matches(
                    '[data-opera-medio-pago]'
                )
            ) {
                return;
            }


            envi(
                'PAGOS_MEDI',
                {
                    valor:
                        elem.value
                }
            );

        },
        true
    );


    /* ========================================================
       NIVEL 8 — ESCUCHAR ENTER DEL MEDIO
    ======================================================== */

    document.addEventListener(
        'keydown',
        evento => {

            const elem = evento.target;

            if (
                !(elem instanceof HTMLInputElement) ||
                !elem.matches(
                    '[data-opera-medio-pago]'
                )
            ) {
                return;
            }


            if (evento.key !== 'Enter') {
                return;
            }


            envi(
                'PAGOS_ENTR',
                {
                    valor:
                        elem.value
                }
            );

        },
        true
    );


    /* ========================================================
       NIVEL 9 — ESTADO DE LOS CAMPOS
    ======================================================== */

    const esta = () => {

        const forma =
            document.querySelector(
                '[data-opera-forma-pago]'
            );

        const medio =
            document.querySelector(
                '[data-opera-medio-pago]'
            );


        envi(
            'PAGOS_ESTA',
            {
                forma:
                    forma?.value || '',

                medio:
                    medio?.value || ''
            }
        );

    };


    /* ========================================================
       NIVEL 10 — OBSERVAR CAMBIOS DE SALIDA
    ======================================================== */

    document.addEventListener(
        'change',
        evento => {

            const elem = evento.target;

            if (
                elem instanceof HTMLSelectElement &&
                elem.matches(
                    '[data-opera-forma-pago]'
                )
            ) {
                esta();
            }

        },
        true
    );


    document.addEventListener(
        'blur',
        evento => {

            const elem = evento.target;

            if (
                elem instanceof HTMLInputElement &&
                elem.matches(
                    '[data-opera-medio-pago]'
                )
            ) {
                esta();
            }

        },
        true
    );

})();