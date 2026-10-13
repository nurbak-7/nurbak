/* ============================================================
   Ruta: publi/asset/js/categ/venta/opera/accio.js
   Módulo: Ventas / Operaciones / Acciones

   NIVELES:
   1. Inicio del módulo
   2. Conexión Toast
   3. Cierre
   4. Validar guardado
      4.1 Contexto común
      4.2 Documento
      4.3 Cliente
      4.4 Productos
      4.5 Pago
   5.5 Cerrar MINI de productos
   6. Cancelar en servidor
   7. Guardar en servidor
   8. Confirmación
   9. Eventos de botones
   10. Submit defensivo
   11. API del módulo

   RESPONSABILIDAD:
   Guardar, cancelar y confirmar la operación.

   REGLAS:
   - Guardar con productos pide confirmación.
   - Cancelar con productos pide confirmación.
   - La X con productos pide la misma confirmación.
   - Guardar/cancelar confirmado cierra la ventana automáticamente.
============================================================ */

(() => {

    /* ========================================================
       NIVEL 1 — INICIO DEL MÓDULO
    ======================================================== */
    const iniciar = ctx => {

        const { q, state } = ctx;
        const form = q('[data-opera-form]');
        const btnGuar = q('[data-opera-guardar]');
        const btnCanc = q('[data-opera-cancelar]');

        /* ====================================================
           NIVEL 3 — CONEXIÓN REAL CON TOAST

           ACCIO no conserva una referencia muerta. Primero usa
           el contexto y, como respaldo, consulta el registro
           del coordinador Toast existente.
        ==================================================== */
        const toas = () =>
            ctx.api.toas ||
            window.NURBAK_TOAST?.obtener?.('vent/oper') ||
            null;

        let ejec = false;

        if (!form || !btnGuar || !btnCanc) {
            return;
        }


        /* ====================================================
           NIVEL 4 — CIERRE
        ==================================================== */
        const cerrar = () => {
            ctx.api.cerrarVentana?.();
        };

        /* ====================================================
           NIVEL 5 — VALIDAR GUARDADO

           La validación ocurre ANTES de mostrar la confirmación.
           Así el usuario corrige primero los campos obligatorios
           y nunca queda una confirmación bloqueada por un error.
        ==================================================== */
        const validarGuardado = (mostrar = true) => {

            const faltantes = [];

            const fecha = q('[data-opera-fecha]');
            const tipo = q('[data-opera-tipo]');
            const serie = q('[data-opera-serie]');
            const numer = q('[data-opera-numer]');
            const docum = q('[data-opera-docum]');
            const razon = q('[data-opera-razon]');

            /* ====================================================
               NIVEL 5.1 — CONTEXTO COMÚN
            ==================================================== */
            const contexto =
                ctx.api.validarContextoOperacion?.(true, false);

            if (contexto && !contexto.ok) {
                faltantes.push(...contexto.faltantes);
            }

            /* ====================================================
               NIVEL 5.2 — DOCUMENTO
            ==================================================== */
            if (!String(fecha?.value || state.fecha || '').trim()) {
                faltantes.push('fecha');
            }

            if (!String(tipo?.value || state.tipo || '').trim()) {
                faltantes.push('tipo');
            }

            if (!String(serie?.value || state.serie || '').trim()) {
                faltantes.push('serie');
            }

            if (!String(numer?.value || state.numer || '').trim()) {
                faltantes.push('número');
            }

            /* ====================================================
               NIVEL 5.3 — CLIENTE

               La dirección no se considera obligatoria en el flujo
               actual. Documento y razón social sí lo son.
            ==================================================== */
            if (!String(docum?.value || '').trim()) {
                if (!faltantes.includes('cliente')) {
                    faltantes.push('RUC/DNI');
                }
            }

            if (!String(razon?.value || '').trim()) {
                if (!faltantes.includes('cliente')) {
                    faltantes.push('razón social');
                }
            }

            /* ====================================================
               NIVEL 5.4 — PRODUCTOS
            ==================================================== */
            if (!state.detalles.length) {
                if (!faltantes.includes('producto')) {
                    faltantes.push('producto');
                }
            } else {
                state.detalles.forEach((item, indice) => {
                    const prod = Number(item.produ);
                    const canti = Number(item.canti);
                    const preci = Number(item.preci);

                    if (
                        !Number.isInteger(prod) ||
                        prod <= 0 ||
                        !Number.isFinite(canti) ||
                        canti <= 0 ||
                        !Number.isFinite(preci) ||
                        preci < 0
                    ) {
                        faltantes.push(`producto ${indice + 1}`);
                    }
                });
            }

            /* ====================================================
               NIVEL 5.5 — PAGO
            ==================================================== */
            const pago =
                ctx.api.validarPago?.() || {
                    ok: false,
                    faltantes: ['forma de pago', 'medio de pago']
                };

            if (!pago.ok) {
                faltantes.push(...pago.faltantes);
            }

            const unicos = [...new Set(faltantes)];

            if (!unicos.length) {
                return { ok: true, faltantes: [] };
            }

            const mensaje =
                `Complete los campos obligatorios: ${unicos.join(', ')}.`;

            if (mostrar) {
                const api = toas();
                api?.resu?.(mensaje, 'err');

                const primero =
                    q('[data-opera-fecha]') &&
                    !String(q('[data-opera-fecha]').value || '').trim()
                        ? q('[data-opera-fecha]')
                        : q('[data-opera-docum]') &&
                          !String(q('[data-opera-docum]').value || '').trim()
                            ? q('[data-opera-docum]')
                            : q('[data-opera-vendedor]') &&
                              !state.vendedor?.id
                                ? q('[data-opera-vendedor]')
                                : q('[data-opera-almacen]') &&
                                  !String(state.almacenId || '').trim()
                                    ? q('[data-opera-almacen]')
                                    : q('[data-opera-medio-pago]') &&
                                      !String(state.medioPago || '').trim()
                                        ? q('[data-opera-medio-pago]')
                                        : null;

                primero?.focus?.();
            }

            return {
                ok: false,
                faltantes: unicos,
                mensaje
            };
        };

        /* ====================================================
           NIVEL 5.5 — CERRAR MINI DE PRODUCTOS

           Guardar o Cancelar dejan de trabajar con el buscador
           de productos abierto. Se cierra solo el MINI de OPER.
        ==================================================== */
        const cerrarMini = () => {
            window.NURBAK_MINI?.cerrarPorModulo?.(
                'opera-productos'
            );
        };

        /* ====================================================
           NIVEL 6 — CANCELAR EN SERVIDOR
        ==================================================== */
        const cance = async () => {

            const id = Number(state.operacionId || 0);

            if (!id) {
                cerrar();
                return;
            }

            const body = new URLSearchParams({
                accion: 'cancelar',
                id: String(id)
            });

            const x = await fetch(ctx.R, {
                method: 'POST',
                body,
                cache: 'no-store',
                credentials: 'same-origin'
            });

            const d = await x.json();

            if (!x.ok || !d.ok) {
                throw new Error(
                    d.mensaje ||
                    'No se pudo cancelar la operación.'
                );
            }

            // SINCRONIZACIÓN | FASE 2
            window.NURBAK_CATE_SINC?.emitir({
                modu: 'opera',
                recu: 'opera',
                acci: 'cance',
                id: id
            });

            // SINCRONIZACIÓN | FASE 2
            window.NURBAK_CATE_SINC?.emitir({
                modu: 'opera',
                recu: 'docum',
                acci: 'emitir',
                id: Number(d.documento_id || 0),
                dato: d
            });
            window.NURBAK_CATE_SINC?.emitir({
                modu: 'opera',
                recu: 'opera',
                acci: 'emitir',
                id: Number(d.opera || 0),
                dato: d
            });

            state.operacionId = 0;
            state.estados = [];
            state.detalles = [];
            ctx.api.parar?.();
            ctx.api.sali?.();

            const api = toas();
            api?.quit?.();
            api?.resu?.(
                'Operación cancelada correctamente.',
                'exi'
            );
            cerrar();
        };

        /* ====================================================
           NIVEL 7 — GUARDAR EN SERVIDOR
        ==================================================== */
        const guard = async () => {

            const val = validarGuardado(false);

            if (!val.ok) {
                throw new Error(val.mensaje);
            }

            const datos =
                await ctx.api.sincronizarBorrador?.();

            if (!datos || !state.operacionId) {
                throw new Error(
                    'No se pudo preparar la operación para guardar.'
                );
            }

            const body = new URLSearchParams({
                accion: 'confirmar',
                id: String(state.operacionId),
                formp: String(state.formaPago || '')
            });

            const x = await fetch(ctx.R, {
                method: 'POST',
                body,
                cache: 'no-store',
                credentials: 'same-origin'
            });

            const d = await x.json();

            if (!x.ok || !d.ok) {
                throw new Error(
                    d.mensaje ||
                    'No se pudo guardar la operación.'
                );
            }

            state.operacionId = 0;
            state.estados = [];
            state.detalles = [];
            ctx.api.parar?.();
            ctx.api.sali?.();

            const api = toas();
            api?.quit?.();
            api?.resu?.(
                'Operación guardada correctamente.',
                'exi'
            );
            cerrar();
        };

        /* ====================================================
           NIVEL 8 — CONFIRMACIÓN
        ==================================================== */
        const conf = tipo => {

            if (ejec) {
                return;
            }

            if (tipo === 'guar') {
                const val = validarGuardado(true);

                if (!val.ok) {
                    return;
                }
            }

            if (!state.detalles.length) {
                if (tipo === 'canc') {
                    cerrar();
                    return;
                }

                return;
            }

            const api = toas();

            if (!api?.conf) {

                console.error(
                    'Operaciones: Toast no está conectado a ACCIO.'
                );
                return;
            }

            const ejecutar = async funcion => {
                ejec = true;

                try {
                    await funcion();
                } finally {
                    ejec = false;
                }
            };

            cerrarMini();

            api.conf(
                tipo,
                () => ejecutar(guard),
                () => ejecutar(cance)
            );
        };

        /* ====================================================
           NIVEL 9 — EVENTOS DE LOS BOTONES PRINCIPALES
        ==================================================== */
        btnGuar?.addEventListener('click', () => {
            conf('guar');
        });

        btnCanc?.addEventListener('click', () => {
            conf('canc');
        });

        /* ====================================================
           NIVEL 10 — SUBMIT DEFENSIVO DEL FORMULARIO
        ==================================================== */
        form?.addEventListener('submit', evento => {
            evento.preventDefault();
            conf('guar');
        });

        /* ====================================================
           NIVEL 11 — API DEL MÓDULO
        ==================================================== */
        ctx.api.conf = conf;
        ctx.api.validarGuardado = validarGuardado;
        ctx.api.most = conf;
    };

    window.NURBAK_OPERA_MODULOS =
        window.NURBAK_OPERA_MODULOS || {};

    window.NURBAK_OPERA_MODULOS.accio = iniciar;

})();
