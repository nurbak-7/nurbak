/* ============================================================
   Ruta: publi/asset/js/categ/venta/clien/exter.js
   Módulo: Ventas / Cliente / Externo

   RESPONSABILIDAD:
   Consultar la fuente externa de RUC/DNI.
============================================================ */
(() => {
    const buscar = async (ctx, docum) => {
        const body = new URLSearchParams({
            accion: 'buscar-cliente-externo',
            docum
        });

        const respuesta = await fetch(ctx.R, {
            method: 'POST',
            body,
            cache: 'no-store'
        });

        const datos = await respuesta.json();

        if (!respuesta.ok) {
            throw new Error(datos.mensaje || 'No se pudo consultar la fuente externa.');
        }

        if (datos.ok && datos.clien) {
            const clien = datos.clien;

            return {
                ok: true,
                encontrado: true,
                disponible: datos.disponible !== false,
                clien: {
                    id: clien.id || '',
                    docum: clien.documento || '',
                    razon: clien.razon_social || '',
                    direc: clien.direccion || ''
                }
            };
        }

        return datos;
    };

    window.NURBAK_OPERA_CLIENTE = window.NURBAK_OPERA_CLIENTE || {};
    window.NURBAK_OPERA_CLIENTE.externo = buscar;
})();
