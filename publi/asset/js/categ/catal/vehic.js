/* ============================================================
   Ruta: publi/asset/js/categ/catal/vehic.js
   Módulo: Catálogo / Vehículos
   Función: Consulta automática + CRUD mediante MINI.
============================================================ */
(() => {
    const R = 'modul/categ/catal/vehic/vehic-contr.php';
    const L = 50;
    const M = 20;

    const esc = v => String(v ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const pedir = async (params, opt = {}) => {
        const x = await fetch(`${R}?${params.toString()}`, {
            method: opt.method || 'GET',
            body: opt.body || undefined,
            cache: 'no-store',
            headers: { Accept: 'application/json' }
        });
        const d = await x.json();

        if (!x.ok || !d.ok) {
            throw Error(d.mensaje || `HTTP ${x.status}`);
        }

        return d;
    };

    const auth = async p => window.NURBAK_LOGI_AUT?.autorizar
        ? (await window.NURBAK_LOGI_AUT.autorizar(p)).autorizado === true
        : false;

    const abrir = async ({ titulo, id = 0, recargar }) => {
        if (!(await auth('VEHIG'))) return;

        const mini = await window.NURBAK_MINI.abrir({
            titulo,
            rutaVista: `vista/categ/catal/vehic-mini.php?id=${id}`,
            modulo: 'vehiculo-mini',
            clase: 'catal-vehi-mini'
        });

        const form = mini?.querySelector('[data-vehi-mini-form]');
        if (!form) return;

        const msg = form.querySelector('[data-mini-msg]');
        const campoMarca = form.elements.marca;

        campoMarca?.addEventListener('input', () => {
            campoMarca.value = campoMarca.value.toUpperCase().slice(0, 2);
        });

        form.querySelector('[data-mini-cerrar]')?.addEventListener(
            'click',
            () => window.NURBAK_MINI.cerrar(mini)
        );

        if (id) {
            try {
                const d = await pedir(new URLSearchParams({
                    accion: 'obtener',
                    id: String(id)
                }));

                Object.entries(d.vehiculo).forEach(([k, v]) => {
                    const el = form.elements[k];
                    if (el) el.value = v ?? '';
                });
            } catch (e) {
                msg.textContent = e.message;
                return;
            }
        }

        form.addEventListener('submit', async e => {
            e.preventDefault();
            msg.textContent = 'Guardando...';

            try {
                const body = new URLSearchParams(new FormData(form));
                body.set('accion', 'guardar');
                if (id) body.set('id', String(id));

                const d = await pedir(new URLSearchParams(), {
                    method: 'POST',
                    body
                });

                msg.textContent = d.mensaje;
                window.NURBAK_CATE_SINC?.emitir({
                    modu: 'vehic',
                    recu: 'vehic',
                    acci: id ? 'actua' : 'crear',
                    id: Number(d.id || id || 0),
                    dato: d
                });

                await recargar();
                setTimeout(() => window.NURBAK_MINI.cerrar(mini), 250);
            } catch (e) {
                msg.textContent = e.message;
            }
        });
    };

    const iniciar = c => {
        const r = c.matches?.('[data-vehi]') ? c : c.querySelector?.('[data-vehi]');
        if (!r || r.dataset.iniciado === 'si') return;

        const b = r.querySelector('[data-vehi-busc]');
        const tb = r.querySelector('[data-vehi-cuer]');
        const msg = r.querySelector('[data-vehi-vaci]');
        const nuevo = r.querySelector('[data-vehi-nuevo]');

        if (!b || !tb || !msg || !nuevo) return;
        r.dataset.iniciado = 'si';

        const listar = async () => {
            msg.hidden = false;
            msg.textContent = 'Cargando vehículos...';
            tb.innerHTML = '';

            try {
                const d = await pedir(new URLSearchParams({
                    accion: 'listar',
                    buscar: b.value.trim(),
                    limite: String(L)
                }));

                const rows = d.vehiculos || [];
                tb.innerHTML = rows.slice(0, M).map(v => `
                    <tr>
                        <td>${esc(v.id)}</td>
                        <td>${esc(v.marca)}</td>
                        <td>${esc(v.motor)}</td>
                        <td>${esc(v.model)}</td>
                        <td>${esc(v.anio || '—')}</td>
                        <td>
                            <button type="button" data-editar="${esc(v.id)}">Editar</button>
                            <button type="button" data-eliminar="${esc(v.id)}">Eliminar</button>
                        </td>
                    </tr>
                `).join('');

                msg.textContent = b.value.trim() ? 'Sin coincidencias' : 'Sin registros';
                msg.hidden = rows.length > 0;
            } catch (e) {
                msg.textContent = 'No se pudieron cargar los vehículos.';
                console.error('Vehículos:', e);
            }
        };

        nuevo.addEventListener('click', () => abrir({
            titulo: 'Nuevo vehículo',
            recargar: listar
        }));

        tb.addEventListener('click', async e => {
            const ed = e.target.closest('[data-editar]');
            const del = e.target.closest('[data-eliminar]');

            if (ed) {
                abrir({
                    titulo: 'Editar vehículo',
                    id: ed.dataset.editar,
                    recargar: listar
                });
                return;
            }

            if (!del) return;
            if (!(await auth('VEHIG')) || !confirm('¿Eliminar este vehículo?')) return;

            try {
                await pedir(new URLSearchParams(), {
                    method: 'POST',
                    body: new URLSearchParams({
                        accion: 'eliminar',
                        id: del.dataset.eliminar
                    })
                });

                window.NURBAK_CATE_SINC?.emitir({
                    modu: 'vehic',
                    recu: 'vehic',
                    acci: 'elim',
                    id: Number(del.dataset.eliminar || 0)
                });

                await listar();
            } catch (x) {
                alert(x.message);
            }
        });

        /* SINCRONIZACIÓN | FASE 2 */
        window.NURBAK_CATE_SINC?.onCambio(
            () => listar().catch(() => {}),
            { recu: 'vehic' },
            c
        );
        window.NURBAK_CATE_SINC?.onCambio(
            () => listar().catch(() => {}),
            { recu: 'equiv' },
            c
        );

        let t;
        b.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(listar, 250);
        });

        listar();
    };

    document.addEventListener('modu:cargado', e => {
        if (e.detail?.modulo === 'vehic') iniciar(e.detail.modal);
    });

    document.querySelectorAll('[data-vehi]').forEach(iniciar);
})();
