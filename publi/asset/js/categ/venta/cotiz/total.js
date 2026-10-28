/* ============================================================
   Ruta: publi/asset/js/categ/venta/cotiz/total.js
   Módulo: Ventas / Cotización / Totales

   RESPONSABILIDAD:
   Sumar los importes finales de línea en centavos enteros y
   calcular subtotal e IGV de la cotización.
============================================================ */
(() => {
    const iniciar = ctx => {
        const subto = ctx.q('[data-cotiz-subto]');
        const igv = ctx.q('[data-cotiz-igv]');
        const total = ctx.q('[data-cotiz-total]');

        const centavos = valor => {
            const texto = String(valor ?? '0').trim();
            const m = texto.match(/^(\d+)(?:\.(\d{0,2}))?$/);
            if (!m) return 0n;
            return BigInt(m[1]) * 100n + BigInt((m[2] || '').padEnd(2, '0') || '0');
        };
        const formato = valor => `${valor / 100n}.${String(valor % 100n).padStart(2, '0')}`;

        const calcular = () => {
            const importe = ctx.state.detalles.reduce((suma, item) => suma + centavos(item.total), 0n);
            // Cotización presenta importes con IGV incluido: base = total / 1.18.
            const base = (importe * 100n + 59n) / 118n;
            const impuesto = importe - base;

            ctx.state.subto = formato(base);
            ctx.state.igv = formato(impuesto);
            ctx.state.total = formato(importe);
            if (subto) subto.textContent = ctx.state.subto;
            if (igv) igv.textContent = ctx.state.igv;
            if (total) total.textContent = ctx.state.total;
            return {subto:ctx.state.subto, igv:ctx.state.igv, total:ctx.state.total};
        };

        ctx.api.calcularTotales = calcular;
        calcular();
    };
    window.NURBAK_COTIZ_MODULOS.total = iniciar;
})();
