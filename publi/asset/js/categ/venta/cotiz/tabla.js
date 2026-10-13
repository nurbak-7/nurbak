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

        const dibujar = () => {
            body.innerHTML = ctx.state.detalles.map((item,i) => {
                const total = Number(item.canti || 0) * Number(item.preun || 0);
                return `
                    <tr>
                        <td>${i+1}</td>
                        <td>${ctx.esc(item.codig || '—')}</td>
                        <td>${ctx.esc(item.descr || '')}</td>
                        <td><input type="number" min="0.001" step="0.001" value="${Number(item.canti || 0)}" data-cotiz-canti="${i}"></td>
                        <td><input type="number" min="0" step="0.01" value="${Number(item.preun || 0).toFixed(2)}" data-cotiz-preun="${i}"></td>
                        <td><input type="number" min="0" step="0.01" value="${total.toFixed(2)}" data-cotiz-total-fila="${i}"></td>
                        <td><button type="button" data-cotiz-quitar="${i}">×</button></td>
                    </tr>`;
            }).join('');
            ctx.api.calcularTotales?.();
        };

        body.addEventListener('input', evento => {
            const total = evento.target.closest('[data-cotiz-total-fila]');
            if (total) {
                const i = Number(total.dataset.cotizTotalFila);
                const item = ctx.state.detalles[i];
                if (!item) return;
                const val = Math.max(0, Number(total.value || 0));
                if (Number(item.canti) > 0) item.preun = val / Number(item.canti);
                ctx.api.calcularTotales?.();
                return;
            }

            const pre = evento.target.closest('[data-cotiz-preun]');
            if (pre) {
                const i = Number(pre.dataset.cotizPreun);
                const item = ctx.state.detalles[i];
                if (!item) return;
                item.preun = Math.max(0, Number(pre.value || 0));
                ctx.api.calcularTotales?.();
            }
        });

        body.addEventListener('change', evento => {
            const can = evento.target.closest('[data-cotiz-canti]');
            if (can) {
                const i = Number(can.dataset.cotizCanti);
                const item = ctx.state.detalles[i];
                if (!item) return;
                const val = Number(can.value || 0);
                if (!Number.isFinite(val) || val <= 0) {
                    dibujar();
                    return;
                }
                item.canti = val;
                dibujar();
                return;
            }

            const pre = evento.target.closest('[data-cotiz-preun]');
            if (pre) {
                const i = Number(pre.dataset.cotizPreun);
                const item = ctx.state.detalles[i];
                if (!item) return;
                item.preun = Math.max(0, Number(pre.value || 0));
                dibujar();
                return;
            }

            const total = evento.target.closest('[data-cotiz-total-fila]');
            if (total) {
                const i = Number(total.dataset.cotizTotalFila);
                const item = ctx.state.detalles[i];
                if (!item) return;
                const val = Math.max(0, Number(total.value || 0));
                if (Number(item.canti) > 0) item.preun = val / Number(item.canti);
                dibujar();
            }
        });

        body.addEventListener('click', evento => {
            const btn = evento.target.closest('[data-cotiz-quitar]');
            if (!btn) return;
            ctx.state.detalles.splice(Number(btn.dataset.cotizQuitar),1);
            dibujar();
        });

        ctx.api.dibujarDetalles = dibujar;
        dibujar();
    };

    window.NURBAK_COTIZ_MODULOS.tabla = iniciar;
})();
