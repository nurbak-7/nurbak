/* ============================================================
   Ruta: publi/asset/js/categ/venta/cotiz/total.js
   Módulo: Ventas / Cotización / Totales

   RESPONSABILIDAD:
   Calcular subtotal, IGV 18 % y total usando el mismo criterio
   económico de Operaciones.
============================================================ */
(() => {
    const iniciar = ctx => {
        const subto = ctx.q('[data-cotiz-subto]');
        const igv = ctx.q('[data-cotiz-igv]');
        const total = ctx.q('[data-cotiz-total]');
        const calcular = () => {
            const importe = ctx.state.detalles.reduce((suma,item) => suma + Number(item.canti || 0) * Number(item.preun || 0), 0);
            const totalRed = Math.round(importe * 100) / 100;
            const base = Math.round((totalRed / 1.18) * 100) / 100;
            const impuesto = Math.round((totalRed - base) * 100) / 100;
            ctx.state.subto = base; ctx.state.igv = impuesto; ctx.state.total = totalRed;
            if (subto) subto.textContent = base.toFixed(2);
            if (igv) igv.textContent = impuesto.toFixed(2);
            if (total) total.textContent = totalRed.toFixed(2);
            return {subto:base,igv:impuesto,total:totalRed};
        };
        ctx.api.calcularTotales = calcular;
        calcular();
    };
    window.NURBAK_COTIZ_MODULOS.total = iniciar;
})();
