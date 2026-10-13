/* ============================================================
   Ruta: publi/asset/js/categ/catal/produ/produ-mini.js
   Módulo: Catálogo / Productos / MINI

   FUNCIÓN:
   Comportamiento exclusivo del formulario MINI de Producto.

   RESPONSABILIDAD:
   - Abrir MINI.
   - Cargar datos para edición.
   - Guardar alta/edición.
   - Cerrar MINI.

   La vista normal y su tabla pertenecen a produ-modul.js.
============================================================ */
(() => {
    'use strict';

    const key = '__NURBAK_PRODU_MINI';
    if (window[key]) return;
    window[key] = true;

    const R = 'modul/categ/catal/produ/produ-contr.php';
    const ruta = 'vista/categ/catal/produ/produ-mini.php';

    const pedir = async (par, opt = {}) => {
        const res = await fetch(`${R}?${par.toString()}`, {
            method: opt.method || 'GET',
            body: opt.body || undefined,
            cache: 'no-store',
            credentials: 'same-origin',
            headers: {Accept: 'application/json'}
        });
        const dat = await res.json();

        if (!res.ok || !dat.ok) {
            throw new Error(dat.mensaje || `HTTP ${res.status}`);
        }

        return dat;
    };

    const auth = async per => {
        if (!window.NURBAK_LOGI_AUT?.autorizar) return false;
        const res = await window.NURBAK_LOGI_AUT.autorizar(per);
        return res.autorizado === true;
    };

    const abrir = async ({id = 0, rec, titulo}) => {
        if (!(await auth('PRODG'))) return;

        const mini = await window.NURBAK_MINI.abrir({
            titulo,
            rutaVista: `${ruta}?id=${Number(id) || 0}`,
            modulo: 'producto-mini',
            clase: 'catal-prod-mini'
        });

        const form = mini?.querySelector('[data-prod-mini-form]');
        if (!form) return;

        const msg = form.querySelector('[data-mini-msg]');
        const cerrar = () => window.NURBAK_MINI.cerrar(mini);
        const btn = form.querySelector('[data-mini-cerrar]');

        btn?.addEventListener('click', cerrar);

        if (id) {
            try {
                const dat = await pedir(new URLSearchParams({
                    accion: 'obtener',
                    id: String(id)
                }));

                Object.entries(dat.producto || {}).forEach(([nom, val]) => {
                    const ele = form.elements[nom];
                    if (ele) ele.value = val ?? '';
                });
            } catch (err) {
                msg.textContent = err.message;
                return;
            }
        }

        form.addEventListener('submit', async evento => {
            evento.preventDefault();
            msg.textContent = 'Guardando...';

            try {
                const body = new URLSearchParams(new FormData(form));
                body.set('accion', 'guardar');
                if (id) body.set('id', String(id));

                const dat = await pedir(new URLSearchParams(), {
                    method: 'POST',
                    body
                });

                msg.textContent = dat.mensaje;

                // SINCRONIZACIÓN | FASE 2
                window.NURBAK_CATE_SINC?.emitir({
                    modu: 'produ',
                    recu: 'produ',
                    acci: id ? 'actua' : 'crear',
                    id: Number(dat.id || id || 0),
                    dato: dat
                });

                await rec();
                setTimeout(cerrar, 250);
            } catch (err) {
                msg.textContent = err.message;
            }
        });
    };

    document.addEventListener('produ:abrir-mini', evento => {
        abrir(evento.detail || {}).catch(err => {
});
    });
})();
