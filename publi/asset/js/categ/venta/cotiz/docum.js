/* ============================================================
   Ruta: publi/asset/js/categ/venta/cotiz/docum.js
   Módulo: Ventas / Cotización / Documento

   RESPONSABILIDAD:
   Fecha, vigencia y numeración de Cotización.
============================================================ */
(() => {
    const formato = valor => {
        const n = Number(valor);
        if (!Number.isFinite(n) || n < 0) return '';
        return String(Math.trunc(n)).padStart(4,'0');
    };

    const fechaHoy = () => {
        const d = new Date();
        return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    };

    const fechaVigencia = fecha => {
        const base = fecha ? new Date(`${fecha}T00:00:00`) : new Date();
        if (Number.isNaN(base.getTime())) return '';
        base.setDate(base.getDate() + 3);
        return `${base.getFullYear()}-${String(base.getMonth()+1).padStart(2,'0')}-${String(base.getDate()).padStart(2,'0')}`;
    };

    const iniciar = async ctx => {
        const fecha = ctx.q('[data-cotiz-fecha]');
        const vigia = ctx.q('[data-cotiz-vigia]');
        const serie = ctx.q('[data-cotiz-serie]');
        const numero = ctx.q('[data-cotiz-numero]');

        fecha.value = fechaHoy();
        vigia.value = fechaVigencia(fecha.value);
        ctx.state.fecha = fecha.value;
        ctx.state.vigia = vigia.value;

        fecha.addEventListener('change', () => {
            ctx.state.fecha = fecha.value;
            vigia.value = fechaVigencia(fecha.value);
            ctx.state.vigia = vigia.value;
        });
        vigia.addEventListener('change', () => { ctx.state.vigia = vigia.value; });

        const datos = await ctx.pedir({accion:'opciones'});
        ctx.state.serie = datos.numero?.serie || 'C001';
        ctx.state.numero = Number(datos.numero?.siguiente || 0);
        serie.value = ctx.state.serie;
        numero.value = formato(ctx.state.numero);
    };

    window.NURBAK_COTIZ_MODULOS.docum = iniciar;
    window.NURBAK_COTIZ_MODULOS.formatoNumero = formato;
})();
