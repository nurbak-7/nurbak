/* ============================================================
   Ruta: publi/asset/js/categ/inven/stock.js
   Módulo: Inventario / Stock
   Función: Coordinar TIEND → ALMAC → STOCK.
============================================================ */

(() => {

    const rta =
        'modul/categ/inven/stock/stock-contr.php';

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

    const ini = con => {

        const rai =
            con?.matches?.('[data-stoc]')
                ? con
                : con?.querySelector?.('[data-stoc]');

        if (
            !rai ||
            rai.dataset.ini === '1'
        ) {
            return;
        }

        rai.dataset.ini = '1';

        const tien =
            rai.querySelector('[data-stoc-tien]');

        const alma =
            rai.querySelector('[data-stoc-alma]');

        const busc =
            rai.querySelector('[data-stoc-busc]');

        const cuer =
            rai.querySelector('[data-stoc-cuerpo]');

        const mens =
            rai.querySelector('[data-stoc-mensaje]');

        const nuevo =
            rai.querySelector('[data-stoc-asignar]');

        const req = async (
            act,
            dat = {}
        ) => {

            const p =
                new URLSearchParams({
                    accion: act,
                    ...dat
                });

            const r =
                await fetch(
                    `${rta}?${p}`,
                    {
                        cache: 'no-store',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json'
                        }
                    }
                );

            const d =
                await r.json();

            if (
                !r.ok ||
                !d.ok
            ) {
                throw new Error(
                    d.mensaje ||
                    `HTTP ${r.status}`
                );
            }

            return d;
        };

        const cargarTiendas = async (
            destino
        ) => {

            const actual =
                String(
                    destino.value || ''
                );

            const d =
                await req(
                    'opciones',
                    {
                        tiend: 0
                    }
                );

            const tiendas = d.tiendas || [];

            destino.innerHTML =
                tiendas.length
                    ? tiendas
                        .map(x => `
                        <option value="${esc(x.id)}">
                            ${esc(x.codig)}
                            —
                            ${esc(x.nombr)}
                            </option>
                        `)
                        .join('')
                    : '<option value="" disabled>Sin tiendas disponibles</option>';

            if (
                actual &&
                [...destino.options]
                    .some(
                        o => o.value === actual
                    )
            ) {
                destino.value =
                    actual;
            }

            return d;
        };

        const cargarAlmacenes = async (
            tienda,
            destino
        ) => {

            if (!tienda) {
                destino.innerHTML = '';
                return;
            }

            const d =
                await req(
                    'opciones',
                    {
                        tiend: tienda
                    }
                );

            const almacenes = d.almacenes || [];

            destino.innerHTML =
                almacenes.length
                    ? almacenes
                        .map(x => `
                        <option value="${esc(x.id)}">
                            ${esc(x.nombr)}
                            ${
                                Number(x.prin) === 1
                                    ? ' — PRINCIPAL'
                                    : ''
                            }
                            </option>
                        `)
                        .join('')
                    : '<option value="" disabled>Sin almacenes disponibles</option>';
        };

        const cargarOpcionesMini = async (
            mini,
            tiendaActual = ''
        ) => {

            const form =
                mini.querySelector(
                    '[data-stoc-form]'
                );

            const mtien =
                form.querySelector(
                    '[data-stoc-tien]'
                );
            const prod =
                form.querySelector(
                    '[data-stoc-producto]'
                );

            const falm =
                form.querySelector(
                    '[data-stoc-almacen]'
                );

            if (tiendaActual) {
                mtien.value =
                    tiendaActual;
            }

            const tiendas =
                await req(
                    'opciones',
                    {
                        tiend: 0
                    }
                );

            const listaTiendas = tiendas.tiendas || [];

            mtien.innerHTML =
                listaTiendas.length
                    ? listaTiendas
                        .map(x => `
                        <option value="${esc(x.id)}">
                            ${esc(x.codig)}
                            —
                            ${esc(x.nombr)}
                            </option>
                        `)
                        .join('')
                    : '<option value="" disabled>Sin tiendas disponibles</option>';

            if (
                tiendaActual &&
                [...mtien.options]
                    .some(
                        o =>
                            o.value ===
                            tiendaActual
                    )
            ) {
                mtien.value =
                    tiendaActual;
            }

            await cargarAlmacenes(
                mtien.value,
                falm
            );


            const datos =
                await req(
                    'opciones',
                    {
                        tiend:
                            mtien.value || 0
                    }
                );

            const productos = datos.productos || [];

            prod.innerHTML =
                productos.length
                    ? productos
                        .map(x => `
                        <option value="${esc(x.id)}">
                            ${esc(x.descr)}
                            ${
                                x.codig
                                    ? ' | ' + esc(x.codig)
                                    : ''
                            }
                            </option>
                        `)
                        .join('')
                    : '<option value="" disabled>Sin productos disponibles</option>';
        };

        const alm = arr => {

            alma.innerHTML =
                '<option value="">Todos los almacenes</option>' +
                arr
                    .map(x => `
                        <option value="${esc(x.id)}">
                            ${esc(x.nombr)}
                            ${
                                Number(x.prin) === 1
                                    ? ' — PRINCIPAL'
                                    : ''
                            }
                        </option>
                    `)
                    .join('');
        };

        const opt = async () => {

            const id =
                Number(
                    tien.value || 0
                );

            if (!id) {
                alm([]);
                return;
            }

            const d =
                await req(
                    'opciones',
                    {
                        tiend: id
                    }
                );

            alm(
                d.almacenes || []
            );
        };

        const tin = async () => {
            await cargarTiendas(tien);
        };

        const lis = async () => {

            const id =
                Number(
                    tien.value || 0
                );

            if (!id) {
                cuer.innerHTML = '';
                mens.hidden = false;
                mens.textContent =
                    'Seleccione una tienda.';
                return;
            }

            mens.hidden = false;
            mens.textContent =
                'Cargando stock...';

            const d =
                await req(
                    'listar',
                    {
                        tiend: id,
                        almac:
                            Number(
                                alma.value || 0
                            ),
                        buscar:
                            busc.value.trim(),
                        limite: 100
                    }
                );

            const arr =
                d.stock || [];

            cuer.innerHTML =
                arr
                    .map(x => `
                        <tr>
                            <td>${esc(x.produ)}</td>
                            <td>${esc(x.descr)}</td>
                            <td>${esc(x.codig || '—')}</td>
                            <td>${esc(x.origi || '—')}</td>
                            <td>${esc(x.tiend || id)}</td>
                            <td>${esc(x.almacen)}</td>
                            <td>
                                ${
                                    Number(x.prin) === 1
                                        ? 'PRINCIPAL'
                                        : 'SECUNDARIO'
                                }
                            </td>
                            <td>${esc(x.canti)}</td>
                            <td>${esc(x.salss)}</td>
                            <td>${esc(x.stmin)}</td>
                            <td>${esc(x.stmax)}</td>
                            <td>${esc(x.ultim || '—')}</td>
                        </tr>
                    `)
                    .join('');

            mens.hidden =
                arr.length > 0;

            if (!arr.length) {
                mens.textContent =
                    'Sin registros.';
            }
        };

        const abrir = async () => {

            if (!(await auth('STOCG'))) {
                return;
            }

            if (!tien.value) {
                alert(
                    'Seleccione una tienda.'
                );
                return;
            }

            if (!window.NURBAK_MINI?.abrir) {
                throw new Error(
                    'MINI no está disponible.'
                );
            }

            const mini =
                await window.NURBAK_MINI.abrir({
                    titulo: 'Asignar producto',
                    rutaVista:
                        'vista/categ/inven/stock-mini.php',
                    modulo: 'stock-mini',
                    clase: 'stock-mini'
                });

            const form =
                mini?.querySelector(
                    '[data-stoc-form]'
                );

            if (!form) {
                throw new Error(
                    'No se pudo cargar el formulario.'
                );
            }

            const mtien =
                form.querySelector(
                    '[data-stoc-tien]'
                );
            const prod =
                form.querySelector(
                    '[data-stoc-producto]'
                );

            const falm =
                form.querySelector(
                    '[data-stoc-almacen]'
                );

            const miniVentana =
                form.closest(
                    '[data-mini-ventana]'
                );

            const inicial =
                tien.value;

            await cargarOpcionesMini(
                miniVentana,
                inicial
            );

            form.querySelector(
                '[data-stoc-cancelar]'
            )?.addEventListener(
                'click',
                () => window.NURBAK_MINI.cerrar(miniVentana)
            );

            mtien.addEventListener(
                'change',
                () => {
                    cargarOpcionesMini(
                        miniVentana,
                        mtien.value
                    ).catch(error =>
                        alert(
                            error.message
                        )
                    );
                }
            );

            form.addEventListener(
                'submit',
                async evento => {

                    evento.preventDefault();

                    try {

                        if (
                            !mtien.value ||
                            !falm.value
                        ) {
                            alert(
                                'Seleccione tienda y almacén.'
                            );
                            return;
                        }

                        const datos =
                            await req(
                                'asignar',
                                {
                                    tiend:
                                        mtien.value,
                                    produ:
                                        prod.value,
                                    almac:
                                        falm.value,
                                    stmin:
                                        form.querySelector(
                                            '[data-stoc-minimo]'
                                        ).value || 0,
                                    stmax:
                                        form.querySelector(
                                            '[data-stoc-maximo]'
                                        ).value || 0
                                }
                            );

                        window.NURBAK_CATE_SINC?.emitir({
                            modu: 'stock',
                            recu: 'stock',
                            acci: 'actua',
                            id: Number(
                                prod.value || 0
                            ),
                            dato: {
                                tiend:
                                    Number(
                                        mtien.value || 0
                                    ),
                                almac:
                                    Number(
                                        falm.value || 0
                                    ),
                                ...datos
                            }
                        });

                        await lis();

                        window.NURBAK_MINI.cerrar(
                            miniVentana
                        );

                    } catch (error) {

                        alert(
                            error.message
                        );
                    }
                }
            );

            window.NURBAK_ADMI_SINC?.onCambio(
                () =>
                    cargarOpcionesMini(
                        miniVentana,
                        mtien.value
                    ).catch(() => {}),
                {
                    recu: 'tiend'
                },
                miniVentana
            );

            window.NURBAK_CATE_SINC?.onCambio(
                () =>
                    cargarOpcionesMini(
                        miniVentana,
                        mtien.value
                    ).catch(() => {}),
                {
                    recu: 'almac'
                },
                miniVentana
            );

            window.NURBAK_CATE_SINC?.onCambio(
                () =>
                    cargarOpcionesMini(
                        miniVentana,
                        mtien.value
                    ).catch(() => {}),
                {
                    recu: 'produ'
                },
                miniVentana
            );
        };

        tien.addEventListener(
            'change',
            async () => {

                try {
                    await opt();
                    await lis();
                } catch (error) {
                    alert(
                        error.message
                    );
                }
            }
        );

        alma.addEventListener(
            'change',
            () =>
                lis().catch(
                    error =>
                        alert(
                            error.message
                        )
                )
        );

        nuevo.addEventListener(
            'click',
            () => {
                abrir().catch(error => {
                    alert(error.message);
                });
            }
        );

        const actualiza = async () => {

            await tin();
            await opt();
            await lis();
        };

        window.NURBAK_ADMI_SINC?.onCambio(
            () =>
                actualiza().catch(
                    () => {}
                ),
            {
                recu: 'tiend'
            },
            con
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () =>
                actualiza().catch(
                    () => {}
                ),
            {
                recu: 'almac'
            },
            con
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () =>
                actualiza().catch(
                    () => {}
                ),
            {
                recu: 'produ'
            },
            con
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () =>
                lis().catch(
                    () => {}
                ),
            {
                recu: 'movim'
            },
            con
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () =>
                lis().catch(
                    () => {}
                ),
            {
                recu: 'stock'
            },
            con
        );

        let t;

        busc.addEventListener(
            'input',
            () => {

                clearTimeout(t);

                t =
                    setTimeout(
                        () =>
                            lis().catch(
                                error =>
                                    alert(
                                        error.message
                                    )
                            ),
                        220
                    );
            }
        );

        tin()
            .then(opt)
            .then(lis)
            .catch(error => {

                mens.textContent =
                    error.message;

                mens.hidden = false;
            });
    };

    document.addEventListener(
        'modu:cargado',
        evento => {

            if (
                evento.detail?.modulo ===
                'stock'
            ) {
                ini(
                    evento.detail.modal
                );
            }
        }
    );

    document
        .querySelectorAll('[data-stoc]')
        .forEach(ini);

})();
