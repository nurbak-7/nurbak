/* ============================================================
   Ruta: publi/asset/js/categ/venta/cotiz/clien.js
   Módulo: Ventas / Cotización / Cliente

   RESPONSABILIDAD:
   RUC/DNI → local → fuente externa.
   Reutiliza exclusivamente el servicio externo existente.
============================================================ */
(() => {
    const iniciar = ctx => {
        const docum = ctx.q('[data-cotiz-docum]');
        if (!docum) return;

        let timer = null;
        let solicitud = 0;
        let ultima = '';

        const completo = doc => doc.length === 8 || doc.length === 11;

        const buscar = async () => {
            const doc = docum.value.replace(/\D/g,'');
            if (!completo(doc) || doc === ultima) return;
            const id = ++solicitud;
            ultima = doc;
            ctx.state.clienId = 0;

            try {
                const local = await ctx.pedir({accion:'buscar-cliente-local',docum:doc});
                if (id !== solicitud || docum.value.replace(/\D/g,'') !== doc) return;

                if (local.clien) {
                    ctx.api.mostrarCliente(local.clien);
                    return;
                }

                const externo = await ctx.pedir({accion:'buscar-cliente-externo',docum:doc});
                if (id !== solicitud || docum.value.replace(/\D/g,'') !== doc) return;

                if (externo.ok && externo.clien) {
                    ctx.api.mostrarCliente({
                        docum: doc,
                        razon: externo.clien.razon_social || externo.clien.razon || '',
                        direc: externo.clien.direccion || externo.clien.direc || ''
                    });
                } else {
                    throw new Error(externo.mensaje || 'Cliente no encontrado.');
                }
            } catch (error) {
                if (id !== solicitud) return;
                ultima = '';
                ctx.api.toas?.resu?.(error.message, 'err');
            }
        };

        docum.addEventListener('input', () => {
            clearTimeout(timer);
            solicitud++;
            ultima = '';
            ctx.state.clienId = 0;
            const doc = docum.value.replace(/\D/g,'');
            if (!completo(doc)) return;
            timer = setTimeout(() => void buscar(), 550);
        });

        docum.addEventListener('blur', () => {
            clearTimeout(timer);
            void buscar();
        });

        ctx.registrarLimpieza(() => clearTimeout(timer));
    };

    window.NURBAK_COTIZ_MODULOS.clien = iniciar;
})();
