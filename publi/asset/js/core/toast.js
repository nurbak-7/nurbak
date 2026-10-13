/* ============================================================
   Ruta: publi/asset/js/core/toast.js
   Función: Coordinador independiente del sistema Toast.

   RESPONSABILIDAD:
   - Cargar el Toast propio de cada módulo.
   - Esperar a que el módulo esté realmente registrado antes
     de entregarlo al módulo que lo solicita.
   - Evitar dobles cargas y carreras entre LAYO y los módulos.
============================================================ */
(() => {

    /* ========================================================
       NIVEL 1 — SOLICITAR CSS AL COORDINADOR ÚNICO
    ======================================================== */
    window.NURBAK_ESTIL.cargar('core/toast').catch(error => {
        console.error('TOAST: no se pudo cargar su CSS.', error);
    });

    /* ========================================================
       NIVEL 2 — RUTAS DE LOS MÓDULOS
    ======================================================== */
    const rutas = Object.freeze({
        operaciones: {
            modulo: 'vent/oper',
            js: 'publi/asset/js/categ/venta/opera.js',
            toast: 'publi/asset/js/categ/venta/opera/toast.js'
        },
        cotiz: {
            modulo: 'vent/cotiz',
            js: 'publi/asset/js/categ/venta/cotiz.js',
            toast: 'publi/asset/js/categ/venta/cotiz/toast.js'
        },
        nvent: {
            modulo: 'vent/nvent',
            js: 'publi/asset/js/categ/venta/nvent.js',
            toast: 'publi/asset/js/categ/venta/nvent/toast.js'
        }
    });

    const registros = new Map();
    const cargas = new Map();

    /* ========================================================
       NIVEL 3 — REGISTRO Y CONSULTA
    ======================================================== */
    const registrar = (modulo, api) => {
        if (modulo && api) {
            registros.set(modulo, api);
        }
    };

    const obtener = modulo => registros.get(modulo) || null;

    /* ========================================================
       NIVEL 4 — OBTENER FUNCIÓN REAL DEL MÓDULO
    ======================================================== */
    const obtenerInicio = ruta =>
        window.NURBAK_TOAST_MODULOS?.[ruta.modulo];

    /* ========================================================
       NIVEL 5 — ESPERAR UNA CARGA YA EXISTENTE

       No basta con detectar el <script>. Puede existir mientras
       todavía está descargándose. Ese era el punto de carrera:
       cargar() podía devolver null antes de que toast.js se
       registrara, dejando ACCIO sin su API.
    ======================================================== */
    const esperarScript = (script, ruta) => new Promise(resolve => {

        if (typeof obtenerInicio(ruta) === 'function') {
            resolve();
            return;
        }

        const resolver = () => resolve();

        script.addEventListener('load', resolver, { once: true });
        script.addEventListener('error', resolver, { once: true });
    });

    /* ========================================================
       NIVEL 6 — CARGAR TOAST DEL MÓDULO
    ======================================================== */
    const cargar = (nombr, ventana) => {
        const ruta = rutas[nombr];

        if (!ruta) {
            return Promise.resolve(null);
        }

        const iniciar = () => {
            const fn = obtenerInicio(ruta);

            return typeof fn === 'function'
                ? fn({ ventana, ruta })
                : null;
        };

        /* Ya está registrado: no esperar ni recargar. */
        if (typeof obtenerInicio(ruta) === 'function') {
            return Promise.resolve(iniciar());
        }

        /* Ya existe una carga en curso: compartirla. */
        if (cargas.has(ruta.toast)) {
            return cargas.get(ruta.toast).then(iniciar);
        }

        const promesa = new Promise(resolve => {
            const selector = `[data-toast-modulo="${ruta.toast}"]`;
            let existente = document.querySelector(selector);

            if (existente) {
                if (existente.dataset.toastEstado === 'cargado') {
                    if (typeof obtenerInicio(ruta) === 'function') {
                        resolve();
                        return;
                    }

                    existente.remove();
                    existente = null;
                } else if (existente.dataset.toastEstado === 'cargando') {
                    esperarScript(existente, ruta).then(resolve);
                    return;
                } else {
                    existente.remove();
                    existente = null;
                }
            }

            const script = document.createElement('script');
            script.src = ruta.toast;
            script.dataset.toastModulo = ruta.toast;
            script.dataset.toastEstado = 'cargando';

            script.addEventListener(
                'load',
                () => {
                    script.dataset.toastEstado = 'cargado';
                    resolve();
                },
                { once: true }
            );

            script.addEventListener(
                'error',
                () => {
                    script.dataset.toastEstado = 'error';
                    script.remove();
                    resolve();
                },
                { once: true }
            );

            document.body.appendChild(script);
        });

        cargas.set(ruta.toast, promesa);

        return promesa.then(() => {
            const api = iniciar();

            if (!api) {
                throw new Error(`No se registró el Toast del módulo ${ruta.modulo}.`);
            }

            return api;
        }).finally(() => {
            cargas.delete(ruta.toast);
        });
    };

    /* ========================================================
       NIVEL 7 — API GLOBAL
    ======================================================== */
    window.NURBAK_TOAST = {
        rutas,
        registrar,
        obtener,
        cargar,
        registros
    };

    /* ========================================================
       NIVEL 8 — REVISAR MODALES YA EXISTENTES
    ======================================================== */
    const revisar = () => {
        document.querySelectorAll('[data-modal]').forEach(ventana => {
            const modulo = ventana.dataset.modulo;

            if (rutas[modulo]) {
                void cargar(modulo, ventana);
            }
        });
    };

    document.addEventListener(
        'modu:cargado',
        evento => {
            const modulo = evento.detail?.modulo;
            const modal = evento.detail?.modal;

            if (rutas[modulo]) {
                void cargar(modulo, modal);
            }
        }
    );

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            revisar,
            { once: true }
        );
    } else {
        revisar();
    }

})();
