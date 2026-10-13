/* ============================================================
   Ruta: publi/asset/js/categ/venta/opera/tabla.js
   Módulo: Ventas / Operaciones / Tabla

   RESPONSABILIDAD:
   Dibujar, editar y eliminar filas de productos.

   REGLAS:
   - QTY cambia la reserva cuando se confirma el cambio.
   - P.UNIT afecta el cálculo, no la reserva.
   - TOTAL ajusta P.UNIT manteniendo QTY.
   - El producto repetido se mantiene en una sola fila.
============================================================ */

(() => {

    const iniciar = ctx => {

        const { q, state } = ctx;
        const body = q('[data-opera-detalles]');

        const dibujarDetalles = () => {

            if (!body) return;

            body.innerHTML = state.detalles.map((item, i) => {

                const importe =
                    Number(item.canti) *
                    Number(item.preci);

                return `
                    <tr>
                        <td>${i + 1}</td>
                        <td>${ctx.esc(item.codig)}</td>
                        <td>${ctx.esc(item.descr)}</td>
                        <td>
                            <input
                                type="number"
                                min="0.001"
                                step="0.001"
                                value="${item.canti}"
                                data-opera-canti-fila="${i}"
                            >
                        </td>
                        <td>
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                value="${Number(item.preci).toFixed(2)}"
                                data-opera-preci-fila="${i}"
                            >
                        </td>
                        <td>
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                value="${importe.toFixed(2)}"
                                data-opera-total-fila="${i}"
                            >
                        </td>
                        <td>
                            <button
                                type="button"
                                data-opera-quitar="${i}"
                            >×</button>
                        </td>
                    </tr>`;
            }).join('');

            ctx.api.calcularTotales?.();
        };

        const actualizarImporte = indice => {

            const item = state.detalles[indice];
            const campo = body?.querySelector(
                `[data-opera-total-fila="${indice}"]`
            );

            if (!item || !campo) return;

            campo.value = (
                Number(item.canti) *
                Number(item.preci)
            ).toFixed(2);

            ctx.api.calcularTotales?.();
        };

        const sincronizar = async indice => {
            try {
                await ctx.api.sincronizarBorrador?.();
                actualizarImporte(indice);
            } catch (error) {
                throw error;
            }
        };

        /* ====================================================
           NIVEL 3 — QTY
        ==================================================== */
        body?.addEventListener('change', async e => {

            const campo =
                e.target.closest('[data-opera-canti-fila]');

            if (!campo) return;

            const indice = Number(campo.dataset.operaCantiFila);
            const item = state.detalles[indice];
            if (!item) return;

            const anterior = Number(item.canti);
            const nueva = Number(campo.value);

            if (!Number.isFinite(nueva) || nueva <= 0) {
                campo.value = anterior;
                return;
            }

            item.canti = nueva;
            actualizarImporte(indice);

            if (!state.operacionId) {
                return;
            }

            campo.disabled = true;

            try {
                await sincronizar(indice);
            } catch (error) {
                item.canti = anterior;
                dibujarDetalles();
                alert(error.message);
            } finally {
                campo.disabled = false;
            }
        });

        /* ====================================================
           NIVEL 4 — P.UNIT
        ==================================================== */
        body?.addEventListener('input', e => {

            const campo =
                e.target.closest('[data-opera-preci-fila]');

            if (!campo) return;

            const indice = Number(campo.dataset.operaPreciFila);
            const item = state.detalles[indice];
            if (!item) return;

            item.preci = Math.max(0, Number(campo.value || 0));
            actualizarImporte(indice);
        });

        body?.addEventListener('change', async e => {

            const campo =
                e.target.closest('[data-opera-preci-fila]');

            if (!campo) return;

            const indice = Number(campo.dataset.operaPreciFila);
            const item = state.detalles[indice];
            if (!item) return;

            if (!Number.isFinite(Number(campo.value))) {
                dibujarDetalles();
                return;
            }

            campo.value = Number(item.preci).toFixed(2);

            if (!state.operacionId) {
                return;
            }

            campo.disabled = true;

            try {
                await sincronizar(indice);
            } catch (error) {
                dibujarDetalles();
                alert(error.message);
            } finally {
                campo.disabled = false;
            }
        });

        /* ====================================================
           NIVEL 5 — TOTAL DE LÍNEA
        ==================================================== */
        body?.addEventListener('input', e => {

            const campo =
                e.target.closest('[data-opera-total-fila]');

            if (!campo) return;

            const indice = Number(campo.dataset.operaTotalFila);
            const item = state.detalles[indice];
            if (!item) return;

            const total = Math.max(0, Number(campo.value || 0));
            const canti = Number(item.canti);

            if (canti > 0) {
                item.preci = total / canti;
            }

            ctx.api.calcularTotales?.();
        });

        body?.addEventListener('change', async e => {

            const campo =
                e.target.closest('[data-opera-total-fila]');

            if (!campo) return;

            const indice = Number(campo.dataset.operaTotalFila);
            const item = state.detalles[indice];
            if (!item) return;

            const total = Math.max(0, Number(campo.value || 0));
            const canti = Number(item.canti);

            if (!Number.isFinite(total) || canti <= 0) {
                dibujarDetalles();
                return;
            }

            item.preci = total / canti;
            actualizarImporte(indice);

            if (!state.operacionId) {
                return;
            }

            campo.disabled = true;

            try {
                await sincronizar(indice);
            } catch (error) {
                dibujarDetalles();
                alert(error.message);
            } finally {
                campo.disabled = false;
            }
        });

        /* ====================================================
           NIVEL 6 — ELIMINAR FILA Y LIBERAR RESERVA
        ==================================================== */
        body?.addEventListener('click', async e => {

            const boton =
                e.target.closest('[data-opera-quitar]');

            if (!boton) return;

            const indice = Number(boton.dataset.operaQuitar);
            if (!state.detalles[indice]) return;

            boton.disabled = true;

            const eliminado = state.detalles.splice(indice, 1)[0];
            dibujarDetalles();

            if (!state.operacionId) return;

            try {

                if (!state.detalles.length) {
                    await ctx.api.vaci?.();
                    ctx.api.parar?.();
                    state.estados = [];
                    return;
                }

                await ctx.api.sincronizarBorrador?.();

            } catch (error) {

                state.detalles.splice(indice, 0, eliminado);
                dibujarDetalles();
                alert(error.message);
            }
        });

        ctx.api.dibujarDetalles = dibujarDetalles;
        dibujarDetalles();
    };

    window.NURBAK_OPERA_MODULOS =
        window.NURBAK_OPERA_MODULOS || {};

    window.NURBAK_OPERA_MODULOS.tabla = iniciar;

})();
