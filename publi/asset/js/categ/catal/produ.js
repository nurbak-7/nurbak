/* ============================================================
   Ruta: publi/asset/js/categ/catal/produ.js
   Módulo: Catálogo / Productos

   FUNCIÓN:
   Coordinador JS de Productos.

   RESPONSABILIDAD:
   Cargar una sola vez los dos submódulos:
   - produ/produ-modul.js → vista principal.
   - produ/produ-mini.js  → MINI de Producto.

   No contiene CRUD ni lógica propia de la vista.
============================================================ */
(() => {
    'use strict';

    const key = '__NURBAK_PRODU_COORD';
    if (window[key]) return;
    window[key] = true;

    const arch = [
        'produ-modul.js',
        'produ-mini.js'
    ];

    /* INTEGRACIÓN | FASE 3: compartir cargas pendientes. */
    const pendientes = new Map();

    const carga = archivo => {
        if (pendientes.has(archivo)) {
            return pendientes.get(archivo);
        }

        const tarea = new Promise((resolve, reject) => {
        const sel = `[data-produ-js="${archivo}"]`;
        const ya = document.querySelector(sel);
        const nom = archivo.replace('.js', '');

        if (ya) {
            if (ya.dataset.produEst === 'cargado') {
                resolve();
                return;
            }

            if (ya.dataset.produEst === 'cargando') {
                ya.addEventListener('load', () => resolve(), {once:true});
                ya.addEventListener('error', () => reject(new Error(`No se pudo cargar ${archivo}.`)), {once:true});
                return;
            }

            ya.remove();
        }

        const scr = document.createElement('script');
        scr.src = `publi/asset/js/categ/catal/produ/${archivo}`;
        scr.dataset.produJs = archivo;
        scr.dataset.produEst = 'cargando';

        scr.addEventListener('load', () => {
            scr.dataset.produEst = 'cargado';
            resolve(nom);
        }, {once:true});

        scr.addEventListener('error', () => {
            scr.remove();
            reject(new Error(`No se pudo cargar ${archivo}.`));
        }, {once:true});

        document.body.appendChild(scr);
        });

        pendientes.set(archivo, tarea);
        return tarea.finally(() => pendientes.delete(archivo));
    };

    const cargar = async ven => {
        const root = ven?.querySelector('[data-prod]') || ven;
        if (!root?.matches?.('[data-prod]')) return;

        if (ven.dataset.produCargado === 'si') return;
        if (ven.dataset.produCarga === 'si') return;

        ven.dataset.produCarga = 'si';

        try {
            await Promise.all(arch.map(carga));
            document.dispatchEvent(new CustomEvent('produ:ini', {
                detail: {root, ventana: ven}
            }));
            ven.dataset.produCargado = 'si';
        } catch (err) {
            ven.dataset.produCarga = '';
throw err;
        }
    };

    document.addEventListener('modu:cargado', evento => {
        if (evento.detail?.modulo !== 'produ') return;
        cargar(evento.detail.modal).catch(() => {});
    });

    document.querySelectorAll('[data-prod]').forEach(root => {
        const ven = root.closest('[data-modal-ventana], [data-mini-ventana]') || root;
        cargar(ven).catch(() => {});
    });
})();
