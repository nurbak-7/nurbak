/* ============================================================
   Ruta: debug/js/categ/venta/opera/debug-opera.js
   Función: Escuchar exclusivamente el nivel general de
   OPERACIONES.

   JERARQUÍA:
   VENTAS
      └── OPERACIONES
           ├── nivel general
           └── subáreas
                └── PRODUCTOS / MINI

   RESPONSABILIDAD:
   - Registrar la activación de OPERACIONES.
   - Registrar la presencia de la ventana principal.
   - Registrar únicamente la interacción del área general.
   - Marcar la apertura/cierre del MINI como subproceso.
   - Dejar el detalle del MINI a debug-produ.js.

   INDEPENDENCIA:
   - No incluye archivos funcionales.
   - No modifica APIs del navegador.
   - No consulta la base funcional.
   - No duplica el detalle del MINI.
============================================================ */
(() => {
    'use strict';

    const key = '__NURBAK_DEBUG_CATEG_VENTA_OPERA_OPERA';
    const ruta = new URL(
        'debug/php/categ/venta/opera/debug-opera.php',
        document.baseURI
    ).href;

    const selector = '[data-opera]';
    const miniSelector = '[data-opera-prod-mini]';

    if (window[key]) return;
    window[key] = true;

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

    envi('OPERA_ACTI', {
        archivo: 'debug-opera.js'
    });

    const pertenece = elemento => {
        if (!(elemento instanceof Element)) {
            return false;
        }

        const opera = elemento.closest(selector);

        if (!opera) return false;

        /* El MINI tiene su propio DEBUG de PRODUCTOS. */
        if (elemento.closest(miniSelector)) {
            return false;
        }

        return true;
    };

    const datos = elemento => {
        if (!(elemento instanceof Element)) return {};

        let valor = '';

        if ('value' in elemento) {
            const tipo = String(
                elemento.type || ''
            ).toLowerCase();

            if (tipo !== 'password') {
                valor = String(
                    elemento.value ?? ''
                ).slice(0, 200);
            }
        }

        return {
            tag: elemento.tagName || '',
            id: elemento.id || '',
            nombr: elemento.getAttribute('name') || '',
            tipo: elemento.getAttribute('type') || '',
            valor,
            texto: String(
                elemento.textContent || ''
            ).trim().slice(0, 200)
        };
    };

    document.addEventListener(
        'click',
        evento => {
            if (!pertenece(evento.target)) return;
            envi('OPERA_CLIC', datos(evento.target));
        },
        true
    );

    document.addEventListener(
        'input',
        evento => {
            if (!pertenece(evento.target)) return;
            envi('OPERA_INPU', datos(evento.target));
        },
        true
    );

    document.addEventListener(
        'change',
        evento => {
            if (!pertenece(evento.target)) return;
            envi('OPERA_CHAN', datos(evento.target));
        },
        true
    );

    document.addEventListener(
        'submit',
        evento => {
            if (!pertenece(evento.target)) return;
            envi('OPERA_SUBM', datos(evento.target));
        },
        true
    );

    /* ========================================================
       ESTADO DE LA VENTANA PRINCIPAL

       Se registra una sola vez por apertura/cierre.
       Ya no se genera una línea por cada mutación interna.
    ======================================================== */

    let ventanaActiva = false;
    let miniActiva = false;

    const observar = () => {
        const ventana = Boolean(
            document.querySelector(selector)
        );

        if (ventana && !ventanaActiva) {
            ventanaActiva = true;

            envi('OPERA_VENTANA_ACTIVA', {
                modulo: 'operaciones'
            });
        }

        if (!ventana && ventanaActiva) {
            ventanaActiva = false;

            envi('OPERA_VENTANA_CIERRE', {
                modulo: 'operaciones'
            });
        }

        const mini = Boolean(
            document.querySelector(miniSelector)
        );

        if (mini && !miniActiva) {
            miniActiva = true;

            envi('OPERA_MINI_ACTI', {
                area: 'productos'
            });
        }

        if (!mini && miniActiva) {
            miniActiva = false;

            envi('OPERA_MINI_CIER', {
                area: 'productos'
            });
        }
    };

    const observer = new MutationObserver(observar);

    const iniciar = () => {
        if (!document.body) return;

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        observar();
    };

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            iniciar,
            { once: true }
        );
    } else {
        iniciar();
    }

    window.addEventListener(
        'error',
        evento => {
            envi('OPERA_JSER', {
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
            const razon =
                evento.reason instanceof Error
                    ? evento.reason
                    : new Error(
                        String(
                            evento.reason ??
                            'Promise rechazada.'
                        )
                    );

            envi('OPERA_PROM', {
                mensaje: razon.message,
                archivo: razon.stack || ''
            });
        },
        true
    );
})();
