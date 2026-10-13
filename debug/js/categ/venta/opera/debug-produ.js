/* ============================================================
   Ruta: debug/js/categ/venta/opera/debug-produ.js
   Función: Observar exclusivamente el proceso PRODUCTOS de
   OPERACIONES, incluido el MINI "Agregar producto".

   JERARQUÍA:
   VENTAS
      └── OPERACIONES
           └── PRODUCTOS
                └── MINI

   RESPONSABILIDAD:
   - Registrar la activación del área PRODUCTOS.
   - Registrar apertura/cierre del MINI.
   - Registrar interacción exclusiva del MINI.
   - Registrar las peticiones del MINI y sus respuestas.
   - Registrar el SSE de stock usado por el MINI.
   - Registrar errores JS mientras el MINI está activo.

   INDEPENDENCIA:
   - No modifica archivos funcionales.
   - No consulta la base funcional.
   - No altera la respuesta de fetch().
   - No reemplaza la lógica funcional.
============================================================ */
(() => {
    'use strict';

    const key = '__NURBAK_DEBUG_CATEG_VENTA_OPERA_PRODU';
    const ruta = new URL(
        'debug/php/categ/venta/opera/debug-produ.php',
        document.baseURI
    ).href;

    const selector = '[data-opera-prod-mini]';

    if (window[key]) return;
    window[key] = true;

    /* ========================================================
       ENVÍO AL RECEPTOR PROPIO DEL ÁREA
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
        } catch (_) {}
    };

    /* ========================================================
       UTILIDADES
    ======================================================== */

    const esMini = () =>
        Boolean(document.querySelector(selector));

    const datosElemento = elemento => {
        if (!(elemento instanceof Element)) return {};

        let valor = '';

        if ('value' in elemento) {
            const tipo = String(
                elemento.type || ''
            ).toLowerCase();

            if (tipo !== 'password') {
                valor = String(
                    elemento.value ?? ''
                ).slice(0, 120);
            }
        }

        return {
            tag: elemento.tagName || '',
            id: elemento.id || '',
            nombr: elemento.getAttribute('name') || '',
            tipo: elemento.getAttribute('type') || '',
            valor,
            dato: elemento.getAttribute('data-opera-prod-buscar')
                !== null
                ? 'buscar'
                : ''
        };
    };

    const pertenece = elemento =>
        elemento instanceof Element &&
        Boolean(elemento.closest(selector));

    /* ========================================================
       ACTIVACIÓN DEL ÁREA
    ======================================================== */

    envi('PRODU_ACTI', {
        archivo: 'debug-produ.js',
        area: 'operaciones/productos'
    });

    /* ========================================================
       MINI: APERTURA Y CIERRE

       Se registra una sola vez cada transición.
    ======================================================== */

    let miniActivo = false;

    const observarMini = () => {
        const activo = esMini();

        if (activo && !miniActivo) {
            miniActivo = true;

            envi('PRODU_MINI_ACTI', {
                modulo: 'opera-productos',
                vista: 'produc-mini.php'
            });

            return;
        }

        if (!activo && miniActivo) {
            miniActivo = false;

            envi('PRODU_MINI_CIER', {
                modulo: 'opera-productos'
            });
        }
    };

    const miniObserver = new MutationObserver(
        observarMini
    );

    if (document.body) {
        miniObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    observarMini();

    /* ========================================================
       INTERACCIÓN EXCLUSIVA DEL MINI
    ======================================================== */

    document.addEventListener('click', evento => {
        if (!pertenece(evento.target)) return;

        envi('PRODU_MINI_CLIC', datosElemento(evento.target));
    }, true);

    document.addEventListener('input', evento => {
        if (!pertenece(evento.target)) return;

        envi('PRODU_MINI_INPU', datosElemento(evento.target));
    }, true);

    document.addEventListener('change', evento => {
        if (!pertenece(evento.target)) return;

        envi('PRODU_MINI_CHAN', datosElemento(evento.target));
    }, true);

    document.addEventListener('keydown', evento => {
        if (!pertenece(evento.target)) return;

        if (
            evento.key !== 'Enter' &&
            evento.key !== 'Escape' &&
            evento.key !== 'ArrowUp' &&
            evento.key !== 'ArrowDown'
        ) {
            return;
        }

        envi('PRODU_MINI_TECL', {
            tecla: evento.key
        });
    }, true);

    /* ========================================================
       ERRORES JS DEL MINI

       Solo se registran mientras el MINI está abierto.
    ======================================================== */

    window.addEventListener(
        'error',
        evento => {
            if (!miniActivo) return;

            envi('PRODU_MINI_JSER', {
                mensaje: evento.message ||
                    'Error JavaScript.',
                archivo: evento.filename || '',
                linea: evento.lineno || 0,
                columna: evento.colno || 0
            });
        },
        true
    );

    window.addEventListener(
        'unhandledrejection',
        evento => {
            if (!miniActivo) return;

            const razon =
                evento.reason instanceof Error
                    ? evento.reason
                    : new Error(
                        String(
                            evento.reason ??
                            'Promise rechazada.'
                        )
                    );

            envi('PRODU_MINI_PROM', {
                mensaje: razon.message,
                archivo: razon.stack || ''
            });
        },
        true
    );
})();
