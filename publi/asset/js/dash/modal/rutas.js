/* ============================================================
   Ruta: publi/asset/js/dash/modal/rutas.js
   Función: Mantener las rutas de navegación por MÓDULO → SUBMÓDULO.

   La capa visual conserva los nombres del navbar.
   Cada submódulo enlaza aquí con:
   - vista PHP
   - recurso técnico MÓDULO/SUBMÓDULO
============================================================ */

window.NURBAK_MODAL_RUTAS = (() => {
    const definir = () => ({
        catalogo: {
            articulos: { vista: 'vista/categ/catal/artic.php', recu: 'catal/artic' },
            produ: { vista: 'vista/categ/catal/produ.php', recu: 'catal/produ' },
            vehic: { vista: 'vista/categ/catal/vehic.php', recu: 'catal/vehic' },
            equiv: { vista: 'vista/categ/catal/equiv.php', recu: 'catal/equiv' },
            imext: { vista: 'vista/categ/catal/imext/imext.php', recu: 'catal/imext' },
        },
        inventario: {
            almacenes: { vista: 'vista/categ/inven/almac.php', recu: 'inven/almac' },
            stock: { vista: 'vista/categ/inven/stock.php', recu: 'inven/stock' },
            movimientos: { vista: 'vista/categ/inven/movim.php', recu: 'inven/movim' },
            ordco: { vista: 'vista/categ/inven/ordco.php', recu: 'inven/ordco' },
        },
        compras: {
            proveedores: { vista: 'vista/categ/compr/prove.php', recu: 'compr/prove' },
            'orden-compra-ext': { vista: 'vista/categ/compr/orcex.php', recu: 'compr/orcex' },
            'compra-externa': { vista: 'vista/categ/compr/comex.php', recu: 'compr/comex' },
            recepcion: { vista: 'vista/categ/compr/recep.php', recu: 'compr/recep' },
        },
        ventas: {
            clientes: { vista: 'vista/categ/venta/clien.php', recu: 'venta/clien' },
            cotiz: { vista: 'vista/categ/venta/cotiz.php', recu: 'venta/cotiz' },
            operaciones: { vista: 'vista/categ/venta/opera.php', recu: 'venta/opera' },
            'notas-venta': { vista: 'vista/categ/venta/nvent.php', recu: 'venta/nvent' },
            devoluciones: { vista: 'vista/categ/venta/devol.php', recu: 'venta/devol' },
        },
        facturacion: {
            facturas: { vista: 'vista/categ/factu/factu.php', recu: 'factu/factu' },
            boletas: { vista: 'vista/categ/factu/bolet.php', recu: 'factu/bolet' },
            'notas-credito': { vista: 'vista/categ/factu/ncred.php', recu: 'factu/ncred' },
            'notas-debito': { vista: 'vista/categ/factu/ndebi.php', recu: 'factu/ndebi' },
            'guias-remision': { vista: 'vista/categ/factu/gremi.php', recu: 'factu/gremi' },
            sunat: { vista: 'vista/categ/factu/sunat.php', recu: 'factu/sunat' },
        },
        pagos: {
            'registro-pagos': { vista: 'vista/categ/pagos/pagos.php', recu: 'pagos/regp' },
            caja: { vista: 'vista/categ/pagos/caja.php', recu: 'pagos/caja' },
            creditos: { vista: 'vista/categ/pagos/credi.php', recu: 'pagos/credi' },
        },
        reportes: {
            informes: { vista: 'vista/categ/repor/infor.php', recu: 'repor/infor' },
            auditoria: { vista: 'vista/categ/repor/audit.php', recu: 'repor/audit' },
        },
        admi: {
            'admi-perm': { vista: 'vista/admin/permi/permi.php', recu: 'admi/permi' },
            'admi-usua': { vista: 'vista/admin/usuar/usuar.php', recu: 'admi/usuar' },
            'admi-tiend': { vista: 'vista/admin/tiend/tiend.php', recu: 'admi/tiend' },
        },
    });

    const obtener = () => definir();

    const resolverRecurso = recu => {
        const buscado = String(recu || '');
        const rutas = obtener();

        for (const submodulos of Object.values(rutas)) {
            for (const ruta of Object.values(submodulos)) {
                if (ruta.recu === buscado) return ruta;
            }
        }

        return null;
    };

    return { obtener, resolverRecurso };
})();
