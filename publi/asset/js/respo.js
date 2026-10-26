/* ============================================================
   Ruta: publi/asset/js/respo.js
   Función: Cargar y mantener al final del CSS la capa responsive.

   RESPONSABILIDAD:
   - Escuchar el evento existente modu:cargado.
   - Cargar las adaptaciones del recurso ya abierto.
   - Mantener el responsive después de los estilos base en la cascada.

   INDEPENDENCIA:
   - No cambia la lógica funcional.
   - No modifica core/estil.js ni sus rutas.
   - No bloquea la carga de módulos.
============================================================ */

(() => {
    const mapa = Object.freeze({
        'catal/artic': { movi: 'publi/asset/css/respo/movi/categ/catal/artic.css' },
        'catal/produ': { movi: 'publi/asset/css/respo/movi/categ/catal/produ.css' },
        'catal/vehic': { movi: 'publi/asset/css/respo/movi/categ/catal/vehic.css' },
        'catal/equiv': { movi: 'publi/asset/css/respo/movi/categ/catal/equiv.css' },
        'catal/imext': { movi: 'publi/asset/css/respo/movi/categ/catal/imext/imext.css' },
        'compr/orcex': { movi: 'publi/asset/css/respo/movi/categ/compr/orcex.css' },
        'inven/almac': { movi: 'publi/asset/css/respo/movi/categ/inven/almac.css' },
        'inven/stock': { movi: 'publi/asset/css/respo/movi/categ/inven/stock.css' },
        'inven/movim': { movi: 'publi/asset/css/respo/movi/categ/inven/movim.css' },
        'inven/ordco': { movi: 'publi/asset/css/respo/movi/categ/inven/ordco.css' },
        'venta/cotiz': { tabl: 'publi/asset/css/respo/tabl/categ/venta/cotiz.css', movi: 'publi/asset/css/respo/movi/categ/venta/cotiz.css' },
        'venta/opera': { movi: 'publi/asset/css/respo/movi/categ/venta/opera.css' },
        'venta/nvent': { tabl: 'publi/asset/css/respo/tabl/categ/venta/nvent.css', movi: 'publi/asset/css/respo/movi/categ/venta/nvent.css' },
        'repor/audit': { movi: 'publi/asset/css/respo/movi/categ/repor/audit.css' },
        'admi/permi': { movi: 'publi/asset/css/respo/movi/admin/perm/perm.css' },
        'admi/tiend': { tabl: 'publi/asset/css/respo/tabl/admin/tiend/tiend.css' }
    });

    const pendientes = new Map();
    const cargados = new Set();

    const reordenar = () => {
        const hojas = [...document.querySelectorAll('link[data-respo-css]')];
        hojas.forEach(hoja => document.head.appendChild(hoja));
    };

    const cargar = ruta => {
        if (cargados.has(ruta)) return Promise.resolve();
        if (pendientes.has(ruta)) return pendientes.get(ruta);

        const existente = [...document.querySelectorAll('link[data-respo-css]')]
            .find(hoja => hoja.dataset.respoCss === ruta);
        if (existente) {
            cargados.add(ruta);
            return Promise.resolve();
        }

        const promesa = new Promise((resolver, rechazar) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = ruta;
            link.dataset.respoCss = ruta;
            link.addEventListener('load', () => {
                cargados.add(ruta);
                resolver();
            }, { once: true });
            link.addEventListener('error', () => {
                link.remove();
                rechazar(new Error(`No se pudo cargar responsive: ${ruta}`));
            }, { once: true });
            document.head.appendChild(link);
        });

        pendientes.set(ruta, promesa);
        promesa.catch(() => pendientes.delete(ruta));
        return promesa.finally(() => pendientes.delete(ruta));
    };

    document.addEventListener('modu:cargado', evento => {
        const recurso = String(evento.detail?.recu || '');
        const rutas = mapa[recurso];
        if (!rutas) {
            reordenar();
            return;
        }

        // El orden es intencional: adaptaciones de tablet primero y de móvil después.
        // Carga secuencial determinista: tablet primero y móvil después.
        // Si se cargan en paralelo, la hoja que termina última puede ganar
        // la cascada independientemente del orden declarado en el mapa.
        (async () => {
            const archivos = [rutas.tabl, rutas.movi].filter(Boolean);
            for (const ruta of archivos) {
                try {
                    await cargar(ruta);
                } catch (error) {
                    console.error('RESPONSIVE:', error);
                }
            }
            reordenar();
        })();
    });
})();
