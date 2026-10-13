/* ============================================================
   Ruta: publi/asset/js/admin/permi/permi.js
   Función: Coordinar la interfaz del submódulo PERM.

   NIVEL 1: Consultar usuarios y permisos.
   NIVEL 2: Pintar la tabla.
   NIVEL 3: Mostrar DETALLE mediante hover.
   NIVEL 4: Abrir MINI con CREAR o EDITAR.
   NIVEL 5: Buscar y seleccionar usuario dentro de MINI.
   NIVEL 6: Asignar, guardar y eliminar asignaciones.
   NIVEL FINAL: Mantener PERM independiente.
============================================================ */

(() => {
    const rutaCont = 'modul/admin/permi/permi-contr.php';
    const grupos = [
        { nombr: 'ADMINISTRACIÓN', codigos: ['CONFG','PERMG','PERMV','TIENG','TIENV','USUAG','USUAV'] },
        { nombr: 'CATÁLOGO', codigos: ['CATEG','EQUIG','PRODG','PRODV','VEHIG','VEHIV'] },
        { nombr: 'INVENTARIO', codigos: ['ALMAG','ALMAV','MOVIG','MOVIV','STOCG','STOCV'] },
        { nombr: 'COMPRAS', codigos: ['OCOMG','OCOMV','ORCEG','ORCEV','PROVG','PROVV','RECEG'] },
        { nombr: 'VENTAS', codigos: ['CLIEG','CLIEV','COTIG','DEVOG','NOVEG','NOVEV','VENTG'] },
        { nombr: 'FACTURACIÓN', codigos: ['BOLEG','BOLEV','FACTG','FACTV','GUIAG','NCREG','NDEBG','SUNAG'] },
        { nombr: 'PAGOS', codigos: ['CAJAG','CREDG','CREDV','PAGOG','PAGOV'] },
        { nombr: 'REPORTES', codigos: ['AUDIV','INFOV'] },
    ];

        const esc = valor => String(valor ?? '')
        .replaceAll('&','&amp;').replaceAll('<','&lt;')
        .replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');

    const iniciar = async () => {
        const area = document.querySelector('[data-admi-perm]');
        if (!area || area.dataset.permIniciado === 'si') return;
        area.dataset.permIniciado = 'si';

        const lista = area.querySelector('[data-perm-lista]');
        const buscar = area.querySelector('[data-perm-buscar]');

        const pedir = async (accion, parametros = {}) => {
            const url = new URL(rutaCont, document.baseURI);
            url.searchParams.set('accion', accion);
            Object.entries(parametros).forEach(([k,v]) => url.searchParams.set(k, v));
            
            try {
                const respuesta = await fetch(url.href, { cache:'no-store', credentials:'same-origin' });
                
                const texto = await respuesta.text();
                let datos;
                try { datos = JSON.parse(texto); } catch (_) {
                    
                    throw new Error(`Respuesta inválida del servidor (HTTP ${respuesta.status}).`);
                }
                
                if (!respuesta.ok || datos.ok !== true) throw new Error(datos.mensaje || `HTTP ${respuesta.status}`);
                
                return datos;
            } catch (error) {
                
                throw error;
            }
        };

        const enviar = async (form) => {
            const datos = new FormData(form);
            datos.set('accion','GUARDAR');
            
            try {
                const respuesta = await fetch(`${rutaCont}?accion=GUARDAR&_ts=${Date.now()}`, {
                    method:'POST', body:datos, credentials:'same-origin', cache:'no-store',
                    headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
                });
                
                const texto = await respuesta.text();
                let resultado;
                try { resultado = JSON.parse(texto); } catch (_) {
                    
                    throw new Error(`Respuesta inválida del servidor (HTTP ${respuesta.status}).`);
                }
                
                if (!respuesta.ok || resultado.ok !== true) throw new Error(resultado.mensaje || 'No se pudo guardar.');
                
                return resultado;
            } catch (error) {
                
                throw error;
            }
        };

        let catalogo = [];
        let usuarios = [];

        const cargarCatalogo = async () => {
            if (catalogo.length) return catalogo;
            catalogo = (await pedir('PERMISOS')).permisos || [];
            return catalogo;
        };

        const permisoPorCodigo = codig => catalogo.find(p => p.perm_codi === codig);

        /* ========================================================
           NIVEL 6 — VER
           Mostrar únicamente los permisos realmente asignados.
           El catálogo completo NO se presenta en VER.
        ======================================================== */
        const detalleHtml = usuario => {
            const asignados = new Map(
                (usuario.permisos || [])
                    .filter(p => Number(p.activ) === 1)
                    .map(p => [p.perm_codi, p])
            );

            if (Number(usuario.acceso_total) === 1) {
                return '<div class="admi-perm-total">ACCESO TOTAL POR ROL</div>';
            }

            const bloques = grupos.map(grupo => {
                const permisos = grupo.codigos
                    .map(codig => asignados.get(codig))
                    .filter(Boolean);

                if (!permisos.length) return '';

                return `<div class="admi-perm-grupo">
                    <div class="admi-perm-grupo-titulo">${esc(grupo.nombr)}</div>
                    ${permisos.map(p => `<span class="admi-perm-item is-asignado">${esc(p.perm_nomb)}</span>`).join('')}
                </div>`;
            }).join('');

            return bloques || '<div class="admi-perm-sin-asignaciones">SIN PERMISOS ASIGNADOS</div>';
        };

        const pintar = async () => {
            if (!lista) return;
            try {
                await cargarCatalogo();
                const texto = buscar?.value.trim() || '';
                const datos = await pedir('LISTAR', texto ? {texto} : {});
                usuarios = datos.usuarios || [];

                if (!usuarios.length) {
                    lista.innerHTML = '<tr><td colspan="6">No se encontraron usuarios.</td></tr>';
                    return;
                }

                lista.innerHTML = usuarios.map(u => {
                    const codig = u.admi_codi || u.oper_codi || '—';
                    const total = (u.permisos || []).filter(p => Number(p.activ) === 1).length;
                    return `<tr data-perm-usua="${u.id}" tabindex="0" title="Seleccionar usuario">
                        <td>${esc(codig)}</td>
                        <td>${esc(u.usua_logi)}</td>
                        <td>${esc(u.rol_nomb)}</td>
                        <td>${Number(u.acceso_total) === 1 ? 'TOTAL' : total}</td>
                        <td>
                            <div class="admi-perm-detalle">
                                <span class="admi-perm-ver">VER</span>
                                <div class="admi-perm-hover">${detalleHtml(u)}</div>
                            </div>
                        </td>
                        <!-- ==================================================
                             NIVEL 4 — ACCIONES
                             PERM solo administra su configuración.
                             El estad ACTIVO/INACTIVO pertenece a USUARIOS.
                        =================================================== -->
                        <td>
                            <div class="admi-perm-acciones-fila">
                                <button type="button" class="admi-perm-accion admi-perm-accion-editar" data-perm-fila="editar" aria-label="Editar permisos" data-perm-id="${u.id}">EDITAR</button>
                                <button type="button" class="admi-perm-accion admi-perm-accion-eliminar" data-perm-fila="eliminar" aria-label="Eliminar permisos" data-perm-id="${u.id}">ELIMINAR</button>
                            </div>
                        </td>
                    </tr>`;
                }).join('');
            } catch (error) {
                
                if (lista) lista.innerHTML = '<tr><td colspan="6">No se pudo cargar la lista.</td></tr>';
            }
        };

        const construirMini = async usuario => {
            await cargarCatalogo();
            const permisosHtml = grupos.map(grupo => {
                const disponibles = grupo.codigos.map(permisoPorCodigo).filter(Boolean);
                if (!disponibles.length) return '';
                return `<section class="admi-perm-mini-grupo" data-perm-grupo="${esc(grupo.nombr)}">
                    <div class="admi-perm-mini-grupo-titulo">${esc(grupo.nombr)} <span data-perm-contador>0/${disponibles.length}</span></div>
                    ${disponibles.map(p => `<label class="admi-perm-mini-opcion">
                        <input type="checkbox" name="perm_ids[]" value="${p.id}" data-perm-check>
                        <span>${esc(p.perm_nomb)}</span>
                    </label>`).join('')}
                </section>`;
            }).join('');

            const usuarioId = usuario?.id ? String(usuario.id) : '';
            const usuarioTexto = usuario
                ? `<strong>USUARIO:</strong> ${esc(usuario.usua_logi)}<br><strong>CÓDIGO:</strong> ${esc(usuario.admi_codi || usuario.oper_codi || '—')}<br><strong>ROL:</strong> ${esc(usuario.rol_nomb)}`
                : 'Seleccione un usuario para realizar la asignación.';

            return `<form data-perm-form data-perm-usuario="${esc(usuarioId)}">
                <div class="admi-perm-mini-busqueda">
                    <input type="search" placeholder="Buscar código o usuario..." data-perm-mini-buscar autocomplete="off">
                    <button type="button" data-perm-mini-buscar-btn>BUSCAR</button>
                    <div class="admi-perm-mini-resultados" data-perm-mini-resultados></div>
                </div>
                <div class="admi-perm-mini-usuario" data-perm-mini-resultado>${usuarioTexto}</div>
                <input type="hidden" name="usuar" value="${esc(usuarioId)}" data-perm-mini-id>
                <div data-perm-mini-permisos>${permisosHtml}</div>
                <div class="admi-perm-mini-acciones">
                    <button type="submit" disabled data-perm-mini-guardar>GUARDAR</button>
                </div>
                <div class="admi-perm-mini-mensaje" data-perm-mini-mensaje></div>
            </form>`;
        };

        const actualizarContadores = form => {
            form.querySelectorAll('[data-perm-grupo]').forEach(grupo => {
                const checks = [...grupo.querySelectorAll('[data-perm-check]')];
                const marcados = checks.filter(c => c.checked).length;
                const contador = grupo.querySelector('[data-perm-contador]');
                if (contador) contador.textContent = `${marcados}/${checks.length}`;
            });
        };

        const abrirMiniPermisos = async (usuario = null, modo = 'crear') => {
            if (!window.NURBAK_MINI?.abrir) {
                throw new Error('La mini-ventana no está disponible.');
            }

            const contenido = await construirMini(usuario);
            const mini = await window.NURBAK_MINI.abrir({
                titulo: modo === 'editar' ? 'EDITAR PERMISOS' : 'ASIGNAR PERMISOS',
                contenido,
                modulo: modo === 'editar' ? 'admi-perm-editar' : 'admi-perm-asignar',
                clase: 'admi-perm-mini'
            });
            conectarMini(mini, usuario || null);
            return mini;
        };

        const abrirCrear = async () => {
            try {
                await abrirMiniPermisos(null, 'crear');
            } catch (error) {
                
                alert(error.message);
            }
        };

        const editarPermisos = async id => {
            const usuario = usuarios.find(u => String(u.id) === String(id));
            if (!usuario) throw new Error('No se encontró el usuario seleccionado.');
            await abrirMiniPermisos(usuario, 'editar');
        };

        /* ========================================================
           NIVEL 8 — ELIMINAR ASIGNACIONES
           Esta operación es independiente de GUARDAR.
           Siempre utiliza POST y solo afecta usape.
        ======================================================== */
        const eliminarPermisos = async id => {
            const usuario = usuarios.find(u => String(u.id) === String(id));
            if (!usuario) throw new Error('No se encontró el usuario seleccionado.');

            if (Number(usuario.acceso_total) === 1) {
                
                alert('Este usuario tiene acceso total por su rol. No requiere asignaciones individuales.');
                return;
            }

            const nombr = usuario.usua_logi || usuario.usua_nomb || 'usuario';
            if (!confirm(`¿Eliminar todas las asignaciones de permisos de ${nombr}?`)) {
                
                return;
            }

            

            const datos = new FormData();
            datos.set('accion', 'ELIMINAR');
            datos.set('usuar', String(id));

            const url = new URL(rutaCont, document.baseURI);
            url.searchParams.set('accion', 'ELIMINAR');
            url.searchParams.set('_ts', String(Date.now()));

            const respuesta = await fetch(url.href, {
                method: 'POST',
                body: datos,
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const texto = await respuesta.text();
            

            let resultado;
            try {
                resultado = texto.trim() !== '' ? JSON.parse(texto) : null;
            } catch (_) {
                throw new Error(`Respuesta inválida del servidor (HTTP ${respuesta.status}).`);
            }

            if (!respuesta.ok || resultado?.ok !== true) {
                throw new Error(resultado?.mensaje || `No se pudo eliminar (HTTP ${respuesta.status}).`);
            }

            // SINCRONIZACIÓN | FASE 2
            window.NURBAK_ADMI_SINC?.emitir({
                modu: 'permi',
                recu: 'permi',
                acci: 'elim',
                id: Number(id || 0)
            });

            await pintar();
        };

        const conectarMini = (mini, usuarioInicial = null) => {
            const form = mini?.querySelector('[data-perm-form]');
            if (!form || form.dataset.permConectado === 'si') return;
            form.dataset.permConectado = 'si';

            const campoId = form.querySelector('[data-perm-mini-id]');
            const identidad = form.querySelector('[data-perm-mini-resultado]');
            const buscadorMini = form.querySelector('[data-perm-mini-buscar]');
            const botonBuscarMini = form.querySelector('[data-perm-mini-buscar-btn]');
            const resultadosMini = form.querySelector('[data-perm-mini-resultados]');

            const aplicarUsuario = usuario => {
                if (!usuario) return;
                form.dataset.permUsuario = String(usuario.id);
                if (campoId) campoId.value = String(usuario.id);
                if (identidad) identidad.innerHTML = `<strong>USUARIO:</strong> ${esc(usuario.usua_logi)}<br><strong>CÓDIGO:</strong> ${esc(usuario.admi_codi || usuario.oper_codi || '—')}<br><strong>ROL:</strong> ${esc(usuario.rol_nomb)}`;

                const ids = new Set((usuario.permisos || []).map(p => Number(p.id)));
                form.querySelectorAll('[data-perm-check]').forEach(check => check.checked = ids.has(Number(check.value)));
                actualizarContadores(form);

                const boton = form.querySelector('[data-perm-mini-guardar]');
                const mensaje = form.querySelector('[data-perm-mini-mensaje]');
                const accesoTotal = Number(usuario.acceso_total) === 1;
                if (boton) boton.disabled = accesoTotal;
                if (mensaje) mensaje.textContent = accesoTotal ? 'Este rol tiene acceso total. No requiere asignaciones individuales.' : '';
                if (resultadosMini) resultadosMini.innerHTML = '';
            };

            const buscarUsuarioMini = async () => {
                const texto = buscadorMini?.value.trim() || '';
                if (!resultadosMini) return;
                if (!texto) {
                    resultadosMini.innerHTML = '';
                    return;
                }
                try {
                    const datos = await pedir('LISTAR', {texto});
                    const encontrados = datos.usuarios || [];
                    resultadosMini.innerHTML = encontrados.length
                        ? encontrados.map(u => `<button type="button" class="admi-perm-mini-resultado-item" data-perm-mini-seleccionar="${u.id}">${esc(u.admi_codi || u.oper_codi || '—')} — ${esc(u.usua_logi)} — ${esc(u.rol_nomb)}</button>`).join('')
                        : '<span class="admi-perm-mini-sin-resultados">No se encontraron usuarios.</span>';
                } catch (error) {
                    resultadosMini.innerHTML = `<span class="admi-perm-mini-sin-resultados">${esc(error.message)}</span>`;
                }
            };

            if (usuarioInicial) aplicarUsuario(usuarioInicial);

            botonBuscarMini?.addEventListener('click', buscarUsuarioMini);
            buscadorMini?.addEventListener('input', buscarUsuarioMini);

            resultadosMini?.addEventListener('click', e => {
                const boton = e.target.closest('[data-perm-mini-seleccionar]');
                if (!boton) return;
                const usuario = usuarios.find(u => String(u.id) === String(boton.dataset.permMiniSeleccionar));
                if (usuario) aplicarUsuario(usuario);
            });

            form.addEventListener('change', e => {
                if (e.target.matches('[data-perm-check]')) actualizarContadores(form);
            });

            form.addEventListener('submit', async e => {
                e.preventDefault();
                const boton = form.querySelector('[data-perm-mini-guardar]');
                const mensaje = form.querySelector('[data-perm-mini-mensaje]');
                if (!form.dataset.permUsuario) {
                    if (mensaje) mensaje.textContent = 'Seleccione un usuario antes de guardar.';
                    return;
                }
                boton.disabled = true;
                try {
                    await enviar(form);
                    const permId = Number(form.querySelector('[data-perm-mini-id]')?.value || 0);
                    // SINCRONIZACIÓN | FASE 2
                    window.NURBAK_ADMI_SINC?.emitir({
                        modu: 'permi',
                        recu: 'permi',
                        acci: 'actua',
                        id: permId
                    });
                    if (mensaje) mensaje.textContent = 'Permisos guardados correctamente.';
                    await pintar();
                } catch (error) {
                    if (mensaje) mensaje.textContent = error.message;
                } finally {
                    if (Number(form.querySelector('[data-perm-mini-id]')?.value || 0) > 0) {
                        const usuario = usuarios.find(u => String(u.id) === String(form.querySelector('[data-perm-mini-id]')?.value));
                        boton.disabled = Number(usuario?.acceso_total) === 1;
                    }
                }
            });
        };

        // SINCRONIZACIÓN | FASE 2
        const modal = area.closest('[data-modal]') || area;
        window.NURBAK_ADMI_SINC?.onCambio(
            () => void pintar(),
            {recu:'usuar'},
            modal
        );

        lista?.addEventListener('click', async e => {
            const accion = e.target.closest('[data-perm-fila]');
            if (accion) {
                const id = Number(accion.dataset.permId || 0);
                if (id <= 0) return;
                accion.disabled = true;
                try {
                    if (accion.dataset.permFila === 'editar') await editarPermisos(id);
                    if (accion.dataset.permFila === 'eliminar') await eliminarPermisos(id);
                } catch (error) {
                    
                    alert(error.message);
                } finally {
                    accion.disabled = false;
                }
                return;
            }

            const fila = e.target.closest('tr[data-perm-usua]');
            if (!fila || e.target.closest('.admi-perm-detalle')) return;
            lista.querySelectorAll('tr.is-seleccionado').forEach(f => f.classList.remove('is-seleccionado'));
            fila.classList.add('is-seleccionado');
        });

        lista?.addEventListener('keydown', e => {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            const fila = e.target.closest('tr[data-perm-usua]');
            if (!fila) return;
            e.preventDefault();
            lista.querySelectorAll('tr.is-seleccionado').forEach(f => f.classList.remove('is-seleccionado'));
            fila.classList.add('is-seleccionado');
        });

        area.querySelector('[data-perm-accion="crear"]')?.addEventListener('click', abrirCrear);
        buscar?.addEventListener('input', pintar);

        
        await pintar();
    };

    // Primera apertura: inicializa la vista que ya existe.
    iniciar();

    // Reapertura: Vent crea un nuevo DOM, pero el JS puede estar ya cargado.
    // Volvemos a inicializar PERM únicamente sobre la nueva ventana.
    document.addEventListener('modu:cargado', evento => {
        const detalle = evento.detail || {};
        if (detalle.rutaVista !== 'vista/admin/permi/permi.php') return;
        if (!detalle.modal?.querySelector('[data-admi-perm]')) return;
        iniciar();
    });
})();
