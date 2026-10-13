/* ============================================================
   Ruta: publi/asset/js/categ/venta/clien/local.js
   Módulo: Ventas / Cliente / Local

   RESPONSABILIDAD:
   Buscar el cliente por RUC/DNI en venta.clien.
============================================================ */
(() => {
    const buscar = async (ctx, docum) => {
        const body = new URLSearchParams({
            accion: 'buscar-cliente-local',
            docum
        });

        const respuesta = await fetch(ctx.R, {
            method: 'POST',
            body,
            cache: 'no-store'
        });

        const datos = await respuesta.json();

        if (!respuesta.ok || !datos.ok) {
            throw new Error(datos.mensaje || 'No se pudo consultar el cliente local.');
        }

        if (!datos.clien) return null;

        const clien = datos.clien;

        return {
            id: clien.id || '',
            docum: clien.docum || '',
            razon: clien.razon || '',
            direc: clien.direc || ''
        };
    };

    window.NURBAK_OPERA_CLIENTE = window.NURBAK_OPERA_CLIENTE || {};
    window.NURBAK_OPERA_CLIENTE.local = buscar;
})();
