/* ============================================================
   Ruta: publi/asset/js/admin/usuar/usuar.js
   Función: Coordinar la interfaz y las operaciones de USUA.

   🔹 Nivel 1: Localizar la interfaz USUA.
   🔹 Nivel 2: Consultar y mostrar usuarios.
   🔹 Nivel 3: Abrir MINI para crear o editar.
   🔹 Nivel 4: Guardar y actualizar usuarios.
   🔹 Nivel 5: Suspender o activar usuarios.
   🔹 Nivel 6: Eliminar usuarios.
   🔹 Nivel Final: Mantener independiente la lógica de USUA.
============================================================ */

(() => {
    const rutaCont = 'modul/admin/usuar/usuar-contr.php';
        const iniciar = async () => {
        const area = document.querySelector('[data-admi-usua]');
        if (!area) return;

        // Una instancia de USUA solo se inicializa una vez.
        // Al reabrir la ventana, el DOM es nuevo y puede inicializarse de nuevo.
        if (area.dataset.usuaIniciado === 'si') return;
        area.dataset.usuaIniciado = 'si';
const lista = area.querySelector('[data-usua-lista]');
        const buscar = area.querySelector('[data-usua-buscar]');
        const botonNuevo = area.querySelector('[data-usua-accion="nuevo"]');

        const obtener = async (accion = 'LISTAR', parametros = {}) => {
            const url = new URL(rutaCont, document.baseURI);
            url.searchParams.set('accion', accion);
            Object.entries(parametros).forEach(([clave, valor]) => url.searchParams.set(clave, valor));

            

            try {
                const respuesta = await fetch(url.href, { method: 'GET', credentials: 'same-origin', cache: 'no-store' });
                

                const texto = await respuesta.text();
                let datos = null;
                try { datos = texto ? JSON.parse(texto) : null; } catch (_) {
                    
                    throw new Error(`Respuesta inválida del servidor (HTTP ${respuesta.status}).`);
                }

                

                if (!respuesta.ok) throw new Error(datos?.mensaje || `HTTP ${respuesta.status}`);
                if (!datos?.ok) throw new Error(datos?.mensaje || 'Respuesta no válida.');

                
                return datos;
            } catch (error) {
                
                throw error;
            }
        };

        const enviar = async (accion, formulario = null, extras = {}) => {
            const datos = formulario ? new FormData(formulario) : new FormData();
            datos.set('accion', accion);
            Object.entries(extras).forEach(([clave, valor]) => datos.set(clave, String(valor)));

            const url = new URL(rutaCont, document.baseURI);
            url.searchParams.set('accion', accion);
            url.searchParams.set('_ts', String(Date.now()));

            

            try {
                const respuesta = await fetch(url.href, {
                    method: 'POST',
                    body: datos,
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                

                const texto = await respuesta.text();
                let resultado = null;
                try {
                    resultado = texto.trim() !== '' ? JSON.parse(texto) : null;
                } catch (_) {
                    
                    throw new Error(`Respuesta inválida del servidor (HTTP ${respuesta.status}).`);
                }

                

                if (!respuesta.ok) throw new Error(resultado?.mensaje || `HTTP ${respuesta.status}`);
                if (resultado?.ok !== true) throw new Error(resultado?.mensaje || 'El servidor no confirmó la operación.');

                
                return resultado;
            } catch (error) {
                
                throw error;
            }
        };

        const pintarUsuarios = usuarios => {
            if (!lista) return;

            if (!usuarios.length) {
                lista.innerHTML = '<tr><td colspan="6">No se encontraron usuarios.</td></tr>';
                return;
            }

            lista.innerHTML = usuarios.map(usuario => {
                const codig = usuario.admi_codi ?? usuario.oper_codi ?? '—';
                const activ = Number(usuario.activ) === 1;
                const accionEstado = activ ? 'SUSPENDER' : 'ACTIVAR';

                return `
                    <tr data-usua-id="${usuario.id}">
                        <td>${codig}</td>
                        <td>${usuario.usua_logi ?? ''}</td>
                        <td>${[usuario.usua_nomb, usuario.usua_apel].filter(Boolean).join(' ')}</td>
                        <td>${usuario.rol_nomb ?? ''}</td>
                        <td>${activ ? 'ACTIVO' : 'SUSPENDIDO'}</td>
                        <td>
                            <div class="admi-usua-acciones-fila">
                                <button type="button" data-usua-fila="editar" data-usua-id="${usuario.id}">EDITAR</button>
                                <button type="button" data-usua-fila="estad" data-usua-id="${usuario.id}">${accionEstado}</button>
                                <button type="button" data-usua-fila="eliminar" data-usua-id="${usuario.id}">ELIMINAR</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        };

        const cargarUsuarios = async (texto = '') => {
            try {
                const limpio = texto.trim();
                
                const datos = limpio === ''
                    ? await obtener('LISTAR')
                    : await obtener('BUSCAR', { texto: limpio });
                const usuarios = datos.usuarios || [];
                pintarUsuarios(usuarios);
                
            } catch (error) {
                
                if (lista) lista.innerHTML = '<tr><td colspan="6">No se pudo cargar la lista.</td></tr>';
            }
        };

        const cargarRoles = async mini => {
            const select = mini?.querySelector('[data-usua-campo="rol"]');
            if (!select) return;
            const datos = await obtener('ROLES');
            select.innerHTML = '<option value="">Seleccionar...</option>';
            (datos.roles || []).forEach(rol => {
                const opcion = document.createElement('option');
                opcion.value = rol.id;
                opcion.textContent = rol.rol_nomb;
                select.appendChild(opcion);
            });
        };

        const cargarCodigo = async mini => {
            const campo = mini?.querySelector('[data-usua-campo="codi"]');
            const select = mini?.querySelector('[data-usua-campo="rol"]');
            if (!campo || !select) return;

            const actualizar = async () => {
                const nombreRol = (select.selectedOptions[0]?.textContent || '').trim().toUpperCase();
                campo.value = '';
                if (!nombreRol) return;

                const tipo = nombreRol === 'ADMINISTRADOR'
                    ? 'ADMINISTRADOR'
                    : nombreRol === 'USUARIO' ? 'OPERADOR' : '';
                if (!tipo) return;

                const datos = await obtener('CODIGO', { tipo });
                campo.value = datos.codig || '';
            };

            select.addEventListener('change', actualizar);
            await actualizar();
        };

        const prepararFormulario = (mini, modo) => {
            const form = mini?.querySelector('[data-usua-form]');
            if (!form) throw new Error('No se encontró el formulario de usuario.');
            form.dataset.usuaModo = modo;

            const clave = form.querySelector('[data-usua-campo="clave"]');
            const rol = form.querySelector('[data-usua-campo="rol"]');
            if (clave) {
                clave.required = modo === 'crear';
                if (modo === 'editar') clave.value = '';
            }
            /* El rol puede cambiarse también durante la edición. */
            if (rol) rol.disabled = false;
            return form;
        };

        const abrirMini = async ({ modo, usuario = null }) => {
            const titulo = modo === 'crear' ? 'NUEVO USUARIO' : 'EDITAR USUARIO';
            const modulo = modo === 'crear' ? 'admi-usua-nuevo' : 'admi-usua-editar';

            const mini = await window.NURBAK_MINI.abrir({
                titulo,
                rutaVista: 'vista/admin/usuar/usuar-form.php',
                modulo,
            });

            const form = prepararFormulario(mini, modo);
            const codig = form.querySelector('[data-usua-campo="codi"]');
            const logi = form.querySelector('[data-usua-campo="logi"]');
            const nomb = form.querySelector('[data-usua-campo="nombr"]');
            const corr = form.querySelector('[data-usua-campo="corre"]');
            const dni = form.querySelector('[data-usua-campo="dni"]');
            const apel = form.querySelector('[data-usua-campo="apell"]');
            const celu = form.querySelector('[data-usua-campo="celul"]');
            const rol = form.querySelector('[data-usua-campo="rol"]');
            let ultimoDniConsultado = '';

            const solicitarDni = async () => {
                const docum = (dni?.value || '').replace(/\D/g, '');
                if (docum.length !== 8) {
                    alert('El DNI debe tener 8 dígitos.');
                    dni?.focus();
                    return;
                }

                if (docum === ultimoDniConsultado) return;
                ultimoDniConsultado = docum;

                try {
                    const datos = await obtener('DATOS_DNI', { dni: docum });
                    const persona = datos.datos || {};

                    if (dni) dni.value = persona.usua_dni || docum;
                    if (nomb) nomb.value = persona.usua_nomb || '';
                    if (apel) apel.value = persona.usua_apel || '';
                    if (celu && persona.usua_celu) celu.value = persona.usua_celu;

                    

                    if (nomb) nomb.focus();
                } catch (error) {
                    ultimoDniConsultado = '';
                    
                    alert(error.message);
                }
            };

            /* El DNI se consulta automáticamente al completar los 8 dígitos. */
            dni?.addEventListener('input', () => {
                const docum = (dni.value || '').replace(/\D/g, '').slice(0, 8);
                dni.value = docum;
                if (docum.length === 8) solicitarDni();
            });

            await cargarRoles(mini);

            if (modo === 'crear') {
                const opcionUsuario = [...rol.options].find(o => o.textContent.trim().toUpperCase() === 'USUARIO');
                if (opcionUsuario) rol.value = opcionUsuario.value;
                await cargarCodigo(mini);
            } else {
                codig.value = usuario.admi_codi ?? usuario.oper_codi ?? '';
                logi.value = usuario.usua_logi ?? '';
                nomb.value = usuario.usua_nomb ?? '';
                apel.value = usuario.usua_apel ?? '';
                dni.value = usuario.usua_dni ?? '';
                celu.value = usuario.usua_celu ?? '';
                corr.value = usuario.usua_corr ?? '';
                rol.value = String(usuario.rol_id ?? '');

                /* En edición, mostrar el código existente y actualizar la
                   previsualización si se cambia el rol. */
                const codigoOriginal = codig.value;
                const rolOriginal = String(usuario.rol_id ?? '');

                rol.addEventListener('change', async () => {
                    if (String(rol.value) === rolOriginal) {
                        codig.value = codigoOriginal;
                        return;
                    }

                    const nombreRol = (rol.selectedOptions[0]?.textContent || '').trim().toUpperCase();
                    const tipo = nombreRol === 'ADMINISTRADOR'
                        ? 'ADMINISTRADOR'
                        : nombreRol === 'USUARIO' ? 'OPERADOR' : '';

                    if (!tipo) {
                        codig.value = '';
                        return;
                    }

                    try {
                        const datos = await obtener('CODIGO', { tipo });
                        codig.value = datos.codig || '';
                    } catch (error) {
                        codig.value = '';
                        throw error;
                    }
                });
            }

            form.addEventListener('submit', async evento => {
                evento.preventDefault();
                const boton = form.querySelector('button[type="submit"]');
                if (boton) boton.disabled = true;

                try {
                    const accion = modo === 'crear' ? 'CREAR' : 'ACTUALIZAR';
                    await enviar(accion, form, modo === 'editar' ? { id: usuario.id } : {});

                    // SINCRONIZACIÓN | FASE 2
                    window.NURBAK_ADMI_SINC?.emitir({
                        modu: 'usuar',
                        recu: 'usuar',
                        acci: modo === 'crear' ? 'crear' : 'actua',
                        id: Number(usuario?.id || 0)
                    });
                    
                    window.NURBAK_MINI.cerrar(mini);
                    await cargarUsuarios(buscar?.value || '');
                } catch (error) {
                    
                    alert(error.message);
                } finally {
                    if (boton) boton.disabled = false;
                }
            }, { once: true });

            return mini;
        };

        const solicitarMiniNuevo = async () => {
            if (!window.NURBAK_MINI?.listo) {
                const quitar = window.NURBAK_SINC?.on('mini:listo', () => {
                    quitar();
                    void solicitarMiniNuevo();
                }) || (() => {});
                return;
            }
            try {
                await abrirMini({ modo: 'crear' });
            } catch (error) {
                
                alert(error.message);
            }
        };

        const editarUsuario = async id => {
            if (!window.NURBAK_MINI?.listo) return;
            try {
                const datos = await obtener('PORID', { id });
                await abrirMini({ modo: 'editar', usuario: datos.usuario });
            } catch (error) {
                
                alert(error.message);
            }
        };

        const cambiarEstado = async (id, fila) => {
            const usuario = await obtener('PORID', { id });
            const dato = usuario.usuario;
            const activoActual = Number(dato.activ) === 1;
            const nuevoActivo = activoActual ? 0 : 1;
            const accionTexto = activoActual ? 'suspender' : 'activar';

            if (!confirm(`¿Deseas ${accionTexto} al usuario ${dato.usua_logi}?`)) return;

            const resultado = await enviar('ESTADO', null, {
                id,
                activ: nuevoActivo,
            });

            
            if (fila) fila.dataset.usuaActivo = String(nuevoActivo);

            // SINCRONIZACIÓN | FASE 2
            window.NURBAK_ADMI_SINC?.emitir({
                modu: 'usuar',
                recu: 'usuar',
                acci: 'esta',
                id: Number(id || 0),
                dato: { activ: nuevoActivo }
            });

            await cargarUsuarios(buscar?.value || '');
            return resultado;
        };

        const eliminarUsuario = async id => {
            const datos = await obtener('PORID', { id });
            const usuario = datos.usuario;
            const codig = usuario.admi_codi ?? usuario.oper_codi ?? '—';

            if (!confirm(`¿Deseas ELIMINAR al usuario ${usuario.usua_logi} (${codig})?\n\nEsta acción eliminará físicamente la cuenta.`)) return;

            await enviar('ELIMINAR', null, { id });

            // SINCRONIZACIÓN | FASE 2
            window.NURBAK_ADMI_SINC?.emitir({
                modu: 'usuar',
                recu: 'usuar',
                acci: 'elim',
                id: Number(id || 0)
            });

            await cargarUsuarios(buscar?.value || '');
        };

        botonNuevo?.addEventListener('click', solicitarMiniNuevo);

        lista?.addEventListener('click', async evento => {
            const boton = evento.target.closest('[data-usua-fila]');
            if (!boton) return;

            const id = Number(boton.dataset.usuaId || 0);
            const accion = boton.dataset.usuaFila;
            if (id <= 0) return;

            boton.disabled = true;
            try {
                if (accion === 'editar') await editarUsuario(id);
                if (accion === 'estad') await cambiarEstado(id, boton.closest('tr'));
                if (accion === 'eliminar') await eliminarUsuario(id);
            } catch (error) {
                
                alert(error.message);
            } finally {
                boton.disabled = false;
            }
        });

        buscar?.addEventListener('input', () => cargarUsuarios(buscar.value));

        window.NURBAK_USUA = window.NURBAK_USUA || {};
        window.NURBAK_USUA.iniciar = iniciar;
        window.NURBAK_USUA.cargarUsuarios = cargarUsuarios;
        window.NURBAK_USUA.abrirNuevo = solicitarMiniNuevo;

        
        
        
        cargarUsuarios();
    };

    // Primera apertura: inicializa la vista que ya existe.
    iniciar();

    // Reapertura: Vent reutiliza el JS cargado, pero crea un nuevo DOM.
    // Este evento vuelve a inicializar USUA sobre la nueva ventana.
    document.addEventListener('modu:cargado', evento => {
        const detalle = evento.detail || {};
        if (detalle.rutaVista !== 'vista/admin/usuar/usuar.php') return;
        if (!detalle.modal?.querySelector('[data-admi-usua]')) return;
        iniciar();
    });
})();
