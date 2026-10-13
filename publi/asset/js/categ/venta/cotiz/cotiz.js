/* ============================================================
   Ruta: publi/asset/js/categ/venta/cotiz/cotiz/cotiz.js
   Módulo: Ventas / Cotización / Estado

   RESPONSABILIDAD:
   Componer los datos que necesita Cotización sin asumir
   responsabilidades de Cliente, Vendedor o Productos.
============================================================ */
(() => {
    const iniciar = ctx => {
        const {q,state} = ctx;
        const docum = q('[data-cotiz-docum]');
        const razon = q('[data-cotiz-razon]');
        const direc = q('[data-cotiz-direc]');
        const vende = q('[data-cotiz-vende-cod]');
        const placa = q('[data-cotiz-placa]');

        ctx.api.sincronizarCliente = () => {
            state.clien = {
                id: state.clienId || 0,
                docum: String(docum?.value || '').replace(/\D/g,''),
                razon: String(razon?.value || '').trim(),
                direc: String(direc?.value || '').trim()
            };
        };

        ctx.api.form = () => ({
            id: state.cotizId,
            clien: state.clienId,
            vende: state.vendeId,
            docum: String(docum?.value || '').replace(/\D/g,''),
            razon: String(razon?.value || '').trim(),
            direc: String(direc?.value || '').trim(),
            placa: ctx.api.placa?.() || String(placa?.value || '').trim().toUpperCase(),
            fecha: String(q('[data-cotiz-fecha]')?.value || ''),
            vigia: String(q('[data-cotiz-vigia]')?.value || ''),
            obser: '',
            detalles: JSON.stringify(state.detalles)
        });

        ctx.api.validar = () => {
            const d = String(docum?.value || '').replace(/\D/g,'');
            if (![8,11].includes(d.length)) return {ok:false,mensaje:'Debe indicar un RUC/DNI válido.'};
            if (String(razon?.value || '').trim() === '') return {ok:false,mensaje:'No se pudo identificar al cliente.'};
            if (state.vendeId <= 0) return {ok:false,mensaje:'Debe indicar el vendedor.'};
            const pla = ctx.api.validarPlaca?.() || {ok:true,valor:String(placa?.value || '').trim().toUpperCase()};
            if (!pla.ok) return pla;
            const fecha = String(q('[data-cotiz-fecha]')?.value || '');
            const vigia = String(q('[data-cotiz-vigia]')?.value || '');
            if (!fecha) return {ok:false,mensaje:'Debe indicar la fecha.'};
            if (!vigia) return {ok:false,mensaje:'Debe indicar la vigencia.'};
            const base = new Date(`${fecha}T00:00:00`);
            const fin = new Date(base);
            fin.setDate(fin.getDate() + 3);
            const esperada = `${fin.getFullYear()}-${String(fin.getMonth()+1).padStart(2,'0')}-${String(fin.getDate()).padStart(2,'0')}`;
            if (vigia !== esperada) return {ok:false,mensaje:'La vigencia de la cotización debe ser de 3 días.'};
            if (!state.detalles.length) return {ok:false,mensaje:'La cotización debe contener productos.'};
            for (const [i,item] of state.detalles.entries()) {
                const canti = Number(item.canti || 0);
                const preun = Number(item.preun || 0);
                if (Number(item.produ || 0) <= 0 || canti <= 0 || !Number.isFinite(preun) || preun < 0) return {ok:false,mensaje:`Revise el producto ${i + 1}.`};
            }
            return {ok:true};
        };

        ctx.api.mostrarCliente = datos => {
            if (!datos) return;
            docum.value = datos.docum || docum.value || '';
            razon.value = datos.razon || datos.razon_social || razon.value || '';
            direc.value = datos.direc || datos.direccion || direc.value || '';
            state.clienId = Number(datos.id || 0);
            ctx.api.sincronizarCliente();
        };

        ctx.api.mostrarVendedor = datos => {
            state.vendeId = Number(datos?.id || 0);
            q('[data-cotiz-vende]').value = String(state.vendeId);
            if (vende) {
                vende.value = datos?.display || '';
                vende.dataset.codig = datos?.codig || '';
            }
        };

        [docum, razon, direc].forEach(el => el?.addEventListener('input', ctx.api.sincronizarCliente));
        placa?.addEventListener('input', () => { placa.value = placa.value.toUpperCase(); });
    };
    window.NURBAK_COTIZ_MODULOS.cotiz = iniciar;
})();
