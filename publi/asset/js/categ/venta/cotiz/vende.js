/* ============================================================
   Ruta: publi/asset/js/categ/venta/cotiz/vende.js
   Módulo: Ventas / Cotización / Vendedor

   RESPONSABILIDAD:
   Consultar el vendedor por código y reemplazar el código
   por el usuario resuelto en el mismo campo.
============================================================ */
(() => {
    const iniciar = ctx => {
        const campo = ctx.q('[data-cotiz-vende-cod]');
        if (!campo) return;
        let timer = null;
        let procesando = '';
        let ultimo = '';
        const codigoValido = codig => /^\d{3}$/.test(codig) || /^A\d{3}$/.test(codig);
        const limpiar = () => {
            ctx.state.vendeId = 0;
            ctx.q('[data-cotiz-vende]').value = '0';
            campo.value = '';
            campo.dataset.codig = '';
        };
        const buscar = async valor => {
            const codig = String(valor || '').trim().toUpperCase();
            if (!codigoValido(codig) || procesando === codig || ultimo === codig) return;
            procesando = codig;
            try {
                const datos = await ctx.pedir({accion:'buscar-vendedor',codig});
                ultimo = codig;
                if (!datos.encontrado || !datos.vendedor) {
                    throw new Error('Vendedor no encontrado o inactivo.');
                }
                ctx.api.mostrarVendedor(datos.vendedor);
            } catch (error) {
                ultimo = '';
                limpiar();
                ctx.api.toas?.resu?.(error.message,'err');
            } finally {
                procesando = '';
            }
        };
        campo.addEventListener('input', () => {
            const valor = campo.value.toUpperCase().replace(/\s/g,'');
            campo.value = valor;
            campo.dataset.codig = '';
            ctx.state.vendeId = 0;
            ctx.q('[data-cotiz-vende]').value = '0';
            ultimo = '';
            clearTimeout(timer);
            if (!codigoValido(valor)) return;
            timer = setTimeout(() => void buscar(valor), 250);
        });
        campo.addEventListener('change', () => { clearTimeout(timer); void buscar(campo.value); });
        const placa = ctx.q('[data-cotiz-placa]');
        const formPlaca = valor => {
            const base = String(valor || '')
                .toUpperCase()
                .replace(/[^A-Z0-9]/g, '')
                .slice(0, 6);
            return base.length > 3
                ? `${base.slice(0,3)}-${base.slice(3)}`
                : base;
        };

        placa?.addEventListener('input', () => {
            placa.value = formPlaca(placa.value);
        });

        ctx.api.placa = () => formPlaca(placa?.value || '');
        ctx.api.validarPlaca = () => {
            const valor = ctx.api.placa();
            if (valor !== '' && !/^[A-Z0-9]{3}-[A-Z0-9]{3}$/.test(valor)) {
                return {ok:false,mensaje:'La placa debe tener el formato SSS-SSS.'};
            }
            return {ok:true,valor};
        };

        placa?.addEventListener('change', () => {
            placa.value = formPlaca(placa.value);
        });

        ctx.registrarLimpieza(() => clearTimeout(timer));
    };
    window.NURBAK_COTIZ_MODULOS.vende = iniciar;
})();
