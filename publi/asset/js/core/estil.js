/* ============================================================
   Ruta: publi/asset/js/core/estil.js
   Función: Coordinador único de carga dinámica CSS.

   RESPONSABILIDADES:
   - Resolver CSS por identificador técnico.
   - Mantener dependencias entre hojas.
   - Compartir cargas concurrentes y reutilizar hojas ya cargadas.
   - No cargar ni resolver JavaScript.
============================================================ */

(() => {
    const base = 'publi/asset/css/';

    const mapa = Object.freeze({
        'core/toast': { archivo: `${base}core/toast.css` },

        'catal/artic': { archivo: `${base}categ/catal/artic.css` },
        'catal/produ': { archivo: `${base}categ/catal/produ.css` },
        'catal/vehic': { archivo: `${base}categ/catal/vehic.css` },
        'catal/equiv': { archivo: `${base}categ/catal/equiv.css` },
        'catal/imext': { archivo: `${base}categ/catal/imext/imext.css` },

        'compr/prove': { archivo: `${base}categ/compr/prove.css` },
        'compr/comex': { archivo: `${base}categ/compr/comex.css` },
        'compr/orcex': { archivo: `${base}categ/compr/orcex.css` },
        'compr/recep': { archivo: `${base}categ/compr/recep.css` },

        'inven/almac': { archivo: `${base}categ/inven/almac.css` },
        'inven/stock': { archivo: `${base}categ/inven/stock.css` },
        'inven/movim': { archivo: `${base}categ/inven/movim.css` },
        'inven/ordco': { archivo: `${base}categ/inven/ordco.css` },

        'venta/clien': {
            archivo: `${base}categ/venta/clien.css`,
            antes: [`${base}categ/venta/clien/clien.css`]
        },
        'venta/cotiz': { archivo: `${base}categ/venta/cotiz.css` },
        'venta/opera': {
            archivo: `${base}categ/venta/opera.css`,
            antes: [
                `${base}categ/venta/opera/docu.css`,
                `${base}categ/venta/clien/clien.css`,
                `${base}categ/venta/opera/opera.css`,
                `${base}categ/venta/opera/prod.css`,
                `${base}categ/venta/opera/tabl.css`,
                `${base}categ/venta/opera/tota.css`,
                `${base}categ/venta/opera/pago.css`,
                `${base}categ/venta/opera/acci.css`
            ]
        },
        'venta/nvent': { archivo: `${base}categ/venta/nvent.css` },
        'venta/devol': { archivo: `${base}categ/venta/devol.css` },

        'factu/factu': { archivo: `${base}categ/factu/factu-base.css` },
        'factu/bolet': {
            archivo: `${base}categ/factu/bolet.css`,
            depende: ['factu/factu']
        },
        'factu/ncred': {
            archivo: `${base}categ/factu/ncred.css`,
            depende: ['factu/factu']
        },
        'factu/ndebi': {
            archivo: `${base}categ/factu/ndebi.css`,
            depende: ['factu/factu']
        },
        'factu/gremi': {
            archivo: `${base}categ/factu/gremi.css`,
            depende: ['factu/factu']
        },
        'factu/sunat': {
            archivo: `${base}categ/factu/sunat.css`,
            depende: ['factu/factu']
        },

        'pagos/regp': { archivo: `${base}categ/pagos/pagos.css` },
        'pagos/caja': { archivo: `${base}categ/pagos/caja.css` },
        'pagos/credi': { archivo: `${base}categ/pagos/credi.css` },

        'repor/infor': { archivo: `${base}categ/repor/infor.css` },
        'repor/audit': { archivo: `${base}categ/repor/audit.css` },

        'admi/permi': { archivo: `${base}admin/perm/perm.css` },
        'admi/usuar': { archivo: `${base}admin/usua/usua.css` },
        'admi/tiend': { archivo: `${base}admin/tiend/tiend.css` }
    });

    const pendientes = new Map();
    const cargados = new Set();

    const normalizar = ruta => new URL(ruta, document.baseURI).href;

    const yaCargado = ruta => {
        const url = normalizar(ruta);
        if (cargados.has(url)) return true;

        const hojas = document.querySelectorAll('link[rel="stylesheet"]');
        for (const hoja of hojas) {
            if (hoja.href === url || hoja.dataset.estilCss === url) {
                cargados.add(url);
                return true;
            }
        }
        return false;
    };

    const cargarArchivo = ruta => {
        const url = normalizar(ruta);
        if (pendientes.has(url)) return pendientes.get(url);
        if (yaCargado(ruta)) return Promise.resolve();

        const promesa = new Promise((resolver, rechazar) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = url;
            link.dataset.estilCss = url;
            link.addEventListener('load', () => {
                cargados.add(url);
                resolver();
            }, { once: true });
            link.addEventListener('error', () => {
                link.remove();
                rechazar(new Error(`No se pudo cargar CSS: ${ruta}`));
            }, { once: true });
            document.head.appendChild(link);
        });

        pendientes.set(url, promesa);
        promesa.catch(() => pendientes.delete(url));
        return promesa.finally(() => pendientes.delete(url));
    };

    const cargar = async (identificador, pila = new Set()) => {
        const clave = String(identificador || '');
        const recurso = mapa[clave];
        if (!recurso) throw new Error(`No hay CSS definido para ${clave}`);
        if (pila.has(clave)) throw new Error(`Dependencia CSS circular: ${clave}`);

        const nuevaPila = new Set(pila);
        nuevaPila.add(clave);

        for (const dependencia of recurso.depende || []) {
            await cargar(dependencia, nuevaPila);
        }
        for (const archivo of recurso.antes || []) {
            await cargarArchivo(archivo);
        }
        await cargarArchivo(recurso.archivo);
    };

    window.NURBAK_ESTIL = Object.freeze({
        listo: true,
        cargar,
        mapa
    });
})();
