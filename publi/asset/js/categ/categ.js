/* ============================================================
   Ruta: publi/asset/js/categ/categ.js
   Función: Entregar las rutas reales de JavaScript de CATEG.
   CSS pertenece exclusivamente a core/estil.js.
============================================================ */

(() => {
    const mapa = {
        'catal/artic': 'publi/asset/js/categ/catal/artic.js',
        'catal/produ': 'publi/asset/js/categ/catal/produ.js',
        'catal/vehic': 'publi/asset/js/categ/catal/vehic.js',
        'catal/equiv': 'publi/asset/js/categ/catal/equiv.js',
        'catal/imext': 'publi/asset/js/categ/catal/imext/imext.js',
        'compr/prove': 'publi/asset/js/categ/compr/prove.js',
        'compr/comex': 'publi/asset/js/categ/compr/comex.js',
        'compr/orcex': 'publi/asset/js/categ/compr/orcex.js',
        'compr/recep': 'publi/asset/js/categ/compr/recep.js',
        'inven/almac': 'publi/asset/js/categ/inven/almac.js',
        'inven/stock': 'publi/asset/js/categ/inven/stock.js',
        'inven/movim': 'publi/asset/js/categ/inven/movim.js',
        'inven/ordco': 'publi/asset/js/categ/inven/ordco.js',
        'venta/clien': 'publi/asset/js/categ/venta/clien.js',
        'venta/cotiz': 'publi/asset/js/categ/venta/cotiz.js',
        'venta/opera': 'publi/asset/js/categ/venta/opera.js',
        'venta/nvent': 'publi/asset/js/categ/venta/nvent.js',
        'venta/devol': 'publi/asset/js/categ/venta/devol.js',
        'factu/factu': 'publi/asset/js/categ/factu/factu.js',
        'factu/bolet': 'publi/asset/js/categ/factu/bolet.js',
        'factu/ncred': 'publi/asset/js/categ/factu/ncred.js',
        'factu/ndebi': 'publi/asset/js/categ/factu/ndebi.js',
        'factu/gremi': 'publi/asset/js/categ/factu/gremi.js',
        'factu/sunat': 'publi/asset/js/categ/factu/sunat.js',
        'pagos/regp': 'publi/asset/js/categ/pagos/pagos.js',
        'pagos/caja': 'publi/asset/js/categ/pagos/caja.js',
        'pagos/credi': 'publi/asset/js/categ/pagos/credi.js',
        'repor/infor': 'publi/asset/js/categ/repor/infor.js',
        'repor/audit': 'publi/asset/js/categ/repor/audit.js',
    };

    window.NURBAK_CATE = Object.freeze({
        listo: true,
        rutaJs: ruta => mapa[ruta] || ''
    });
})();
