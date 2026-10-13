/* ============================================================
   Ruta: publi/asset/js/categ/compr/comex.js
   Módulo: Compras / Compra externa
============================================================ */

(() => {

    const R = 'modul/categ/compr/comex/comex-contr.php';

    const esc = v => String(v ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const auth = async permiso => {
        if (!window.NURBAK_LOGI_AUT?.autorizar) {
            return false;
        }

        const res =
            await window.NURBAK_LOGI_AUT.autorizar(
                permiso
            );

        return res.autorizado === true;
    };

    const ini = c => {

        const r = c.matches?.('[data-comx]')
            ? c
            : c.querySelector?.('[data-comx]');

        if (
            !r ||
            r.dataset.listo === 'si'
        ) {
            return;
        }

        r.dataset.listo = 'si';

        const q = s => r.querySelector(s);

        const b = q('[data-comx-busc]');
        const tb = q('[data-comx-cuer]');
        const ms = q('[data-comx-mens]');
        const nuevo = q('[data-comx-nuev]');

        const pedir = async (accion, datos = {}, opciones = {}) => {

            const p = new URLSearchParams({
                acci: accion,
                ...datos
            });

            const res = await fetch(
                `${R}?${p.toString()}`,
                {
                    method: opciones.method || 'GET',
                    body: opciones.body || undefined,
                    cache: 'no-store',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json'
                    }
                }
            );

            const dat = await res.json();

            if (!res.ok || !dat.ok) {
                throw Error(
                    dat.mensaje ||
                    `HTTP ${res.status}`
                );
            }

            return dat;
        };

        const listar = async () => {
            const termino = b.value.trim();
            ms.hidden = false;
            ms.textContent = termino ? 'Buscando compras...' : 'Cargando compras...';
            tb.innerHTML = '';

            try {
                const datos = await pedir(
                    'lista',
                    {
                        busc: termino,
                        limi: 50
                    }
                );

                const filas = datos.comex || [];

                tb.innerHTML = filas
                    .map(x => `
                        <tr>
                            <td>${esc(x.id)}</td>
                            <td>${esc(x.razon)}</td>
                            <td>${esc(x.estad)}</td>
                            <td>${esc(x.fecha)}</td>
                            <td>${Number(x.total || 0).toFixed(2)}</td>
                            <td>${esc(x.obser || '—')}</td>
                        </tr>
                    `)
                    .join('');

                ms.textContent = termino ? 'Sin coincidencias' : 'Sin registros';
                ms.hidden = filas.length > 0;
            } catch (error) {
                ms.hidden = false;
                ms.textContent = `No se pudieron cargar las compras: ${error instanceof Error ? error.message : String(error)}`;
                console.error('COMEX — listado de compras:', error);
            }
        };

        const abrir = async () => {

            if (!(await auth('COMEG'))) {
                return;
            }

            if (!window.NURBAK_MINI?.abrir) {
                throw new Error(
                    'MINI no está disponible.'
                );
            }

            const mini =
                await window.NURBAK_MINI.abrir({
                    titulo: 'Nueva compra externa',
                    rutaVista:
                        'vista/categ/compr/comex-mini.php',
                    modulo: 'comex-mini',
                    clase: 'comex-mini'
                });

            const form =
                mini?.querySelector(
                    '[data-comx-form]'
                );

            if (!form) {
                throw new Error(
                    'No se pudo cargar el formulario.'
                );
            }

            const v =
                form.querySelector(
                    '[data-comx-prove]'
                );

            const p =
                form.querySelector(
                    '[data-comx-produ]'
                );

            const ca =
                form.querySelector(
                    '[data-comx-canti]'
                );

            const pu =
                form.querySelector(
                    '[data-comx-preun]'
                );

            const dt =
                form.querySelector(
                    '[data-comx-deta]'
                );

            const to =
                form.querySelector(
                    '[data-comx-total]'
                );

            const obse =
                form.querySelector(
                    '[data-comx-obse]'
                );

            let ps = [];
            let ls = [];

            const op = async () => {

                const datos =
                    await pedir('opcio');

                const proveedores = datos.prove || [];

                v.innerHTML =
                    proveedores.length
                        ? proveedores
                            .map(x => `
                                <option value="${esc(x.id)}">
                                    ${esc(x.razon)}
                                    ${x.ruc ? ' | ' + esc(x.ruc) : ''}
                                </option>
                            `)
                            .join('')
                        : '<option value="" disabled>Sin proveedores disponibles</option>';

                ps = datos.produ || [];

                p.innerHTML =
                    ps.length
                        ? ps
                            .map(x => `
                                <option value="${esc(x.id)}">
                                    ${esc(x.descr)}
                                    ${x.codig ? ' | ' + esc(x.codig) : ''}
                                </option>
                            `)
                            .join('')
                        : '<option value="" disabled>Sin productos disponibles</option>';
            };

            const dib = () => {

                dt.innerHTML = ls
                    .map((x, i) => `
                        <tr>
                            <td>${esc(x.txt)}</td>
                            <td>${x.canti}</td>
                            <td>${x.preun.toFixed(2)}</td>
                            <td>${(x.canti * x.preun).toFixed(2)}</td>
                            <td>
                                <button
                                    type="button"
                                    data-del="${i}"
                                >
                                    Quitar
                                </button>
                            </td>
                        </tr>
                    `)
                    .join('');

                to.textContent =
                    ls
                        .reduce(
                            (s, x) =>
                                s + x.canti * x.preun,
                            0
                        )
                        .toFixed(2);
            };

            form.querySelector(
                '[data-comx-canc]'
            )?.addEventListener(
                'click',
                () => window.NURBAK_MINI.cerrar(mini)
            );

            form.querySelector(
                '[data-comx-agre]'
            )?.addEventListener(
                'click',
                () => {

                    const id = Number(p.value);
                    const canti = Number(ca.value);
                    const preun = Number(pu.value);

                    const producto =
                        ps.find(
                            x => Number(x.id) === id
                        );

                    if (
                        !producto ||
                        canti <= 0 ||
                        preun < 0
                    ) {
                        alert(
                            'Producto, cantidad y PCOMP son obligatorios.'
                        );
                        return;
                    }

                    ls.push({
                        produ: id,
                        canti,
                        preun,
                        txt:
                            producto.descr +
                            (
                                producto.codig
                                    ? ` | ${producto.codig}`
                                    : ''
                            )
                    });

                    dib();

                    ca.value = '';
                    pu.value = '';
                    p.focus();
                }
            );

            dt.addEventListener(
                'click',
                evento => {

                    const boton =
                        evento.target.closest(
                            '[data-del]'
                        );

                    if (!boton) {
                        return;
                    }

                    ls.splice(
                        Number(boton.dataset.del),
                        1
                    );

                    dib();
                }
            );

            form.addEventListener(
                'submit',
                async evento => {

                    evento.preventDefault();

                    if (
                        !v.value ||
                        !ls.length
                    ) {
                        alert(
                            'Proveedor y productos son obligatorios.'
                        );
                        return;
                    }

                    try {

                        const body =
                            new URLSearchParams({
                                acci: 'agreg',
                                prove: v.value,
                                obse: obse.value,
                                detex: JSON.stringify(
                                    ls.map(x => ({
                                        produ: x.produ,
                                        canti: x.canti,
                                        preun: x.preun
                                    }))
                                )
                            });

                        const res =
                            await fetch(
                                R,
                                {
                                    method: 'POST',
                                    body,
                                    credentials: 'same-origin',
                                    headers: {
                                        Accept: 'application/json'
                                    }
                                }
                            );

                        const dat =
                            await res.json();

                        if (
                            !res.ok ||
                            !dat.ok
                        ) {
                            throw Error(
                                dat.mensaje ||
                                `HTTP ${res.status}`
                            );
                        }

                        window.NURBAK_CATE_SINC?.emitir({
                            modu: 'comex',
                            recu: 'comex',
                            acci: 'crear',
                            id: Number(dat.id || 0),
                            dato: dat
                        });

                        form.reset();
                        ls = [];
                        dib();

                        await listar();

                        window.NURBAK_MINI.cerrar(mini);

                    } catch (error) {

                        alert(
                            error.message
                        );
                    }
                }
            );

            window.NURBAK_CATE_SINC?.onCambio(
                () => op().catch(() => {}),
                {
                    recu: 'prove'
                },
                mini
            );

            window.NURBAK_CATE_SINC?.onCambio(
                () => op().catch(() => {}),
                {
                    recu: 'produ'
                },
                mini
            );

            await op();
            p.focus();
        };

        nuevo.addEventListener(
            'click',
            () => {
                abrir().catch(error => {
                    alert(error.message);
                });
            }
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () => listar().catch(() => {}),
            {
                recu: 'prove'
            },
            c
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () => listar().catch(() => {}),
            {
                recu: 'produ'
            },
            c
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () => listar().catch(() => {}),
            {
                recu: 'comex'
            },
            c
        );

        let t;

        b.addEventListener(
            'input',
            () => {

                clearTimeout(t);

                t = setTimeout(
                    () => listar().catch(() => {}),
                    250
                );
            }
        );

        listar().catch(error => {
            ms.hidden = false;
            ms.textContent = error.message;
        });
    };

    document.addEventListener(
        'modu:cargado',
        evento => {
            if (
                evento.detail?.modulo ===
                'compra-externa'
            ) {
                ini(evento.detail.modal);
            }
        }
    );

    document
        .querySelectorAll('[data-comx]')
        .forEach(ini);

})();
