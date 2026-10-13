/* ============================================================
   Ruta: publi/asset/js/categ/venta/clien/edita.js
   Módulo: Ventas / Cliente / Edición

   RESPONSABILIDAD:
   Mantener los datos visibles del cliente editables y
   sincronizados con el estad de Operaciones.
============================================================ */
(() => {
    const preparar = ctx => {
        const { q, state } = ctx;
        const docum = q('[data-opera-docum]');
        const razon = q('[data-opera-razon]');
        const direc = q('[data-opera-direc]');

        const sincronizar = () => {
            state.clien = {
                id: state.clienId || '',
                docum: docum?.value.trim() || '',
                razon: razon?.value.trim() || '',
                direc: direc?.value.trim() || ''
            };
        };

        [docum, razon, direc].forEach(input => {
            input?.addEventListener('input', () => {
                if (input === docum) {
                    const actual = docum.value.replace(/\D/g, '');
                    if (docum.value !== actual) docum.value = actual;
                    if (actual !== state.clien?.docum) state.clienId = '';
                }
                sincronizar();
                });
        });

        ctx.api.sincronizarCliente = sincronizar;
    };

    window.NURBAK_OPERA_CLIENTE = window.NURBAK_OPERA_CLIENTE || {};
    window.NURBAK_OPERA_CLIENTE.edit = preparar;
})();
