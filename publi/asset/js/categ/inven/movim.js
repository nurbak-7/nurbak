/* ============================================================
   Ruta: publi/asset/js/categ/inven/movim.js
   Módulo: Inventario / Movimientos
   Función: Coordinar TIEND → ALMAC → MOVIM.
============================================================ */

(() => {

    const rta =
        'modul/categ/inven/movim/movim-contr.php';

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
            con?.matches?.('[data-movi]')
                ? con
                : con?.querySelector?.('[data-movi]');

        if (
            !rai ||
            rai.dataset.ini === '1'
        ) {
            return;
        }

        rai.dataset.ini = '1';

        const tien =
            rai.querySelector('[data-movi-tien]');

        const alma =
            rai.querySelector('[data-movi-alma]');

        const busc =
            rai.querySelector('[data-movi-busc]');

        const cuer =
            rai.querySelector('[data-movi-cuerpo]');

        const mens =
            rai.querySelector('[data-movi-mensaje]');

        const nuevo =
            rai.querySelector('[data-movi-nuevo]');

        const req = async (
            act,
            dat = {}
        ) => {

            const p =
                new URLSearchParams({
                    accion: act,
                    ...dat
                });

            const r = await fetch(
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

        const tin = async (
            destino
        ) => {

            const actual =
                String(
                    destino.value || ''
                );

            const r =
                await fetch(
                    'modul/categ/inven/almac/almac-contr.php?accion=tiendas',
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
                    'No se pudieron cargar tiendas.'
                );
            }

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
        };

        const cargarAlmacenes = async (
            tienda,
            destino
        ) => {

            if (!tienda) {
                destino.innerHTML = '';
                return;
            }

            const datos =
                await req(
                    'opciones',
                    {
                        tiend: tienda
                    }
                );

            const arr =
                datos.almacenes || [];

            destino.innerHTML =
                arr.length
                    ? arr
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

        const opciones = async (
            form,
            tiendaActual = ''
        ) => {

            const mtien =
                form.querySelector(
                    '[data-movi-tien]'
                );
            const prod =
                form.querySelector(
                    '[data-movi-producto]'
                );

            const falm =
                form.querySelector(
                    '[data-movi-almacen]'
                );

            if (!mtien) {
                return;
            }

            if (tiendaActual) {
                mtien.value =
                    tiendaActual;
            }

            await tin(mtien);

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

        const opt = async () => {

            const id =
                Number(
                    tien.value || 0
                );

            if (!id) {
                alma.innerHTML =
                    '<option value="">Todos los almacenes</option>';
                return;
            }

            const d =
                await req(
                    'opciones',
                    {
                        tiend: id
                    }
                );

            const arr =
                d.almacenes || [];

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
                'Cargando movimientos...';

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
                d.movimientos || [];

            cuer.innerHTML =
                arr
                    .map(x => `
                        <tr>
                            <td>${esc(x.id)}</td>
                            <td>${esc(x.descr)}</td>
                            <td>${esc(x.codig || '—')}</td>
                            <td>${esc(x.tiend || id)}</td>
                            <td>${esc(x.almacen)}</td>
                            <td>${esc(x.tipo)}</td>
                            <td>${esc(x.canti)}</td>
                            <td>${esc(x.motvo || '—')}</td>
                            <td>${esc(x.fecha)}</td>
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

            if (!(await auth('MOVIG'))) {
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
                    titulo: 'Registrar movimiento',
                    rutaVista:
                        'vista/categ/inven/movim-mini.php',
                    modulo: 'movim-mini',
                    clase: 'movim-mini'
                });

            const form =
                mini?.querySelector(
                    '[data-movi-form]'
                );

            if (!form) {
                throw new Error(
                    'No se pudo cargar el formulario.'
                );
            }

            await opciones(
                form,
                tien.value
            );

            const mtien =
                form.querySelector(
                    '[data-movi-tien]'
                );

            const falm =
                form.querySelector(
                    '[data-movi-almacen]'
                );

            const cancelar =
                form.querySelector(
                    '[data-movi-cancelar]'
                );

            mtien.addEventListener(
                'change',
                () => {
                    cargarAlmacenes(
                        mtien.value,
                        falm
                    ).catch(error =>
                        alert(
                            error.message
                        )
                    );
                }
            );

            cancelar?.addEventListener(
                'click',
                () => window.NURBAK_MINI.cerrar(mini)
            );

            form.addEventListener(
                'submit',
                async evento => {

                    evento.preventDefault();

                    try {

                        const prod =
                            form.querySelector(
                                '[data-movi-producto]'
                            );

                        const tipo =
                            form.querySelector(
                                '[data-movi-tipo]'
                            );

                        const cant =
                            form.querySelector(
                                '[data-movi-canti]'
                            );

                        const motv =
                            form.querySelector(
                                '[data-movi-motvo]'
                            );

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
                                'registrar',
                                {
                                    tiend:
                                        mtien.value,
                                    produ:
                                        prod.value,
                                    almac:
                                        falm.value,
                                    tipo:
                                        tipo.value,
                                    canti:
                                        cant.value,
                                    motvo:
                                        motv.value
                                }
                            );

                        window.NURBAK_CATE_SINC?.emitir({
                            modu: 'movim',
                            recu: 'movim',
                            acci: 'regis',
                            id: Number(
                                datos.id || 0
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
                                produ:
                                    Number(
                                        prod.value || 0
                                    ),
                                ...datos
                            }
                        });

                        window.NURBAK_CATE_SINC?.emitir({
                            modu: 'movim',
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
                                    )
                            }
                        });

                        await lis();

                        window.NURBAK_MINI.cerrar(
                            mini
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
                    opciones(
                        form,
                        mtien.value
                    ).catch(() => {}),
                {
                    recu: 'tiend'
                },
                mini
            );

            window.NURBAK_CATE_SINC?.onCambio(
                () =>
                    opciones(
                        form,
                        mtien.value
                    ).catch(() => {}),
                {
                    recu: 'almac'
                },
                mini
            );

            window.NURBAK_CATE_SINC?.onCambio(
                () =>
                    opciones(
                        form,
                        mtien.value
                    ).catch(() => {}),
                {
                    recu: 'produ'
                },
                mini
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

            await tin(tien);
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

        tin(tien)
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
                'movimientos'
            ) {
                ini(
                    evento.detail.modal
                );
            }
        }
    );

    document
        .querySelectorAll('[data-movi]')
        .forEach(ini);

})();
