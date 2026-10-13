/* ============================================================
   Ruta: publi/asset/js/categ/venta/cotiz/accio.js
   Módulo: Ventas / Cotización / Acciones

   RESPONSABILIDAD:
   Validar, confirmar, guardar y cancelar Cotización.
============================================================ */
(() => {
    const iniciar = ctx => {
        const guardarBtn = ctx.q('[data-cotiz-guardar]');
        const cancelarBtn = ctx.q('[data-cotiz-cancelar]');
        let ejecutando = false;
        const toast = () => ctx.api.toas || window.NURBAK_TOAST?.obtener?.('vent/cotiz') || null;

        const guardarReal = async () => {
            if (ejecutando) return;
            ejecutando = true;
            try {
                const form = ctx.api.form();
                const datos = await ctx.pedir({}, {method:'POST',body:new URLSearchParams({accion:'guardar',...form})});
                const c = datos.cotiz || {};
                ctx.state.cotizId = Number(c.id || 0);
                ctx.state.clienId = Number(form.clien || ctx.state.clienId || 0);
                ctx.state.vendeId = Number(form.vende || ctx.state.vendeId || 0);
                ctx.state.serie = c.serie || ctx.state.serie;
                ctx.state.numero = Number(c.numco || ctx.state.numero || 0);
                ctx.q('[data-cotiz-serie]').value = ctx.state.serie;
                ctx.q('[data-cotiz-numero]').value = ctx.api.formatoNumero?.(ctx.state.numero) || String(ctx.state.numero).padStart(4,'0');
                toast()?.quit?.();
                toast()?.resu?.('Cotización guardada correctamente.','exi');
                document.dispatchEvent(new CustomEvent('cotiz:actualizada',{
                    detail:{accion:'guardar',id:ctx.state.cotizId}
                }));
setTimeout(() => ctx.api.cerrarVentana?.(),700);
            } finally { ejecutando=false; }
        };

        const cancelarReal = async () => {
            if (ejecutando) return;
            ejecutando=true;
            try {
                if (ctx.state.cotizId > 0) {
                    await ctx.pedir({}, {method:'POST',body:new URLSearchParams({accion:'cancelar',id:String(ctx.state.cotizId)})});
                }
                toast()?.quit?.();
                toast()?.resu?.('Cotización cancelada correctamente.','exi');
                document.dispatchEvent(new CustomEvent('cotiz:actualizada',{
                    detail:{accion:'cancelar',id:ctx.state.cotizId}
                }));
setTimeout(() => ctx.api.cerrarVentana?.(),700);
            } finally { ejecutando=false; }
        };

        const solicitarGuardar = () => {
            if (ejecutando) return;
            const val = ctx.api.validar?.() || {ok:false,mensaje:'No se pudo validar la cotización.'};
            if (!val.ok) { toast()?.resu?.(val.mensaje,'err'); return; }
            const t=toast();
            if (!t) { guardarReal().catch(error=>toast()?.resu?.(error.message,'err')); return; }
            t.conf?.('guar',guardarReal,()=>{});
        };

        const solicitarCancelar = () => {
            if (ejecutando) return;
            if (!ctx.state.detalles.length && ctx.state.cotizId<=0) { cancelarReal(); return; }
            const t=toast();
            if (!t) { cancelarReal().catch(error=>toast()?.resu?.(error.message,'err')); return; }
            t.conf?.('canc',cancelarReal,()=>{});
        };

        guardarBtn?.addEventListener('click',e=>{e.preventDefault();solicitarGuardar();});
        cancelarBtn?.addEventListener('click',e=>{e.preventDefault();solicitarCancelar();});
        ctx.api.cancelar=solicitarCancelar;
        ctx.api.guardar=solicitarGuardar;
        ctx.api.guardarReal=guardarReal;
        ctx.api.cancelarReal=cancelarReal;
    };
    window.NURBAK_COTIZ_MODULOS.accio = iniciar;
})();
