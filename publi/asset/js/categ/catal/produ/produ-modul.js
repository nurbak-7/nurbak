/* ============================================================
   Ruta: publi/asset/js/categ/catal/produ/produ-modul.js
   Módulo: Catálogo / Productos

   FUNCIÓN:
   Comportamiento de la ventana principal de Productos.

   RESPONSABILIDAD:
   - Buscar/listar productos.
   - Dibujar la tabla.
   - Solicitar Nueva/Editar a la MINI mediante evento.
   - Eliminar productos.

   No contiene la lógica interna del formulario MINI.
============================================================ */
(() => {
    'use strict';

    const key = '__NURBAK_PRODU_MODUL';
    if (window[key]) return;
    window[key] = true;

    const R = 'modul/categ/catal/produ/produ-contr.php';
    const LIM = 50;
    const MAX = 20;

    const esc = val => String(val ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

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

    const abrir = ({id = 0, rec}) => {
        document.dispatchEvent(new CustomEvent('produ:abrir-mini', {
            detail: {
                id: Number(id) || 0,
                rec,
                titulo: Number(id) > 0 ? 'Editar producto' : 'Nuevo producto'
            }
        }));
    };

    const iniciar = root => {
        if (!root || root.dataset.produMod === 'si') return;

        const busc = root.querySelector('[data-prod-busc]');
        const cuer = root.querySelector('[data-prod-cuer]');
        const vaci = root.querySelector('[data-prod-vaci]');
        const nuevo = root.querySelector('[data-prod-nuevo]');

        if (!busc || !cuer || !vaci || !nuevo) return;

        root.dataset.produMod = 'si';

        const listar = async () => {
            vaci.hidden = false;
            vaci.textContent = 'Cargando productos...';
            cuer.innerHTML = '';

            try {
                const dat = await pedir(new URLSearchParams({
                    accion: 'listar',
                    buscar: busc.value.trim(),
                    limite: String(LIM)
                }));

                const rows = dat.productos || [];

                cuer.innerHTML = rows.slice(0, MAX).map(p => `
                    <tr>
                        <td>${esc(p.id)}</td>
                        <td>${esc(p.descr)}</td>
                        <td>${esc(p.codig || '—')}</td>
                        <td>${esc(p.origi || '—')}</td>
                        <td>${esc(p.medid || '—')}</td>
                        <td>${esc(p.super ?? '0')}</td>
                        <td>${esc(p.equiv || '—')}</td>
                        <td>${Number(p.pcomp || 0).toFixed(2)}</td>
                        <td>${Number(p.pvent || 0).toFixed(2)}</td>
                        <td>
                            <button type="button" data-editar="${esc(p.id)}">Editar</button>
                            <button type="button" data-eliminar="${esc(p.id)}">Eliminar</button>
                        </td>
                    </tr>
                `).join('');

                vaci.textContent = busc.value.trim() ? 'Sin coincidencias' : 'Sin registros';
                vaci.hidden = rows.length > 0;
            } catch (err) {
                vaci.textContent = 'No se pudieron cargar los productos.';
                console.error('Productos:', err);
            }
        };

        nuevo.addEventListener('click', () => {
            abrir({id: 0, rec: listar});
        });

        cuer.addEventListener('click', async evento => {
            const edi = evento.target.closest('[data-editar]');
            const eli = evento.target.closest('[data-eliminar]');

            if (edi) {
                abrir({id: edi.dataset.editar, rec: listar});
                return;
            }

            if (!eli) return;
            if (!(await auth('PRODG'))) return;
            if (!confirm('¿Eliminar este producto?')) return;

            try {
                await pedir(new URLSearchParams(), {
                    method: 'POST',
                    body: new URLSearchParams({
                        accion: 'eliminar',
                        id: eli.dataset.eliminar
                    })
                });

                // SINCRONIZACIÓN | FASE 2
                window.NURBAK_CATE_SINC?.emitir({
                    modu: 'produ',
                    recu: 'produ',
                    acci: 'elim',
                    id: Number(eli.dataset.eliminar || 0)
                });

                await listar();
            } catch (err) {
                alert(err.message);
            }
        });

        // SINCRONIZACIÓN | FASE 2
        window.NURBAK_CATE_SINC?.onCambio(() => listar().catch(() => {}), {recu:'produ'}, root);
        window.NURBAK_CATE_SINC?.onCambio(() => listar().catch(() => {}), {recu:'equiv'}, root);

        let tim;
        busc.addEventListener('input', () => {
            clearTimeout(tim);
            tim = setTimeout(listar, 250);
        });

        listar();
    };

    document.addEventListener('produ:ini', evento => {
        iniciar(evento.detail?.root);
    });
})();
