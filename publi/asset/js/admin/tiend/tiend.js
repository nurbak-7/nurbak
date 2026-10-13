/* ============================================================
   Ruta: publi/asset/js/admin/tiend/tiend.js
   Función: Coordinar la interfaz del submódulo TIEND.
   🔹 Nivel 2: Listar y buscar tiendas.
   🔹 Nivel 3: CREAR / EDITAR tienda.
   🔹 Nivel 4: Administrar usuarios.
   🔹 Nivel 5: Asignar usuario.
   🔹 Nivel 6: Cambiar responsable.
   🔹 Nivel 7: Activar / inactivar.
   🔹 Nivel 8: Eliminar.
============================================================ */

(() => {
    const rutaCont = 'modul/admin/tiend/tiend-contr.php';
        const esc = valor => String(valor ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const pedir = async (accion, parametros = {}) => {
        const url = new URL(rutaCont, document.baseURI);
        url.searchParams.set('accion', accion);

        Object.entries(parametros).forEach(([clave, valor]) => {
            url.searchParams.set(clave, valor);
        });

        
        try {
            const respuesta = await fetch(url.href, {
                method: 'GET',
                cache: 'no-store',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            

            const texto = await respuesta.text();
            let datos;

            try {
                datos = JSON.parse(texto);
            } catch (_) {
                
                throw new Error(`Respuesta inválida del servidor. HTTP ${respuesta.status}.`);
            }

            

            if (!respuesta.ok || datos.ok !== true) {
                throw new Error(datos.mensaje || `HTTP ${respuesta.status}`);
            }

            
            return datos;
        } catch (error) {
            
            throw error;
        }
    };

    const enviar = async (accion, datos = {}) => {
        const formulario = datos instanceof FormData ? datos : new FormData();

        if (!(datos instanceof FormData)) {
            Object.entries(datos).forEach(([clave, valor]) => {
                formulario.append(clave, valor);
            });
        }

        formulario.set('accion', accion);

        
        try {
            const respuesta = await fetch(`${rutaCont}?_ts=${Date.now()}`, {
                method: 'POST',
                body: formulario,
                cache: 'no-store',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            

            const texto = await respuesta.text();
            let resultado;

            try {
                resultado = JSON.parse(texto);
            } catch (_) {
                
                throw new Error(`Respuesta inválida del servidor. HTTP ${respuesta.status}.`);
            }

            

            if (!respuesta.ok || resultado.ok !== true) {
                throw new Error(resultado.mensaje || `HTTP ${respuesta.status}`);
            }

            
            return resultado;
        } catch (error) {
            
            throw error;
        }
    };

    const iniciar = async () => {
        const area = document.querySelector('[data-admi-tiend]');

        if (!area || area.dataset.tiendIniciado === 'si') return;

        area.dataset.tiendIniciado = 'si';

        const lista = area.querySelector('[data-tiend-lista]');
        const buscar = area.querySelector('[data-tiend-buscar]');

        /* ========================================================
           NIVEL 2 — LISTAR
        ======================================================== */

        const pintar = tiendas => {
            if (!lista) return;

            if (!tiendas.length) {
                lista.innerHTML = '<tr><td colspan="8">No se encontraron tiendas.</td></tr>';
                return;
            }

            lista.innerHTML = tiendas.map(tienda => {
                const usuarios = Number(tienda.usuarios || 0);
                const responsables = Number(tienda.responsables || 0);
                const activ = Number(tienda.activ) === 1;

                let estad = activ
                    ? '<span class="admi-tiend-estad is-activ">ACTIVA</span>'
                    : '<span class="admi-tiend-estad is-inactivo">INACTIVA</span>';

                let responsable = esc(tienda.responsable_nomb || '—');

                if (responsables > 1) {
                    responsable += ' <span class="admi-tiend-alerta">!</span>';
                }

                return `
                    <tr data-tiend-id="${tienda.id}">
                        <td>${esc(tienda.tiend_codi)}</td>
                        <td>${esc(tienda.tiend_nomb)}</td>
                        <td>${esc(tienda.tiend_dire || '—')}</td>
                        <td>${esc(tienda.tiend_tele || '—')}</td>
                        <td>${usuarios}</td>
                        <td>${responsable}</td>
                        <td>${estad}</td>
                        <td>
                            <div class="admi-tiend-acciones-fila">
                                <button type="button" data-tiend-accion="editar" data-id="${tienda.id}">EDITAR</button>
                                <button type="button" data-tiend-accion="usuarios" data-id="${tienda.id}">USUARIOS</button>
                                <button type="button" data-tiend-accion="almacenes" data-id="${tienda.id}">ALMACENES</button>
                                <button type="button"
                                    data-tiend-accion="estad"
                                    data-id="${tienda.id}"
                                    data-activ="${activ ? 0 : 1}">
                                    ${activ ? 'INACTIVAR' : 'ACTIVAR'}
                                </button>
                                <button type="button" data-tiend-accion="eliminar" data-id="${tienda.id}">ELIMINAR</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        };

        const cargar = async (texto = '') => {
            try {
                const limpio = texto.trim();
                

                const datos = limpio === ''
                    ? await pedir('LISTAR')
                    : await pedir('BUSCAR', { texto: limpio });

                pintar(datos.tiendas || []);

                
            } catch (error) {
                
                if (lista) {
                    lista.innerHTML = '<tr><td colspan="8">No se pudo cargar la lista.</td></tr>';
                }
            }
        };

        /* ========================================================
           NIVEL 3 — CREAR / EDITAR
        ======================================================== */

        const abrirForm = async (modo, tienda = null) => {
            if (!window.NURBAK_MINI?.abrir) {
                throw new Error('La mini-ventana no está disponible.');
            }

            const titulo = modo === 'crear'
                ? 'NUEVA TIENDA'
                : 'EDITAR TIENDA';

            const mini = await window.NURBAK_MINI.abrir({
                titulo,
                rutaVista: 'vista/admin/tiend/tiend-formu.php',
                modulo: modo === 'crear' ? 'admi-tiend-nueva' : 'admi-tiend-editar',
                clase: 'admi-tiend-mini'
            });

            const form = mini.querySelector('[data-tiend-form]');
            if (!form) throw new Error('No se encontró el formulario de tienda.');

            const codig = form.querySelector('[data-tiend-campo="codig"]');
            const nombr = form.querySelector('[data-tiend-campo="nombr"]');
            const direc = form.querySelector('[data-tiend-campo="direc"]');
            const telef = form.querySelector('[data-tiend-campo="telef"]');

            if (modo === 'crear') {
                
                codig.value = (await pedir('CODIGO')).codig || '';
                
            } else {
                codig.value = tienda.tiend_codi || '';
                nombr.value = tienda.tiend_nomb || '';
                direc.value = tienda.tiend_dire || '';
                telef.value = tienda.tiend_tele || '';
            }

            form.querySelector('[data-tiend-cancelar]')?.addEventListener('click', () => {
                window.NURBAK_MINI.cerrar(mini);
            }, { once: true });

            form.addEventListener('submit', async evento => {
                evento.preventDefault();

                const boton = form.querySelector('button[type="submit"]');
                if (boton) boton.disabled = true;

                try {
                    const datos = new FormData(form);

                    if (modo === 'editar') {
                        datos.set('id', String(tienda.id));
                    }

                    

                    const resultado = await enviar(
                        modo === 'crear' ? 'CREAR' : 'ACTUALIZAR',
                        datos
                    );

                    // SINCRONIZACIÓN | FASE 2
                    window.NURBAK_ADMI_SINC?.emitir({
                        modu: 'tiend',
                        recu: 'tiend',
                        acci: modo === 'crear' ? 'crear' : 'actua',
                        id: Number(resultado.id || tienda?.id || 0),
                        dato: resultado
                    });

                    window.NURBAK_MINI.cerrar(mini);
                    await cargar(buscar?.value || '');
                } catch (error) {
                    
                    alert(error.message);
                } finally {
                    if (boton) boton.disabled = false;
                }
            }, { once: true });

            return mini;
        };

        /* ========================================================
           NIVEL 4 — USUARIOS
        ======================================================== */

        const abrirUsuarios = async id => {
            if (!window.NURBAK_MINI?.abrir) {
                throw new Error('La mini-ventana no está disponible.');
            }

            const datosTienda = await pedir('PORID', { id });
            const tienda = datosTienda.tienda;

            const mini = await window.NURBAK_MINI.abrir({
                titulo: `USUARIOS — ${tienda.tiend_codi}`,
                rutaVista: 'vista/admin/tiend/tiend-usuar.php',
                modulo: 'admi-tiend-usuarios',
                clase: 'admi-tiend-mini admi-tiend-usuarios-mini'
            });

            const listaUsuarios = mini.querySelector('[data-tiend-usuarios-lista]');
            const titulo = mini.querySelector('[data-tiend-usuarios-titulo]');
            const estad = mini.querySelector('[data-tiend-usuarios-estad]');
            const info = mini.querySelector('[data-tiend-usuarios-info]');

            if (titulo) titulo.textContent = `${tienda.tiend_codi} — ${tienda.tiend_nomb}`;
            if (estad) {
                estad.innerHTML = Number(tienda.activ) === 1
                    ? '<span class="admi-tiend-estad is-activ">ACTIVA</span>'
                    : '<span class="admi-tiend-estad is-inactivo">INACTIVA</span>';
            }

            const cargarUsuarios = async () => {
                const datos = await pedir('USUARIOS', { tiend: id });
                const usuarios = datos.usuarios || [];
                const responsables = usuarios.filter(u => Number(u.es_resp) === 1).length;

                if (info) {
                    info.textContent = `${usuarios.length} usuario(s) · ${responsables} responsable(s)`;
                }

                if (!usuarios.length) {
                    listaUsuarios.innerHTML = '<tr><td colspan="5">No hay usuarios asignados.</td></tr>';
                    return;
                }

                listaUsuarios.innerHTML = usuarios.map(u => {
                    const codig = u.admi_codi || u.oper_codi || '—';
                    const resp = Number(u.es_resp) === 1;

                    return `
                        <tr>
                            <td>${esc(codig)}</td>
                            <td>${esc(u.usua_logi)}</td>
                            <td>${esc(u.usua_nomb)}</td>
                            <td>
                                ${resp
                                    ? '<span class="admi-tiend-resp is-si">RESPONSABLE</span>'
                                    : '<span class="admi-tiend-resp is-no">USUARIO</span>'}
                            </td>
                            <td>
                                <div class="admi-tiend-acciones-fila">
                                    ${resp ? '' : `<button type="button" data-tiend-usuario-accion="responsable" data-usua-id="${u.id}">HACER RESPONSABLE</button>`}
                                    <button type="button" data-tiend-usuario-accion="quitar" data-usua-id="${u.id}">RETIRAR</button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            };

            mini.addEventListener('click', async evento => {
                const boton = evento.target.closest('[data-tiend-usuario-accion]');
                if (!boton || !mini.contains(boton)) return;

                const accion = boton.dataset.tiendUsuarioAccion;
                const usuaId = Number(boton.dataset.usuaId || 0);

                if (!usuaId) return;

                try {
                    boton.disabled = true;

                    if (accion === 'responsable') {
                        /* ====================================================
                           NIVEL 6.1 — SOLICITAR CAMBIO DE RESPONSABLE
                           Registrar la intención antes de enviar la operación.
                        ==================================================== */
                        

                        if (!confirm('¿Designar este usuario como responsable principal?')) {
                            
                            return;
                        }

                        await enviar('RESPONSABLE', {
                            tiend: id,
                            usuar: usuaId
                        });

                        // SINCRONIZACIÓN | FASE 2
                        window.NURBAK_ADMI_SINC?.emitir({
                            modu: 'tiend',
                            recu: 'tiend',
                            acci: 'resp',
                            id,
                            dato: { usuar: usuaId }
                        });

                        /* ====================================================
                           NIVEL 6.2 — CAMBIO DE RESPONSABLE CONFIRMADO
                        ==================================================== */
                        

                    } else if (accion === 'quitar') {
                        

                        if (!confirm('¿Retirar este usuario de la tienda?')) {
                            
                            return;
                        }

                        await enviar('QUITAR', {
                            tiend: id,
                            usuar: usuaId
                        });

                        // SINCRONIZACIÓN | FASE 2
                        window.NURBAK_ADMI_SINC?.emitir({
                            modu: 'tiend',
                            recu: 'tiend',
                            acci: 'quit',
                            id,
                            dato: { usuar: usuaId }
                        });

                        
                    }

                    await cargarUsuarios();
                    await cargar(buscar?.value || '');
                } catch (error) {
                    
                    alert(error.message);
                } finally {
                    boton.disabled = false;
                }
            });

            mini.querySelector('[data-tiend-asignar]')?.addEventListener('click', async () => {
                await abrirAsignar(id, mini, cargarUsuarios);
            });

            mini.querySelector('[data-tiend-usuarios-cerrar]')?.addEventListener('click', () => {
                window.NURBAK_MINI.cerrar(mini);
            });

            // SINCRONIZACIÓN | FASE 2
            window.NURBAK_ADMI_SINC?.onCambio(
                () => void cargarUsuarios(),
                {recu:'usuar'},
                mini
            );

            await cargarUsuarios();

            return mini;
        };

        /* ========================================================
           NIVEL 4.1 — ALMACENES
        ======================================================== */

        const abrirAlmas = async id => {
            if (!window.NURBAK_MINI?.abrir) {
                throw new Error('La mini-ventana no está disponible.');
            }

            const datosTienda = await pedir('PORID', { id });
            const tienda = datosTienda.tienda;

            const mini = await window.NURBAK_MINI.abrir({
                titulo: `ALMACENES — ${tienda.tiend_codi}`,
                rutaVista: 'vista/admin/tiend/tiend-alma.php',
                modulo: 'admi-tiend-alma',
                clase: 'admi-tiend-mini admi-tiend-alma-mini'
            });

            const lista = mini.querySelector('[data-tiend-alma-lista]');
            const tit = mini.querySelector('[data-tiend-alma-tit]');
            const est = mini.querySelector('[data-tiend-alma-est]');
            const info = mini.querySelector('[data-tiend-alma-info]');
            const form = mini.querySelector('[data-tiend-alma-form]');
            const nuevo = mini.querySelector('[data-tiend-alma-nuevo]');
            const cancelar = mini.querySelector('[data-tiend-alma-cancelar]');

            if (tit) tit.textContent = `${tienda.tiend_codi} — ${tienda.tiend_nomb}`;
            if (est) {
                est.innerHTML = Number(tienda.activ) === 1
                    ? '<span class="admi-tiend-estad is-activ">TIENDA ACTIVA</span>'
                    : '<span class="admi-tiend-estad is-inactivo">TIENDA INACTIVA</span>';
            }

            const cargarAlmas = async () => {
                const datos = await pedir('ALMAS', { tiend: id });
                const almas = datos.almacenes || [];
                const principal = almas.find(a => Number(a.prin) === 1);
                const activo = principal && Number(principal.estad) === 1;

                if (info) {
                    info.textContent = almas.length
                        ? `${almas.length} almacén(es) · Principal: ${principal ? principal.nombr : 'SIN PRINCIPAL'}${principal && Number(principal.estad) !== 1 ? ' · PRINCIPAL INACTIVO' : ''}`
                        : 'La tienda todavía no tiene almacenes.';
                }

                if (!almas.length) {
                    lista.innerHTML = '<tr><td colspan="5">No hay almacenes registrados.</td></tr>';
                    return;
                }

                lista.innerHTML = almas.map(a => {
                    const esPrin = Number(a.prin) === 1;
                    const act = Number(a.estad) === 1;
                    return `
                        <tr>
                            <td>${esc(a.nombr)}</td>
                            <td>${esc(a.direc || '—')}</td>
                            <td>${esPrin
                                ? '<span class="admi-tiend-alma-tipo is-principal">PRINCIPAL</span>'
                                : '<span class="admi-tiend-alma-tipo is-secundario">SECUNDARIO</span>'}</td>
                            <td>${act
                                ? '<span class="admi-tiend-alma-estado is-activo">ACTIVO</span>'
                                : '<span class="admi-tiend-alma-estado is-inactivo">INACTIVO</span>'}</td>
                            <td>
                                <div class="admi-tiend-acciones-fila">
                                    ${!esPrin && act ? `<button type="button" data-tiend-alma-accion="principal" data-id="${a.id}">HACER PRINCIPAL</button>` : ''}
                                    <button type="button" data-tiend-alma-accion="estado" data-id="${a.id}" data-estad="${act ? 0 : 1}">
                                        ${act ? 'INACTIVAR' : 'ACTIVAR'}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            };

            const cerrarForm = () => {
                if (!form) return;
                form.reset();
                form.hidden = true;
            };

            nuevo?.addEventListener('click', () => {
                if (!form) return;
                form.hidden = false;
                form.querySelector('[name="nombr"]')?.focus();
            });

            cancelar?.addEventListener('click', cerrarForm);

            form?.addEventListener('submit', async evento => {
                evento.preventDefault();
                const boton = form.querySelector('button[type="submit"]');
                if (boton) boton.disabled = true;
                try {
                    const resultado = await enviar('ALCRE', {
                        tiend: id,
                        nombr: form.querySelector('[name="nombr"]')?.value || '',
                        descr: form.querySelector('[name="descr"]')?.value || '',
                        direc: form.querySelector('[name="direc"]')?.value || '',
                        prin: form.querySelector('[name="prin"]')?.checked ? 1 : 0
                    });

                    // SINCRONIZACIÓN | FASE 2
                    window.NURBAK_ADMI_SINC?.emitir({
                        modu: 'tiend',
                        recu: 'almac',
                        acci: 'crear',
                        id: Number(resultado.id || 0),
                        dato: { tiend: id, ...resultado }
                    });
                    cerrarForm();
                    await cargarAlmas();
                } catch (error) {
                    alert(error.message);
                } finally {
                    if (boton) boton.disabled = false;
                }
            });

            mini.addEventListener('click', async evento => {
                const boton = evento.target.closest('[data-tiend-alma-accion]');
                if (!boton || !mini.contains(boton)) return;
                const accion = boton.dataset.tiendAlmaAccion;
                const aid = Number(boton.dataset.id || 0);
                if (!aid) return;
                boton.disabled = true;
                try {
                    if (accion === 'principal') {
                        if (!confirm('¿Designar este almacén como principal?')) return;
                        await enviar('ALPRI', { id: aid });

                        // SINCRONIZACIÓN | FASE 2
                        window.NURBAK_ADMI_SINC?.emitir({
                            modu: 'tiend',
                            recu: 'almac',
                            acci: 'actua',
                            id: aid,
                            dato: { tiend: id }
                        });
                    } else if (accion === 'estado') {
                        const estadAlma = Number(boton.dataset.estad || 0);
                        if (estadAlma === 0 && Number(tienda.activ) === 1) {
                            const fila = boton.closest('tr');
                            const tipo = fila?.querySelector('.admi-tiend-alma-tipo')?.textContent?.trim() || '';
                            if (tipo === 'PRINCIPAL') {
                                alert('No se puede inactivar el almacén principal mientras la tienda está activa.');
                                return;
                            }
                        }
                        if (!confirm(estadAlma === 1 ? '¿Activar este almacén?' : '¿Inactivar este almacén?')) return;
                        await enviar('ALEST', { id: aid, estad: estadAlma });

                        // SINCRONIZACIÓN | FASE 2
                        window.NURBAK_ADMI_SINC?.emitir({
                            modu: 'tiend',
                            recu: 'almac',
                            acci: 'esta',
                            id: aid,
                            dato: { tiend: id, estad: estadAlma }
                        });
                    }
                    await cargarAlmas();
                } catch (error) {
                    alert(error.message);
                } finally {
                    boton.disabled = false;
                }
            });

            mini.querySelector('[data-tiend-alma-cerrar]')?.addEventListener('click', () => {
                window.NURBAK_MINI.cerrar(mini);
            });

            await cargarAlmas();
            return mini;
        };

        /* ========================================================
           NIVEL 5 — ASIGNAR USUARIO
        ======================================================== */

        const abrirAsignar = async (tiendId, miniPadre, recargarUsuarios) => {
            const mini = await window.NURBAK_MINI.abrir({
                titulo: 'ASIGNAR USUARIO',
                rutaVista: 'vista/admin/tiend/tiend-asign.php',
                modulo: 'admi-tiend-asignar',
                clase: 'admi-tiend-mini'
            });

            const form = mini.querySelector('[data-tiend-asignar-form]');
            const listaUsuarios = mini.querySelector('[data-tiend-usuario-lista]');
            const buscarUsuario = mini.querySelector('[data-tiend-usuario-buscar]');
            const checkResp = mini.querySelector('[data-tiend-es-resp]');
            const guardar = mini.querySelector('[data-tiend-asignar-guardar]');

            form.querySelector('[data-tiend-asignar-tiend]').value = String(tiendId);

            let usuarioSeleccionado = 0;
            let temporizador = null;

            const pintarDisponibles = usuarios => {
                if (!usuarios.length) {
                    listaUsuarios.innerHTML = '<p>No hay usuarios disponibles.</p>';
                    usuarioSeleccionado = 0;
                    guardar.disabled = true;
                    return;
                }

                listaUsuarios.innerHTML = usuarios.map(u => {
                    const codig = u.admi_codi || u.oper_codi || '—';

                    return `
                        <label class="admi-tiend-usuario-opcion">
                            <input type="radio" name="tiend_usua_sel" value="${u.id}">
                            <span>
                                <strong>${esc(u.usua_nomb)}</strong>
                                <small>${esc(codig)} · ${esc(u.usua_logi)}</small>
                            </span>
                        </label>
                    `;
                }).join('');
            };

            const cargarDisponibles = async texto => {
                try {
                    const datos = await pedir('USUARIOS_DISPONIBLES', {
                        tiend: tiendId,
                        texto: texto.trim()
                    });

                    pintarDisponibles(datos.usuarios || []);
                } catch (error) {
                    listaUsuarios.innerHTML = `<p>${esc(error.message)}</p>`;
                    guardar.disabled = true;
                }
            };

            listaUsuarios.addEventListener('change', evento => {
                if (evento.target.name !== 'tiend_usua_sel') return;
                usuarioSeleccionado = Number(evento.target.value || 0);
                guardar.disabled = usuarioSeleccionado <= 0;
            });

            buscarUsuario.addEventListener('input', () => {
                clearTimeout(temporizador);
                temporizador = setTimeout(() => {
                    cargarDisponibles(buscarUsuario.value);
                }, 220);
            });

            form.querySelector('[data-tiend-asignar-cancelar]')?.addEventListener('click', () => {
                window.NURBAK_MINI.cerrar(mini);
            }, { once: true });

            form.addEventListener('submit', async evento => {
                evento.preventDefault();

                if (usuarioSeleccionado <= 0) {
                    alert('Seleccione un usuario.');
                    return;
                }

                guardar.disabled = true;

                try {
                    await enviar('ASIGNAR', {
                        tiend: tiendId,
                        usuar: usuarioSeleccionado,
                        respo: checkResp.checked ? 1 : 0
                    });

                    // SINCRONIZACIÓN | FASE 2
                    window.NURBAK_ADMI_SINC?.emitir({
                        modu: 'tiend',
                        recu: 'tiend',
                        acci: 'asig',
                        id: tiendId,
                        dato: { usuar: usuarioSeleccionado, respo: checkResp.checked ? 1 : 0 }
                    });

                    

                    window.NURBAK_MINI.cerrar(mini);
                    await recargarUsuarios();
                    await cargar(buscar?.value || '');
                } catch (error) {
                    
                    alert(error.message);
                    guardar.disabled = false;
                }
            }, { once: true });

            await cargarDisponibles('');

            return mini;
        };

        /* ========================================================
           NIVEL 7 — ESTADO / NIVEL 8 — ELIMINAR
        ======================================================== */

        area.addEventListener('click', async evento => {
            const boton = evento.target.closest('[data-tiend-accion]');

            if (!boton || !area.contains(boton)) return;

            const accion = boton.dataset.tiendAccion;
            const id = Number(boton.dataset.id || 0);

            if (!id) return;

            try {
                boton.disabled = true;

                if (accion === 'editar') {
                    const datos = await pedir('PORID', { id });
                    await abrirForm('editar', datos.tienda);
                    return;
                }

                if (accion === 'usuarios') {
                    await abrirUsuarios(id);
                    return;
                }

                if (accion === 'almacenes') {
                    await abrirAlmas(id);
                    return;
                }

                if (accion === 'estad') {
                    const activ = Number(boton.dataset.activ || 0);

                    const texto = activ === 1
                        ? '¿Activar esta tienda?'
                        : '¿Inactivar esta tienda?';

                    if (!confirm(texto)) return;

                    await enviar('ESTADO', { id, activ });

                    // SINCRONIZACIÓN | FASE 2
                    window.NURBAK_ADMI_SINC?.emitir({
                        modu: 'tiend',
                        recu: 'tiend',
                        acci: 'esta',
                        id,
                        dato: { activ }
                    });

                    await cargar(buscar?.value || '');
                    return;
                }

                if (accion === 'eliminar') {
                    if (!confirm('¿Eliminar esta tienda? Solo se puede eliminar una tienda inactiva y sin usuarios.')) return;

                    await enviar('ELIMINAR', { id });

                    // SINCRONIZACIÓN | FASE 2
                    window.NURBAK_ADMI_SINC?.emitir({
                        modu: 'tiend',
                        recu: 'tiend',
                        acci: 'elim',
                        id
                    });

                    await cargar(buscar?.value || '');
                }
            } catch (error) {
                
                alert(error.message);
            } finally {
                boton.disabled = false;
            }
        });

        area.querySelector('[data-tiend-nueva]')?.addEventListener('click', async () => {
            try {
                await abrirForm('crear');
            } catch (error) {
                
                alert(error.message);
            }
        });

        let temporizadorBuscar = null;

        buscar?.addEventListener('input', () => {
            clearTimeout(temporizadorBuscar);
            temporizadorBuscar = setTimeout(() => {
                cargar(buscar.value);
            }, 220);
        });

        window.NURBAK_TIEND_INICIADO = true;
        

        await cargar('');
    };

    /* ============================================================
       NIVEL FINAL — PRIMERA APERTURA Y REAPERTURA

       Igual que PERM y USUA:
       - La primera ventana inicializa TIEND.
       - Cuando VENT crea otra ventana, el mismo JS debe volver a
         enlazar esa nueva estructura DOM.
       - El estad global no debe impedir la reinicialización.
    ============================================================ */
    iniciar();

    if (!window.NURBAK_TIEND_EVENTO_REGISTRADO) {
        document.addEventListener('modu:cargado', evento => {
            const detalle = evento.detail || {};

            if (detalle.rutaVista !== 'vista/admin/tiend/tiend.php') return;
            if (!detalle.modal?.querySelector('[data-admi-tiend]')) return;

            iniciar();
        });

        window.NURBAK_TIEND_EVENTO_REGISTRADO = true;
    }
})();
