/* ============================================================
   Ruta: publi/asset/js/categ/venta/opera/opera.js
   Módulo: Ventas / Operaciones / Operación

   RESPONSABILIDAD:
   Inicializar vendedor, placa y almacén.
============================================================ */
(() => {
    const iniciar = ctx => {
        const { q, state, ventana: vent } = ctx;

        const vendedor = q('[data-opera-vendedor]');
        const almacen = q('[data-opera-almacen]');
        const placa = q('[data-opera-placa]');
        const obser = q('[data-opera-obser]');

        const LATMS = 15000;
        const ACTMS = 2000;
        let tmLat = null;
        let tmAct = null;
        let latEn = false;
        let sali = false;

        const cargarAlmacenes = (items = []) => {
            if (!almacen) return;

            const actual =
                String(state.almacenId || almacen.value || '');

            almacen.innerHTML =
                items.length
                    ? items
                        .map(x => `
                            <option value="${ctx.esc(x.id)}">
                                ${ctx.esc(x.nombr)}
                            </option>
                        `)
                        .join('')
                    : '<option value="" disabled>Sin almacenes disponibles</option>';

            if (
                actual &&
                [...almacen.options].some(
                    option => option.value === actual
                )
            ) {
                almacen.value = actual;
                state.almacenId = actual;
            } else {
                state.almacenId = almacen.value || '';
            }
        };

        // SINCRONIZACIÓN | FASE 2
        const actualizarAlmacenes = async () => {
            const actual = String(state.almacenId || almacen?.value || '');
            try {
                const respuesta = await fetch(
                    `${ctx.R}?accion=opciones`,
                    { cache: 'no-store' }
                );
                const datos = await respuesta.json();
                if (!respuesta.ok || !datos.ok) return;
                state.opciones = datos;
                cargarAlmacenes(datos.almacenes || []);
                if (actual && [...(almacen?.options || [])].some(op => op.value === actual)) {
                    almacen.value = actual;
                    state.almacenId = actual;
                } else if (actual) {
                    state.almacenId = '';
                }
            } catch (_) {
                /* La ventana conserva el último estado válido. */
            }
        };

        window.NURBAK_ADMI_SINC?.onCambio(
            () => void actualizarAlmacenes(),
            {recu:'tiend'},
            vent
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () => void actualizarAlmacenes(),
            {recu:'almac'},
            vent
        );

        /* ====================================================
           NIVEL 2 — VENDEDOR POR CÓDIGO
           Solo consulta cuando el código está completo.

           USUARIO         → 001 / 002 / 003...
           ADMINISTRADOR   → A001 / A002 / A003...

           nunca consulta PHP por cada carácter escrito.
        ==================================================== */
        let temporizadorVendedor = null;
        let codigoProcesando = '';
        let ultimoCodigoConsultado = '';

        const codigoValido = codig =>
            /^\d{3}$/.test(codig) || /^A\d{3}$/.test(codig);

        const limpiarVendedor = () => {
            state.vendedor = null;
            if (vendedor) {
                vendedor.value = '';
                vendedor.dataset.codig = '';
            }
        };

        const obtenerVendedor = async codigoEntrada => {
            const codig = String(
                codigoEntrada ?? vendedor?.value ?? ''
            ).trim().toUpperCase();

            if (!codig || !codigoValido(codig)) return null;
            if (codigoProcesando === codig) return state.vendedor;
            if (ultimoCodigoConsultado === codig) return state.vendedor;

            codigoProcesando = codig;

            try {
                const body = new URLSearchParams({
                    accion: 'buscar-vendedor',
                    codig
                });

                const respuesta = await fetch(ctx.R, {
                    method: 'POST',
                    body,
                    cache: 'no-store'
                });

                const datos = await respuesta.json();

                if (!respuesta.ok || !datos.ok) {
                    throw new Error(
                        datos.mensaje ||
                        'No se pudo consultar el vendedor.'
                    );
                }

                /* La consulta ya fue ejecutada para este código. */
                ultimoCodigoConsultado = codig;

                if (!datos.encontrado || !datos.vendedor) {
                    limpiarVendedor();
                    return null;
                }

                state.vendedor = datos.vendedor;

                /* ====================================================
                   El mismo input muestra nombr + primer apellido.
                   El código queda guardado en data-codig y el ID
                   permanece en state.vendedor para guardar la operación.
                ==================================================== */
                if (vendedor) {
                    vendedor.value = datos.vendedor.display || '';
                    vendedor.dataset.codig =
                        datos.vendedor.codig || codig;
                }

                return datos.vendedor;

            } catch (error) {
                /* Si falla, permitimos reintentar el mismo código. */
                ultimoCodigoConsultado = '';
                limpiarVendedor();
                console.warn('Vendedor:', error.message);
                return null;

            } finally {
                codigoProcesando = '';
            }
        };

        vendedor?.addEventListener('input', () => {
            const valor = vendedor.value
                .toUpperCase()
                .replace(/\s/g, '');

            vendedor.value = valor;
            vendedor.dataset.codig = '';
            state.vendedor = null;
            ultimoCodigoConsultado = '';

            clearTimeout(temporizadorVendedor);

            /* Todavía no está completo: NO consultar PHP. */
            if (!codigoValido(valor)) return;

            /* ====================================================
               Espera breve para permitir pegado/escritura completa,
               pero nunca dispara consultas para 0, 00, etc.
            ==================================================== */
            temporizadorVendedor = setTimeout(() => {
                obtenerVendedor(valor);
            }, 250);
        });

        vendedor?.addEventListener('change', () => {
            clearTimeout(temporizadorVendedor);
            const valor = vendedor.value
                .toUpperCase()
                .replace(/\s/g, '');

            if (codigoValido(valor)) {
                obtenerVendedor(valor);
            }
        });

        almacen?.addEventListener('change', () => {
            state.almacenId = almacen.value;
            
        });

        /* ====================================================
           NIVEL 4 — CONTEXTO DE LA OPERACIÓN

           OPERA es el coordinador. Aquí se valida el contexto
           común que necesitan los demás submódulos.

           CLIENTE:
           - Puede existir en venta.clien.
           - Puede ser nuevo.
           - No exige clienId para abrir PRODUCTOS.
           - Debe estar identificado por docum + razón/nombr.

           VENDEDOR:
           - Debe estar identificado y tener ID válido.

           ALMACÉN:
           - Debe estar seleccionado y tener ID válido.

           PLACA no forma parte del bloqueo de PRODUCTOS.
        ==================================================== */
        const validarContextoOperacion = (requiereProductos = false, mostrar = true) => {

            const faltantes = [];

            if (!state.vendedor?.id) {
                faltantes.push('vendedor');
            }

            if (!state.almacenId) {
                faltantes.push('almacén');
            }

            if (requiereProductos && !state.detalles.length) {
                faltantes.push('producto');
            }

            if (!faltantes.length) {
                return { ok: true, mensaje: '', faltantes: [] };
            }

            const mensaje =
                `Complete ${faltantes.join(', ')} antes de continuar.`;

            if (mostrar) {
                ctx.api.toas?.resu?.(mensaje, 'err');
            }

            return {
                ok: false,
                mensaje,
                faltantes
            };
        };

        /* ====================================================
           NIVEL 4.1 — MANTENER VIVA LA OPERACIÓN

           El latido informa que la ventana principal sigue activa.
           No reemplaza la validación ni la reserva del servidor.
        ==================================================== */
        const parar = () => {

            if (tmLat) {
                clearInterval(tmLat);
                tmLat = null;
            }

            if (tmAct) {
                clearTimeout(tmAct);
                tmAct = null;
            }
        };

        const actividad = () => {

            if (
                !state.operacionId ||
                !state.detalles.length ||
                sali
            ) return;

            clearTimeout(tmAct);
            tmAct = setTimeout(() => {
                void latir();
            }, ACTMS);
        };

        const latir = async () => {

            const id = Number(state.operacionId || 0);
            if (
                !id ||
                !state.detalles.length ||
                latEn ||
                sali
            ) return;

            latEn = true;

            try {
                const body = new URLSearchParams({
                    accion: 'latid',
                    id: String(id)
                });

                const resp = await fetch(ctx.R, {
                    method: 'POST',
                    body,
                    cache: 'no-store'
                });

                const data = await resp.json();

                if (!resp.ok || !data.ok) {
                    throw new Error(
                        data.mensaje ||
                        'No se pudo mantener activa la operación.'
                    );
                }

            } catch (erro) {

            } finally {
                latEn = false;
            }
        };

        const inici = () => {

            if (
                !state.operacionId ||
                !state.detalles.length
            ) return;

            /* Cada nueva operación vuelve a permitir su liberación al cerrar. */
            sali = false;

            if (tmLat) return;

            void latir();

            tmLat = setInterval(
                latir,
                LATMS
            );
        };

        /* ====================================================
           NIVEL 4.2 — SINCRONIZAR BORRADOR

           La persistencia pertenece al coordinador OPER.
           PROD, TABL y ACCI solo solicitan esta operación mediante
           ctx.api; ninguno conoce la escritura del backend.
        ==================================================== */
        const sincronizarBorrador = async () => {

            if (!state.detalles.length) {
                throw new Error('La operación no tiene productos.');
            }

            const clien = state.clien || {};

            const body = new URLSearchParams({
                accion: 'guardar-borrador',
                opera: String(state.operacionId || 0),
                clien: state.clienId ? String(state.clienId) : '',
                tipod: String(clien.tipod || ''),
                docum: String(clien.docum || ''),
                razon: String(clien.razon || ''),
                direc: String(clien.direc || ''),
                vende: String(state.vendedor?.id || 0),
                almac: String(state.almacenId || 0),
                placa: String(state.placa || ''),
                fecha: String(state.fecha || ''),
                obser: String(state.obser || ''),
                detalles: JSON.stringify(
                    state.detalles.map(item => ({
                        produ: Number(item.produ),
                        canti: Number(item.canti),
                        preci: Number(item.preci)
                    }))
                )
            });

            const respuesta = await fetch(ctx.R, {
                method: 'POST',
                body,
                cache: 'no-store'
            });

            const datos = await respuesta.json();

            if (!respuesta.ok || !datos.ok) {
                throw new Error(
                    datos.mensaje ||
                    'No se pudo actualizar la operación.'
                );
            }

            state.operacionId = Number(datos.opera || 0);
            inici();

            state.estados = Array.isArray(datos.estados)
                ? datos.estados
                : [];

            if (datos.clien) {
                state.clienId = String(datos.clien);
                if (state.clien) state.clien.id = state.clienId;
            }

            if (datos.calculo) {
                state.subto = Number(datos.calculo.subto || 0);
                state.igv = Number(datos.calculo.igv || 0);
                state.total = Number(datos.calculo.total || 0);
            }

            return datos;
        };

        /* ====================================================
           NIVEL 5 — FORMATO DE PLACA
           Formato obligatorio: ABC-123.
           El mismo input coloca automáticamente el guion.
        ==================================================== */
        const formatearPlaca = valor => {
            const limpio = String(valor ?? '')
                .toUpperCase()
                .replace(/[^A-Z0-9]/g, '')
                .slice(0, 6);

            if (limpio.length <= 3) return limpio;
            return limpio.slice(0, 3) + '-' + limpio.slice(3);
        };

        obser?.addEventListener('input', () => {
            state.obser = obser.value;
        });

        placa?.addEventListener('input', () => {
            placa.value = formatearPlaca(placa.value);
            state.placa = placa.value;
            
        });

        placa?.addEventListener('blur', () => {
            placa.value = formatearPlaca(placa.value);
            state.placa = placa.value;
        });

        cargarAlmacenes(state.opciones.almacenes || []);
        if (vendedor) {
            vendedor.value = '';
            vendedor.dataset.codig = '';
            state.vendedor = null;
            
        }

        /* ====================================================
           NIVEL 4.3 — ACTIVIDAD DE LA VENTANA

           OPERA conserva la actividad y la registra en el coordinador
           para que el cierre de modal/sesión termine todo el ciclo.
        ==================================================== */
        vent?.addEventListener('pointerdown', actividad);
        vent?.addEventListener('keydown', actividad);
        vent?.addEventListener('input', actividad);
        vent?.addEventListener('change', actividad);

        const vis = () => {
            if (!document.hidden) actividad();
        };

        document.addEventListener('visibilitychange', vis);

        /* ====================================================
           NIVEL 5 — LIBERAR RESERVA Y TERMINAR CICLO

           MODAL  → fetch keepalive para recibir respuesta.
           PAGEHIDE → sendBeacon como salida del documento.
        ==================================================== */
        const libra = (opid, usarBeacon = false) => {

            const id = Number(opid || 0);
            if (!id || sali) return;

            sali = true;
            parar();

            const body = new URLSearchParams({
                accion: 'cancelar',
                id: String(id)
            });

            /* El ID se invalida antes del envío para impedir que otro
               listener o actividad reabra el ciclo localmente. */
            state.operacionId = 0;
            state.estados = [];

            if (usarBeacon) {
                try {
                    if (typeof navigator.sendBeacon === 'function') {
                        navigator.sendBeacon(ctx.R, body);
                        return;
                    }
                } catch (_) {}
                return;
            }

            void fetch(ctx.R, {
                method: 'POST',
                body,
                keepalive: true,
                cache: 'no-store',
                credentials: 'same-origin'
            }).catch(() => {
                try {
                    if (typeof navigator.sendBeacon === 'function') {
                        navigator.sendBeacon(ctx.R, body);
                    }
                } catch (_) {}
            });
        };

        ctx.registrarLimpieza?.(motivo => {
            const id = Number(state.operacionId || 0);

            parar();
            clearTimeout(tmAct);

            document.removeEventListener(
                'visibilitychange',
                vis
            );

            window.NURBAK_MINI?.cerrarPorModulo?.(
                'opera-productos'
            );

            if (id) {
                libra(id, motivo === 'pagehide');
            } else {
                sali = true;
            }
        });

        ctx.api.obtenerVendedor = obtenerVendedor;
        ctx.api.cargarAlmacenes = cargarAlmacenes;
        ctx.api.validarContextoOperacion = validarContextoOperacion;
        ctx.api.validarContextoProducto = () =>
            validarContextoOperacion(false);
        const vaci = async () => {

            const id = Number(state.operacionId || 0);
            if (!id) return { ok: true };

            const body = new URLSearchParams({
                accion: 'vacia',
                id: String(id)
            });

            const respuesta = await fetch(ctx.R, {
                method: 'POST',
                body,
                cache: 'no-store'
            });

            const datos = await respuesta.json();

            if (!respuesta.ok || !datos.ok) {
                throw new Error(
                    datos.mensaje ||
                    'No se pudo liberar la reserva.'
                );
            }

            return datos;
        };

        ctx.api.sincronizarBorrador = sincronizarBorrador;
        ctx.api.vaci = vaci;
        ctx.api.parar = parar;
        ctx.api.sali = () => {
            sali = true;
            parar();
        };
    };

    window.NURBAK_OPERA_MODULOS = window.NURBAK_OPERA_MODULOS || {};
    window.NURBAK_OPERA_MODULOS.opera = iniciar;
})();
