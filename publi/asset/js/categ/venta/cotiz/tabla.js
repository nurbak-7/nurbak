/* ============================================================
   Ruta: publi/asset/js/categ/venta/cotiz/tabla.js
   Módulo: Ventas / Cotización / Tabla

   RESPONSABILIDAD:
   Dibujar, editar y eliminar filas del detalle.
============================================================ */
(() => {
    const iniciar = ctx => {
        const body = ctx.q('[data-cotiz-detalles]');
        if (!body) return;

        const numero = valor => Number.isFinite(Number(valor)) ? Number(valor) : 0;
        const importe = item => Math.round((numero(item.canti) * numero(item.preun) + Number.EPSILON) * 100) / 100;
        const dibujar = () => {
            body.innerHTML = ctx.state.detalles.map((item, i) => {
                item.total = importe(item);
                const codig = item.vario
                    ? `<input type="text" maxlength="80" value="${ctx.esc(item.codig || '')}" data-cotiz-codig="${i}" aria-label="Código">`
                    : ctx.esc(item.codig || '—');
                const descr = item.vario
                    ? `<input type="text" maxlength="255" value="${ctx.esc(item.descr || '')}" data-cotiz-descr="${i}" aria-label="Descripción">`
                    : ctx.esc(item.descr || '');
                return `
                    <tr>
                        <td>${i + 1}</td>
                        <td>${codig}</td>
                        <td>${descr}</td>
                        <td><input type="text" inputmode="numeric" pattern="[1-9][0-9]*" value="${numero(item.canti)}" data-cotiz-canti="${i}" aria-label="Cantidad" autocomplete="off"></td>
                        <td><input type="text" inputmode="decimal" value="${numero(item.preun).toFixed(2)}" data-cotiz-preun="${i}" aria-label="Precio unitario" autocomplete="off"></td>
                        <td><input type="text" inputmode="decimal" value="${numero(item.total).toFixed(2)}" data-cotiz-total-fila="${i}" aria-label="Total" autocomplete="off"></td>
                        <td><button type="button" data-cotiz-quitar="${i}" aria-label="Eliminar línea">×</button></td>
                    </tr>`;
            }).join('');
            ctx.api.calcularTotales?.();
        };

        // Facilita reemplazar el valor completo sin borrarlo primero.
        body.addEventListener('focusin', evento => {
            const campo = evento.target.closest('input[data-cotiz-codig], input[data-cotiz-descr], input[data-cotiz-canti], input[data-cotiz-preun], input[data-cotiz-total-fila]');
            if (campo) campo.select();
        });

        body.addEventListener('click', evento => {
            const campo = evento.target.closest('input[data-cotiz-codig], input[data-cotiz-descr], input[data-cotiz-canti], input[data-cotiz-preun], input[data-cotiz-total-fila]');
            if (campo) campo.select();
        });

        body.addEventListener('input', evento => {
            const target = evento.target;
            const codig = target.closest('[data-cotiz-codig]');
            const descr = target.closest('[data-cotiz-descr]');
            if (codig) {
                const item = ctx.state.detalles[Number(codig.dataset.cotizCodig)];
                if (item) item.codig = codig.value;
                return;
            }
            if (descr) {
                const item = ctx.state.detalles[Number(descr.dataset.cotizDescr)];
                if (item) item.descr = descr.value;
                return;
            }

            const can = target.closest('[data-cotiz-canti]');
            const pre = target.closest('[data-cotiz-preun]');
            const total = target.closest('[data-cotiz-total-fila]');
            if (!can && !pre && !total) return;
            const i = Number((can || pre || total).dataset[can ? 'cotizCanti' : pre ? 'cotizPreun' : 'cotizTotalFila']);
            const item = ctx.state.detalles[i];
            if (!item) return;

            const texto = (can || pre || total).value.trim();
            // Permite vaciar temporalmente el campo mientras se reemplaza el valor.
            if (texto === '') return;

            if (can) {
                // Cantidad entera positiva; no convertir decimales silenciosamente.
                if (!/^\d+$/.test(texto)) return;
                const val = Number(texto);
                if (!Number.isSafeInteger(val) || val <= 0) return;
                item.canti = val;
            } else {
                const val = Number(texto);
                if (!Number.isFinite(val) || val < 0) return;
                if (pre) {
                    item.preun = Math.round((val + Number.EPSILON) * 100) / 100;
                } else {
                    const qty = numero(item.canti);
                    if (!Number.isInteger(qty) || qty <= 0) return;
                    item.preun = Math.round((val / qty + Number.EPSILON) * 100) / 100;
                }
            }

            item.total = importe(item);
            const totalInput = body.querySelector(`[data-cotiz-total-fila="${i}"]`);
            const preInput = body.querySelector(`[data-cotiz-preun="${i}"]`);
            if (totalInput && !total) totalInput.value = item.total.toFixed(2);
            if (preInput && !pre) preInput.value = numero(item.preun).toFixed(2);
            if (total && preInput) preInput.value = numero(item.preun).toFixed(2);
            ctx.api.calcularTotales?.();
        });

        body.addEventListener('change', evento => {
            const can = evento.target.closest('[data-cotiz-canti]');
            if (can) {
                const i = Number(can.dataset.cotizCanti);
                const item = ctx.state.detalles[i];
                const texto = can.value.trim();
                const val = Number(texto);
                if (!item || !/^\d+$/.test(texto) || !Number.isSafeInteger(val) || val <= 0) {
                    dibujar();
                    return;
                }
                item.canti = val;
                item.total = importe(item);
                dibujar();
            }
        });

        body.addEventListener('click', evento => {
            const btn = evento.target.closest('[data-cotiz-quitar]');
            if (!btn) return;
            ctx.state.detalles.splice(Number(btn.dataset.cotizQuitar), 1);
            dibujar();
        });

        ctx.api.dibujarDetalles = dibujar;
        dibujar();
    };

    window.NURBAK_COTIZ_MODULOS.tabla = iniciar;
})();
