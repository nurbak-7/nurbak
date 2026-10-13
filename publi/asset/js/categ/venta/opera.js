/* ============================================================
   Ruta: publi/asset/js/categ/venta/opera.js
   Módulo: Ventas / Operaciones

   RESPONSABILIDAD:
   Coordinar la carga de Operaciones y sus submódulos.

   CICLO DE VIDA:
   1. Crear el contexto de la ventana.
   2. Inicializar los submódulos del contexto.
   3. Cerrar recursos al cerrar la modal.
   4. Cerrar recursos al salir de la página/sesión.
   5. Evitar que queden latidos, SSE u observadores activos.
============================================================ */

(() => {

    const archivos = [
        'docum.js',
        'clien.js',
        'opera.js',
        'produ.js',
        'tabla.js',
        'total.js',
        'pagos.js',
        'accio.js'
    ];

    window.NURBAK_OPERA_MODULOS =
        window.NURBAK_OPERA_MODULOS || {};

    /* INTEGRACIÓN | FASE 3: compartir cargas pendientes entre ventanas. */
    const pendientes = new Map();

    const cargar = archivo => {
        if (pendientes.has(archivo)) {
            return pendientes.get(archivo);
        }

        const tarea = new Promise((ok, error) => {

        const selector = `[data-opera-js="${archivo}"]`;
        const existe = document.querySelector(selector);
        const listo = () => {
            const nombr = archivo.replace('.js', '');

            if (typeof window.NURBAK_OPERA_MODULOS?.[nombr] !== 'function') {
                throw new Error(`Módulo Operaciones no registrado: ${archivo}`);
            }
        };

        if (existe) {
            if (existe.dataset.operaEstado === 'cargado') {
                try {
                    listo();
                    ok();
                    return;
                } catch (_) {
                    existe.remove();
                }
            }

            if (existe.dataset.operaEstado === 'cargando') {
                existe.addEventListener('load', () => {
                    try {
                        listo();
                        ok();
                    } catch (e) {
                        error(e);
                    }
                }, { once: true });
                existe.addEventListener('error', () => {
                    existe.remove();
                    error(new Error(`No se pudo cargar ${archivo}`));
                }, { once: true });
                return;
            }

            existe.remove();
        }

        const script = document.createElement('script');

        script.src = archivo === 'clien.js'
            ? 'publi/asset/js/categ/venta/clien/clien.js'
            : `publi/asset/js/categ/venta/opera/${archivo}`;

        script.dataset.operaJs = archivo;
        script.dataset.operaEstado = 'cargando';

        script.onload = () => {
            try {
                script.dataset.operaEstado = 'cargado';
                listo();
                ok();
            } catch (e) {
                script.remove();
                error(e);
            }
        };

        script.onerror = () => {
            script.remove();
            error(new Error(`No se pudo cargar ${archivo}`));
        };

        document.body.appendChild(script);
        });

        pendientes.set(archivo, tarea);
        return tarea.finally(() => pendientes.delete(archivo));
    };

    const asegurarToast = () => new Promise((ok, error) => {
        if (window.NURBAK_TOAST?.cargar) {
            ok();
            return;
        }

        const ruta = 'publi/asset/js/core/toast.js';
        let existente =
            document.querySelector(`[data-layo-toast="${ruta}"]`) ||
            document.querySelector(`[data-opera-core-toast="${ruta}"]`);

        if (existente) {
            if (existente.dataset.toastEstado === 'cargando') {
                existente.addEventListener('load', () => ok(), { once: true });
                existente.addEventListener('error', () => {
                    existente.remove();
                    error(new Error('No se pudo cargar el coordinador Toast.'));
                }, { once: true });
                return;
            }

            existente.remove();
            existente = null;
        }

        const script = document.createElement('script');
        script.src = ruta;
        script.dataset.operaCoreToast = ruta;
        script.dataset.toastEstado = 'cargando';
        script.addEventListener('load', () => {
            script.dataset.toastEstado = 'cargado';
            if (window.NURBAK_TOAST?.cargar) {
                ok();
                return;
            }
            error(new Error('El coordinador Toast cargó pero no quedó registrado.'));
        }, { once: true });
        script.addEventListener('error', () => {
            script.remove();
            error(new Error('No se pudo cargar el coordinador Toast.'));
        }, { once: true });
        document.body.appendChild(script);
    });

    const iniciar = async ventana => {

        const root = ventana?.querySelector('[data-opera]');
        if (!root) return;

        if (
            ventana.dataset.operaIniciado === 'si' ||
            ventana.dataset.operaIniciando === 'si'
        ) {
            return;
        }

        ventana.dataset.operaIniciando = 'si';

        const limpiezas = [];
        let cicloCerrado = false;
        let cierreAutorizado = false;

        const registrarLimpieza = funcion => {
            if (typeof funcion !== 'function') return () => {};
            limpiezas.push(funcion);

            return () => {
                const indice = limpiezas.indexOf(funcion);
                if (indice >= 0) limpiezas.splice(indice, 1);
            };
        };

        const ctx = {
            root,
            ventana,

            q: selector => root.querySelector(selector),

            state: {
                opciones: null,
                detalles: [],
                vendedor: null,
                clien: null,
                clienId: '',
                almacenId: '',
                placa: '',
                formaPago: '',
                medioPago: '',
                tipo: 'BOLETA',
                serie: '',
                numer: 0,
                operacionId: 0,
                estados: []
            },

            api: {},

            R: 'modul/categ/venta/opera/opera-contr.php',

            esc: valor => String(valor ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;'),

            registrarLimpieza,

            cerrarCiclo: motivo => {
                if (cicloCerrado) return;
                cicloCerrado = true;

                for (let i = limpiezas.length - 1; i >= 0; i--) {
                    try {
                        limpiezas[i](motivo);
                    } catch (error) {
                        console.error(
                            'Operaciones / limpieza:',
                            error
                        );
                    }
                }

                limpiezas.length = 0;
                ventana.dataset.operaCerrado = 'si';
            }
        };

        let cerrarModal = null;
        let salirPagina = null;

        const registrarCiclo = () => {

            cerrarModal = detalle => {

                if (detalle.modulo !== 'operaciones') return;
                if (detalle.modal && detalle.modal !== ventana) return;

                /* El cierre solo continúa después de Guardar o Cancelar. */
                if (!cierreAutorizado) {
                    return;
                }

                ctx.cerrarCiclo('modal');
            };

            salirPagina = () => {
                ctx.cerrarCiclo('pagehide');
            };

            const quitarCierreModal = window.NURBAK_SINC?.on('modal:cerrando', cerrarModal) || (() => {});

            window.addEventListener(
                'pagehide',
                salirPagina,
                true
            );

            registrarLimpieza(() => {
                quitarCierreModal();

                window.removeEventListener(
                    'pagehide',
                    salirPagina,
                    true
                );
            });
        };

        registrarCiclo();

        /* ====================================================
           NIVEL 2.1 — CIERRE DIRECTO DE LA MODAL

           La X no cierra automáticamente cuando existen productos.
           La decisión la toma ACCIO mediante Guardar/Cancelar.
        ==================================================== */
        const controlCierre = ventana.querySelector(
            '[data-modal-accion="cerrar"]'
        );

        const cerrarDirecto = evento => {
            if (cicloCerrado) return;

            if (!ctx.state.detalles.length) {
                cierreAutorizado = true;
                return;
            }

            evento.preventDefault();
            evento.stopImmediatePropagation();

            ctx.api.conf?.('canc');
        };

        controlCierre?.addEventListener(
            'click',
            cerrarDirecto,
            true
        );

        ctx.api.cerrarVentana = () => {
            if (cicloCerrado) return;

            const cerrar =
                window.NURBAK_MODAL_ACCIO?.cerrarModal;

            if (typeof cerrar !== 'function') {
                console.error(
                    'Operaciones: MODAL no expone su cierre propietario.'
                );
                return;
            }

            cierreAutorizado = true;
            cerrar(ventana);
        };

        registrarLimpieza(() => {
            controlCierre?.removeEventListener(
                'click',
                cerrarDirecto,
                true
            );
        });

        try {
            const respuesta = await fetch(
                'modul/categ/venta/opera/opera-contr.php?accion=opciones',
                { cache: 'no-store' }
            );

            const opciones = await respuesta.json();

            if (!respuesta.ok || !opciones.ok) {
                throw new Error(
                    opciones.mensaje ||
                    'No se pudieron cargar las opciones.'
                );
            }

            if (cicloCerrado) return;

            ctx.state.opciones = opciones;

            await asegurarToast();

            /* INTEGRACIÓN | FASE 3: los recursos se cargan en paralelo.
               La inicialización permanece secuencial porque los submódulos
               comparten ctx.api y existen dependencias funcionales entre ellos. */
            await Promise.all(archivos.map(cargar));

            for (const archivo of archivos) {
                if (cicloCerrado) break;

                /*
                   ACCIO se inicializa al final. TOAST debe estar disponible
                   antes de inicializar ese submódulo.
                */
                if (archivo === 'accio.js') {
                    try {
                        /* ====================================================
                           NIVEL 3.1 — CONECTAR TOAST ANTES DE ACCIO

                           CORE/TOAST es el único coordinador. ACCIO no debe
                           arrancar con una referencia nula ni depender de
                           una carrera entre el script de LAYO y el módulo.
                        ==================================================== */
                        if (window.NURBAK_TOAST?.cargar) {
                            ctx.api.toas = await window.NURBAK_TOAST.cargar(
                                'operaciones',
                                ventana
                            );
                        }

                        /* El registro puede haber quedado disponible en
                           paralelo mientras finalizaba la carga. */
                        ctx.api.toas =
                            ctx.api.toas ||
                            window.NURBAK_TOAST?.obtener?.('vent/oper') ||
                            null;

                        if (!ctx.api.toas) {
                            throw new Error('Toast de Operaciones no quedó conectado.');
                        }
} catch (error) {
                        console.error(
                            'Operaciones / Toast:',
                            error
                        );
                    }
                }

                if (cicloCerrado) break;

                const nombr = archivo.replace('.js', '');
                const modulo =
                    window.NURBAK_OPERA_MODULOS[nombr];

                if (typeof modulo !== 'function') {
                    throw new Error(`No se registró el submódulo ${archivo}.`);
                }

                const api = modulo(ctx);

                if (nombr === 'accio') {
}
            }

            if (!cicloCerrado) {
                ventana.dataset.operaIniciado = 'si';
            }

        } catch (error) {
            ctx.cerrarCiclo('error');
            throw error;

        } finally {
            delete ventana.dataset.operaIniciando;
        }
    };

    document.addEventListener(
        'modu:cargado',
        evento => {
            if (evento.detail?.modulo !== 'operaciones') return;

            iniciar(evento.detail.modal).catch(error => {
                console.error('Operaciones:', error);
            });
        }
    );

})();
