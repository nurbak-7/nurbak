/* ============================================================
   Ruta: publi/asset/js/categ/venta/opera/total.js
   Función: Recurso del sistema.
============================================================ */
/* ============================================================
   Módulo: Operaciones / Totales
   RESPONSABILIDAD: subto, IGV y total.
============================================================ */
(() => {
    const iniciar = ctx => {
        const { q, state } = ctx;

        const subto = q('[data-opera-subto]');
        const igv = q('[data-opera-igv]');
        const total = q('[data-opera-total]');

        const calcularTotales = () => {
            const importe = state.detalles.reduce((suma, item) =>
                suma + Number(item.canti) * Number(item.preci), 0);
            const base = importe / 1.18;
            const impuesto = importe - base;
            state.subto = base;
            state.igv = impuesto;
            state.total = importe;
            if (subto) subto.textContent = base.toFixed(2);
            if (igv) igv.textContent = impuesto.toFixed(2);
            if (total) total.textContent = importe.toFixed(2);
            
        };

        ctx.api.calcularTotales = calcularTotales;
        calcularTotales();
    };
    window.NURBAK_OPERA_MODULOS = window.NURBAK_OPERA_MODULOS || {};
    window.NURBAK_OPERA_MODULOS.total = iniciar;
})();
