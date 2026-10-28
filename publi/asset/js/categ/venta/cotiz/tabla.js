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
                        <td><input type="number" min="0.001" step="0.001" value="${numero(item.canti)}" data-cotiz-canti="${i}" aria-label="Cantidad"></td>
                        <td><input type="number" min="0" step="0.01" value="${numero(item.preun).toFixed(2)}" data-cotiz-preun="${i}" aria-label="Precio unitario"></td>
                        <td><input type="number" min="0" step="0.01" value="${numero(item.total).toFixed(2)}" data-cotiz-total-fila="${i}" aria-label="Total"></td>
                        <td><button type="button" data-cotiz-quitar="${i}" aria-label="Eliminar línea">×</button></td>
                    </tr>`;
            }).join('');
            ctx.api.calcularTotales?.();
        };

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
            const val = Number((can || pre || total).value);
            if (!Number.isFinite(val) || val < 0 || (can && val <= 0)) return;

            if (can) item.canti = val;
            else if (pre) item.preun = Math.round((val + Number.EPSILON) * 100) / 100;
            else {
                const qty = numero(item.canti);
                if (qty <= 0) return;
                item.preun = Math.round((val / qty + Number.EPSILON) * 100) / 100;
            }
            item.total = importe(item);
            const totalInput = body.querySelector(`[data-cotiz-total-fila="${i}"]`);
            const preInput = body.querySelector(`[data-cotiz-preun="${i}"]`);
            if (totalInput) totalInput.value = item.total.toFixed(2);
            if (preInput) preInput.value = numero(item.preun).toFixed(2);
            ctx.api.calcularTotales?.();
        });

        body.addEventListener('change', evento => {
            const can = evento.target.closest('[data-cotiz-canti]');
            if (can) {
                const i = Number(can.dataset.cotizCanti);
                const item = ctx.state.detalles[i];
                const val = Number(can.value);
                if (!item || !Number.isFinite(val) || val <= 0) {
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
