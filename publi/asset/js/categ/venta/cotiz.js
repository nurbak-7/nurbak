/* ============================================================
   Ruta: publi/asset/js/categ/venta/cotiz.js
   Módulo: Ventas / Cotización

   RESPONSABILIDAD:
   Coordinar la consulta principal de Cotización y cargar el
   formulario únicamente dentro de MINI.
============================================================ */
(() => {
    const archivos = [
        'cotiz.js',
        'docum.js',
        'clien.js',
        'vende.js',
        'produ.js',
        'tabla.js',
        'total.js',
        'accio.js'
    ];

    window.NURBAK_COTIZ_MODULOS = window.NURBAK_COTIZ_MODULOS || {};

    /* INTEGRACIÓN | FASE 3: compartir cargas pendientes entre ventanas. */
    const pendientes = new Map();

    const cargar = archivo => {
        if (pendientes.has(archivo)) {
            return pendientes.get(archivo);
        }

        const tarea = new Promise((resolve, reject) => {
        const selector = `[data-cotiz-js="${archivo}"]`;
        const existente = document.querySelector(selector);
        const nom = archivo.replace('.js','');

        if (existente) {
            if (existente.dataset.cotizEstado === 'cargado') {
                if (typeof window.NURBAK_COTIZ_MODULOS[nom] === 'function') {
                    resolve();
                    return;
                }
            }

            if (existente.dataset.cotizEstado === 'cargando') {
                existente.addEventListener('load', () => resolve(), {once:true});
                existente.addEventListener('error', () => reject(new Error(`No se pudo cargar ${archivo}.`)), {once:true});
                return;
            }

            existente.remove();
        }

        const script = document.createElement('script');
        script.src = `publi/asset/js/categ/venta/cotiz/${archivo}`;
        script.dataset.cotizJs = archivo;
        script.dataset.cotizEstado = 'cargando';
        script.addEventListener('load', () => {
            script.dataset.cotizEstado = 'cargado';
            if (typeof window.NURBAK_COTIZ_MODULOS[nom] !== 'function') {
                reject(new Error(`No se registró el submódulo ${archivo}.`));
                return;
            }
            resolve();
        }, {once:true});
        script.addEventListener('error', () => {
            script.remove();
            reject(new Error(`No se pudo cargar ${archivo}.`));
        }, {once:true});
        document.body.appendChild(script);
        });

        pendientes.set(archivo, tarea);
        return tarea.finally(() => pendientes.delete(archivo));
    };

    const formu = async ventana => {
        const root = ventana?.querySelector('[data-cotiz]');
        if (!root) return;
if (ventana.dataset.cotizIniciado === 'si' || ventana.dataset.cotizIniciando === 'si') return;
        ventana.dataset.cotizIniciando = 'si';

        const limp = [];
        let cerrado = false;

        const registrarLimpieza = fn => {
            if (typeof fn !== 'function') return () => {};
            limp.push(fn);
            return () => {
                const i = limp.indexOf(fn);
                if (i >= 0) limp.splice(i,1);
            };
        };

        const ctx = {
            root,
            ventana,
            q: selector => root.querySelector(selector),
            state: {
                detalles: [],
                cotizId: 0,
                clienId: 0,
                clien: null,
                vendeId: 0,
                fecha: '',
                vigia: '',
                serie: 'C001',
                numero: 0,
                placa: '',
                subto: 0,
                igv: 0,
                total: 0
            },
            api: {},
            R: 'modul/categ/venta/cotiz/cotiz-contr.php',
            esc: valor => String(valor ?? '')
                .replaceAll('&','&amp;')
                .replaceAll('<','&lt;')
                .replaceAll('>','&gt;')
                .replaceAll('"','&quot;')
                .replaceAll("'",'&#039;'),
            registrarLimpieza,
            pedir: async (params = {}, opciones = {}) => {
                const url = new URL(ctx.R, document.baseURI);
                Object.entries(params).forEach(([k,v]) => url.searchParams.set(k,String(v ?? '')));
                const respuesta = await fetch(url.href, {
                    method: opciones.method || 'GET',
                    body: opciones.body,
                    cache: 'no-store',
                    credentials: 'same-origin',
                    headers: {Accept:'application/json'}
                });
                const datos = await respuesta.json();
                if (!respuesta.ok || datos.ok !== true) {
                    throw new Error(datos.mensaje || `HTTP ${respuesta.status}`);
                }
                return datos;
            },
            cerrar: motivo => {
                if (cerrado) return;
                cerrado = true;
                for (let i = limp.length - 1; i >= 0; i--) {
                    try { limp[i](motivo); } catch (_) {}
                }
                limp.length = 0;
                ventana.dataset.cotizCerrado = 'si';
            }
        };

        let cierreAutorizado = false;

        const control = ventana.querySelector('[data-modal-accion="cerrar"], [data-mini-accion="cerrar"]');
const cerrarModal = det => {
            if (det.modulo !== 'cotiz') return;
            if (det.modal && det.modal !== ventana) return;
            if (!cierreAutorizado) return;
            ctx.cerrar('modal');
        };

        const salirPagina = () => {
            ctx.cerrar('pagehide');
        };

        const cerrar = evento => {
            if (cerrado) return;
            evento.preventDefault();
            evento.stopImmediatePropagation();

            if (ventana.matches('[data-mini-ventana]')) {
                cierreAutorizado = true;
                ctx.api.cerrarVentana?.();
                return;
            }

            ctx.api.cancelar?.();
        };

        const quitarCierreModal = window.NURBAK_SINC?.on('modal:cerrando', cerrarModal) || (() => {});
        window.addEventListener('pagehide', salirPagina, true);
        control?.addEventListener('click', cerrar, true);

        registrarLimpieza(() => {
            quitarCierreModal();
            window.removeEventListener('pagehide', salirPagina, true);
            control?.removeEventListener('click', cerrar, true);
        });

        ctx.api.cerrarVentana = () => {
            if (cerrado) return;

            cierreAutorizado = true;

            if (ventana.matches('[data-mini-ventana]')) {
                const cerrarMini = window.NURBAK_MINI?.cerrar;

                if (typeof cerrarMini !== 'function') {
                    cierreAutorizado = false;
                    console.error(
                        'Cotización: MINI no expone su cierre propietario.'
                    );
                    return;
                }

                cerrarMini(ventana);
                return;
            }

            const cerrarModalPropietario =
                window.NURBAK_MODAL_ACCIO?.cerrarModal;

            if (typeof cerrarModalPropietario !== 'function') {
                cierreAutorizado = false;
                console.error(
                    'Cotización: MODAL no expone su cierre propietario.'
                );
                return;
            }

            cerrarModalPropietario(ventana);
        };

        try {
            const esperarToast = () => new Promise((resolve, reject) => {
                if (window.NURBAK_TOAST?.cargar) {
                    resolve();
                    return;
                }
                const sel = '[data-layo-toast="publi/asset/js/core/toast.js"]';
                const exis = document.querySelector(sel);
                if (exis) {
                    const fin = () => window.NURBAK_TOAST?.cargar ? resolve() : reject(new Error('Coordinador Toast no disponible.'));
                    exis.addEventListener('load', fin, {once:true});
                    exis.addEventListener('error', () => reject(new Error('No se pudo cargar el coordinador Toast.')), {once:true});
                    return;
                }
                reject(new Error('Coordinador Toast no disponible.'));
            });

            await esperarToast();
            ctx.api.toas = await window.NURBAK_TOAST.cargar('cotiz', ventana);

            /* INTEGRACIÓN | FASE 3: cargar recursos en paralelo;
               la inicialización conserva el orden funcional existente. */
            await Promise.all(archivos.map(cargar));

            for (const archivo of archivos) {
                if (cerrado) break;
                const nom = archivo.replace('.js','');
                const fn = window.NURBAK_COTIZ_MODULOS[nom];
                if (typeof fn !== 'function') {
                    throw new Error(`No se registró el submódulo ${archivo}.`);
                }
                await Promise.resolve(fn(ctx));
            }

            const edid = Number(root.dataset.cotizId || 0);
            if (!cerrado && edid > 0) {
                const datos = await ctx.pedir({accion:'obtener',id:edid});
                const cot = datos.datos?.cotiz || {};
                const det = datos.datos?.detalles || [];

                ctx.state.cotizId = Number(cot.id || edid);
                ctx.state.clienId = Number(cot.clien || 0);
                ctx.state.vendeId = Number(cot.vende || 0);
                ctx.state.fecha = cot.fecha || '';
                ctx.state.vigia = cot.vigia || '';
                ctx.state.serie = cot.serie || 'C001';
                ctx.state.numero = Number(cot.numco || 0);
                ctx.state.placa = cot.placa || '';
                ctx.state.detalles = det.map(item => ({
                    produ:Number(item.produ || 0),
                    codig:item.codig || '',
                    descr:item.descr || '',
                    canti:Number(item.canti || 0),
                    preun:Number(item.preun || 0)
                }));

                ctx.q('[data-cotiz-fecha]').value = ctx.state.fecha;
                ctx.q('[data-cotiz-vigia]').value = ctx.state.vigia;
                ctx.q('[data-cotiz-serie]').value = ctx.state.serie;
                ctx.q('[data-cotiz-numero]').value = ctx.api.formatoNumero?.(ctx.state.numero) || String(ctx.state.numero).padStart(4,'0');
                ctx.q('[data-cotiz-docum]').value = cot.docum || '';
                ctx.q('[data-cotiz-razon]').value = cot.razon || '';
                ctx.q('[data-cotiz-direc]').value = cot.direc || '';
                ctx.q('[data-cotiz-placa]').value = cot.placa || '';
                ctx.q('[data-cotiz-vende-cod]').value = cot.vende_cod || '';
                ctx.q('[data-cotiz-vende-cod]').dataset.codig = cot.vende_cod || '';
                ctx.q('[data-cotiz-vende]').value = String(ctx.state.vendeId);
                ctx.api.sincronizarCliente?.();
                ctx.api.dibujarDetalles?.();
                ctx.api.calcularTotales?.();
            }

            if (!cerrado) {
                ventana.dataset.cotizIniciado = 'si';
}
        } catch (error) {
            const msg = root.querySelector('[data-cotiz-mensaje]');
            if (msg) msg.textContent = error?.message || 'No se pudo inicializar Cotización.';
ctx.cerrar('error');
            console.error('Cotización:', error);
        } finally {
            delete ventana.dataset.cotizIniciando;
        }
    };

    const fech = valor => {
        const base = String(valor || '');
        if (!/^\d{4}-\d{2}-\d{2}$/.test(base)) return base;
        const [anio,mes,dia] = base.split('-');
        return `${dia}/${mes}/${anio}`;
    };

    const esc = valor => String(valor ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'",'&#039;');

    const lista = async ventana => {
        const root = ventana?.querySelector('[data-cotiz-list]');
        if (!root) return;
        if (ventana.dataset.cotizListaIniciado === 'si' || ventana.dataset.cotizListaIniciando === 'si') return;
        ventana.dataset.cotizListaIniciando = 'si';

        const q = selector => root.querySelector(selector);
        const busc = q('[data-cotiz-list-busc]');
        const nuev = q('[data-cotiz-list-nuevo]');
        const cuerp = q('[data-cotiz-list-cuerpo]');
        const mensa = q('[data-cotiz-list-mensaje]');
        let timer = null;
        let ocupado = false;

        const pedir = async (params = {}) => {
            const url = new URL('modul/categ/venta/cotiz/cotiz-contr.php', document.baseURI);
            Object.entries(params).forEach(([k,v]) => url.searchParams.set(k,String(v ?? '')));
            const respuesta = await fetch(url.href,{
                method:'GET',
                cache:'no-store',
                credentials:'same-origin',
                headers:{Accept:'application/json'}
            });
            const datos = await respuesta.json();
            if (!respuesta.ok || datos.ok !== true) {
                throw new Error(datos.mensaje || `HTTP ${respuesta.status}`);
            }
            return datos;
        };

        const abrir = async (id = 0) => {
            if (!window.NURBAK_MINI?.abrir) {
                throw new Error('MINI no está disponible.');
            }

            const mini = await window.NURBAK_MINI.abrir({
                titulo: id > 0 ? 'Editar cotización' : 'Nueva cotización',
                rutaVista: id > 0
                    ? `vista/categ/venta/cotiz/form.php?id=${encodeURIComponent(id)}`
                    : 'vista/categ/venta/cotiz/form.php',
                modulo: id > 0 ? `cotiz-form-${id}` : 'cotiz-form-nuev',
                clase: 'cotiz-mini'
            });

            if (!mini) throw new Error('No se pudo abrir la cotización.');

            document.dispatchEvent(new CustomEvent('cotiz:form-listo',{
                detail:{mini,id:Number(id || 0)}
            }));
        };

        const situa = fila => {
            if (fila.situ) return fila.situ;
            if (fila.estad === 'cancelada') return 'Cancelada';
            return fila.vigia < new Date().toISOString().slice(0,10) ? 'Vencida' : 'Vigente';
        };

        const listar = async () => {
            if (ocupado) return;
            ocupado = true;
            mensa.hidden = false;
            mensa.textContent = 'Cargando cotizaciones...';
            cuerp.innerHTML = '';
            try {
                const datos = await pedir({
                    accion:'listar',
                    buscar:busc.value.trim(),
                    limite:50
                });
                const filas = datos.cotizaciones || [];
                cuerp.innerHTML = filas.map(fila => `
                    <tr>
                        <td class="cotiz-lista-num">${esc(fila.serie)}-${esc(String(fila.numco))}</td>
                        <td class="cotiz-lista-cliente">
                            <strong>${esc(fila.razon || '—')}</strong>
                            <span class="cotiz-lista-fechas">${esc(fech(fila.fecha))}<span aria-hidden="true"> | </span>${esc(fech(fila.vigia))}</span>
                        </td>
                        <td class="cotiz-lista-estado">${esc(situa(fila))}</td>
                        <td class="cotiz-lista-total">${Number(fila.total || 0).toFixed(2)}</td>
                        <td class="cotiz-lista-acc">
                            <button type="button" data-cotiz-list-abrir="${esc(fila.id)}">Editar</button>
                            <button type="button" data-cotiz-list-ver="${esc(fila.id)}">Ver</button>
                        </td>
                    </tr>
                `).join('');
                mensa.textContent = busc.value.trim() ? 'Sin coincidencias' : 'Sin registros';
                mensa.hidden = filas.length > 0;
            } catch (error) {
                mensa.hidden = false;
                mensa.textContent = 'No se pudieron cargar las cotizaciones.';
console.error('Cotización:',error);
            } finally {
                ocupado = false;
            }
        };

        nuev?.addEventListener('click',()=>{
            void abrir().catch(error=>{
                mensa.hidden=false;
                mensa.textContent=error.message;
            });
        });

        cuerp?.addEventListener('click',evento=>{
            const ver=evento.target.closest('[data-cotiz-list-ver]');
            if(ver){
                const id=Number(ver.dataset.cotizListVer || 0);
                if(id<=0)return;
                const url=new URL('modul/pdf/pdf.php',document.baseURI);
                url.searchParams.set('modu','venta');
                url.searchParams.set('docu','cotiz');
                url.searchParams.set('id',String(id));
                const pest=window.open(url.href,'_blank','noopener');
                if(!pest){
                    mensa.hidden=false;
                    mensa.textContent='El navegador bloqueó la ventana del PDF.';
                }
return;
            }

            const boton=evento.target.closest('[data-cotiz-list-abrir]');
            if(!boton)return;
            void abrir(Number(boton.dataset.cotizListAbrir)).catch(error=>{
                mensa.hidden=false;
                mensa.textContent=error.message;
            });
        });

        busc?.addEventListener('input',()=>{
            clearTimeout(timer);
            timer=setTimeout(()=>void listar(),250);
        });

        const actua = () => void listar();
        document.addEventListener('cotiz:actualizada',actua);

        // SINCRONIZACIÓN | FASE 2
        window.NURBAK_CATE_SINC?.onCambio(() => void listar(), {recu:'clien'}, ventana);
        window.NURBAK_CATE_SINC?.onCambio(() => void listar(), {recu:'produ'}, ventana);
        window.NURBAK_CATE_SINC?.onCambio(() => void listar(), {recu:'cotiz'}, ventana);

        const limpi = () => {
            clearTimeout(timer);
            document.removeEventListener('cotiz:actualizada',actua);
        };

        const cerr = det => {
            if (det?.modal !== ventana) return;
            limpi();
            quitarCierreLista();
        };
        const quitarCierreLista = window.NURBAK_SINC?.on('modal:cerrando', cerr) || (() => {});
        ventana.dataset.cotizListaIniciado='si';
        delete ventana.dataset.cotizListaIniciando;
await listar();
    };

    document.addEventListener('modu:cargado', evento => {
        const modulo = evento.detail?.modulo;
        if (modulo === 'cotiz') {
            const modal = evento.detail?.modal;
            if (modal?.querySelector('[data-cotiz-list]')) {
                void lista(modal);
            }
            return;
        }
        if (modulo === 'cotiz-form') {
            void formu(evento.detail?.modal);
        }
    });

    document.addEventListener('cotiz:form-listo', evento => {
        const mini = evento.detail?.mini;
        if (!mini) return;
        void formu(mini);
    });
})();
