/* ============================================================
   Ruta: publi/asset/js/categ/inven/almac.js
   Módulo: Inventario / Almacenes
   Función: Coordinar TIEND → ALMAC.
============================================================ */

(() => {

    const rta =
        'modul/categ/inven/almac/almac-contr.php';

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
            con?.matches?.('[data-alma]')
                ? con
                : con?.querySelector?.('[data-alma]');

        if (
            !rai ||
            rai.dataset.ini === '1'
        ) {
            return;
        }

        rai.dataset.ini = '1';

        const tien =
            rai.querySelector('[data-alma-tien]');

        const busc =
            rai.querySelector('[data-alma-busc]');

        const cuer =
            rai.querySelector('[data-alma-cuerpo]');

        const mens =
            rai.querySelector('[data-alma-mensaje]');

        // Fuente de verdad: registros devueltos por el endpoint TIEND.
        // La etiqueta visible del selector no se usa como identificador.
        let tiendasCargadas = [];

        const idNumerico = valor => {
            const texto = String(valor ?? '').trim();
            if (!/^\d+$/.test(texto)) return 0;
            const id = Number(texto);
            return Number.isSafeInteger(id) && id > 0 ? id : 0;
        };

        const normalizarTexto = valor => String(valor ?? '')
            .normalize('NFKC')
            .replace(/\s+/g, ' ')
            .trim()
            .toLocaleLowerCase();

        const req = async (
            act,
            dat = {}
        ) => {

            const p = new URLSearchParams({
                accion: act,
                ...dat
            });
            const muta = ['agregar', 'estad', 'principal'].includes(act);

            const r = await fetch(
                muta ? rta : `${rta}?${p}`,
                {
                    method: muta ? 'POST' : 'GET',
                    body: muta ? p : undefined,
                    cache: 'no-store',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        ...(muta ? {
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                        } : {})
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
            destino = tien
        ) => {

            const actual =
                String(destino.value || '').trim();

            const d =
                await req('tiendas');

            const tiendas = Array.isArray(d.tiendas)
                ? d.tiendas
                : [];

            if (destino === tien) {
                tiendasCargadas = tiendas;
            }

            destino.innerHTML =
                tiendas.length
                    ? tiendas
                        .map(x => `
                            <option value="${esc(x.id)}">
                                ${esc(x.codig)}
                                —
                                ${esc(x.nombr)}
                                ${
                                    Number(x.activ) === 1
                                        ? ''
                                        : ' (INACTIVA)'
                                }
                            </option>
                        `)
                        .join('')
                    : '<option value="" disabled>Sin tiendas disponibles</option>';

            // Preserva la tienda por ID o código si existe; si no, selecciona
            // la primera tienda activa (o la primera disponible).
            const previa = tiendas.find(x =>
                String(x.id ?? '').trim() === actual ||
                String(x.codig ?? '').trim() === actual
            );
            const elegida = previa ||
                tiendas.find(x => Number(x.activ) === 1 && idNumerico(x.id)) ||
                tiendas.find(x => idNumerico(x.id));

            if (elegida && idNumerico(elegida.id)) {
                destino.value = String(elegida.id);
            } else if (destino === tien) {
                destino.selectedIndex = -1;
            }

            return tiendas;
        };

        const tiendaSeleccionadaId = () => {
            // Primero, contrato normal: el valor es el ID numérico.
            const directo = idNumerico(tien.value);
            if (directo) return directo;

            // Recupera el ID desde la opción visible usando codig/nombr de
            // la misma respuesta, por si el selector conserva el código.
            const opcion = tien.selectedOptions?.[0];
            const texto = normalizarTexto(opcion?.textContent || '');
            const tienda = tiendasCargadas.find(x => {
                const codig = normalizarTexto(x.codig);
                const nombr = normalizarTexto(x.nombr);
                return texto && codig && nombr &&
                    (texto === normalizarTexto(`${x.codig} — ${x.nombr}`) ||
                     (texto.includes(codig) && texto.includes(nombr)));
            });
            const recuperado = idNumerico(tienda?.id);
            if (recuperado) {
                tien.value = String(recuperado);
                return recuperado;
            }

            return 0;
        };

        const lis = async () => {

            const id = tiendaSeleccionadaId();

            if (!id) {
                cuer.innerHTML = '';
                mens.hidden = false;
                mens.textContent = tiendasCargadas.length
                    ? 'No se pudo identificar el ID de la tienda seleccionada.'
                    : 'No hay tiendas disponibles para listar almacenes.';
                return;
            }

            mens.hidden = false;
            mens.textContent =
                'Cargando almacenes...';

            const d =
                await req(
                    'listar',
                    {
                        tiend: id,
                        buscar:
                            busc.value.trim(),
                        limite: 100
                    }
                );

            const arr =
                d.almacenes || [];

            cuer.innerHTML =
                arr.map(x => `
                    <tr>
                        <td>${esc(x.id)}</td>
                        <td>
                            ${esc(
                                tien.selectedOptions[0]
                                    ?.textContent || ''
                            )}
                        </td>
                        <td>${esc(x.nombr)}</td>
                        <td>${esc(x.descr || '—')}</td>
                        <td>${esc(x.direc || '—')}</td>
                        <td>
                            ${
                                Number(x.prin) === 1
                                    ? 'PRINCIPAL'
                                    : 'SECUNDARIO'
                            }
                        </td>
                        <td>
                            ${
                                Number(x.estad) === 1
                                    ? 'ACTIVO'
                                    : 'INACTIVO'
                            }
                        </td>
                        <td>${esc(x.fecre)}</td>
                        <td>
                            ${
                                Number(x.prin) === 0 &&
                                Number(x.estad) === 1
                                    ? `
                                        <button
                                            type="button"
                                            data-alma-pri="${x.id}"
                                        >
                                            Principal
                                        </button>
                                    `
                                    : ''
                            }

                            <button
                                type="button"
                                data-alma-est="${x.id}"
                                data-est="${
                                    Number(x.estad) === 1
                                        ? 0
                                        : 1
                                }"
                            >
                                ${
                                    Number(x.estad) === 1
                                        ? 'Inactivar'
                                        : 'Activar'
                                }
                            </button>
                        </td>
                    </tr>
                `)
                .join('');

            mens.hidden =
                arr.length > 0;

            if (!arr.length) {
                mens.textContent =
                    'La tienda no tiene almacenes.';
            }
        };

        const abrir = async () => {

            if (!(await auth('ALMAG'))) {
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
                    titulo: 'Nuevo almacén',
                    rutaVista:
                        'vista/categ/inven/almac-mini.php',
                    modulo: 'almac-mini',
                    clase: 'almac-mini'
                });

            const form =
                mini?.querySelector(
                    '[data-alma-form]'
                );

            if (!form) {
                throw new Error(
                    'No se pudo cargar el formulario.'
                );
            }

            const mtien =
                form.querySelector(
                    '[data-alma-tien]'
                );

            const nomb =
                form.querySelector(
                    '[data-alma-nombr]'
                );

            const desc =
                form.querySelector(
                    '[data-alma-descr]'
                );

            const dire =
                form.querySelector(
                    '[data-alma-direc]'
                );

            const prin =
                form.querySelector(
                    '[data-alma-prin]'
                );

            mtien.value =
                tien.value;

            form.querySelector(
                '[data-alma-cancelar]'
            )?.addEventListener(
                'click',
                () => window.NURBAK_MINI.cerrar(mini)
            );

            form.addEventListener(
                'submit',
                async evento => {

                    evento.preventDefault();

                    try {

                        const datos =
                            await req(
                                'agregar',
                                {
                                    tiend:
                                        mtien.value,
                                    nombr:
                                        nomb.value,
                                    descr:
                                        desc.value,
                                    direc:
                                        dire.value,
                                    prin:
                                        prin.checked
                                            ? 1
                                            : 0
                                }
                            );

                        window.NURBAK_CATE_SINC?.emitir({
                            modu: 'almac',
                            recu: 'almac',
                            acci: 'crear',
                            id: Number(
                                datos.id || 0
                            ),
                            dato: {
                                tiend:
                                    Number(
                                        mtien.value || 0
                                    ),
                                ...datos
                            }
                        });

                        form.reset();
                        mtien.value =
                            tien.value;

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

            nomb.focus();
        };

        rai.querySelector(
            '[data-alma-nuevo]'
        )?.addEventListener(
            'click',
            () => {
                abrir().catch(error => {
                    alert(error.message);
                });
            }
        );

        tien.addEventListener(
            'change',
            () => {
                lis().catch(error =>
                    alert(error.message)
                );
            }
        );

        busc.addEventListener(
            'input',
            (() => {

                let tim;

                return () => {

                    clearTimeout(tim);

                    tim =
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
                };
            })()
        );

        window.NURBAK_ADMI_SINC?.onCambio(
            async () => {

                const actual =
                    String(
                        tien.value || ''
                    );

                await tin();

                if (
                    actual &&
                    [...tien.options]
                        .some(
                            o =>
                                o.value === actual
                        )
                ) {
                    tien.value =
                        actual;
                }

                await lis();
            },
            {
                recu: 'tiend'
            },
            con
        );

        window.NURBAK_CATE_SINC?.onCambio(
            () =>
                lis().catch(
                    () => {}
                ),
            {
                recu: 'almac'
            },
            con
        );

        cuer.addEventListener(
            'click',
            async evento => {

                const bp =
                    evento.target.closest(
                        '[data-alma-pri]'
                    );

                const be =
                    evento.target.closest(
                        '[data-alma-est]'
                    );

                try {

                    if (bp) {

                        if (
                            !confirm(
                                '¿Designar este almacén como principal?'
                            )
                        ) {
                            return;
                        }

                        await req(
                            'principal',
                            {
                                id:
                                    bp.dataset.almaPri
                            }
                        );

                        window.NURBAK_CATE_SINC?.emitir({
                            modu: 'almac',
                            recu: 'almac',
                            acci: 'actua',
                            id: Number(
                                bp.dataset.almaPri || 0
                            ),
                            dato: {
                                tiend:
                                    Number(
                                        tien.value || 0
                                    )
                            }
                        });

                    } else if (be) {

                        const es =
                            Number(
                                be.dataset.est
                            );

                        if (
                            !confirm(
                                es
                                    ? '¿Activar este almacén?'
                                    : '¿Inactivar este almacén?'
                            )
                        ) {
                            return;
                        }

                        await req(
                            'estad',
                            {
                                id:
                                    be.dataset.almaEst,
                                estad: es
                            }
                        );

                        window.NURBAK_CATE_SINC?.emitir({
                            modu: 'almac',
                            recu: 'almac',
                            acci: 'esta',
                            id: Number(
                                be.dataset.almaEst || 0
                            ),
                            dato: {
                                tiend:
                                    Number(
                                        tien.value || 0
                                    ),
                                estad: es
                            }
                        });

                    } else {
                        return;
                    }

                    await lis();

                } catch (error) {

                    alert(
                        error.message
                    );
                }
            }
        );

        tin()
            .then(async () => {
                // Igual que el flujo inicial de Stock: cargar tiendas y después
                // listar usando la tienda identificada, sin contar opciones.
                await lis();
            })
            .catch(error => {

                mens.hidden = false;
                mens.textContent =
                    error.message;
            });
    };

    document.addEventListener(
        'modu:cargado',
        evento => {

            if (
                evento.detail?.modulo ===
                'almacenes'
            ) {
                ini(
                    evento.detail.modal
                );
            }
        }
    );

    document
        .querySelectorAll('[data-alma]')
        .forEach(ini);

})();
