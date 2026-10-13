/* ============================================================
   Ruta: publi/asset/js/categ/compr/orcex.js
   Módulo: Compras / Orden Compra Ext
   Función: Coordinador funcional de ORCEX.
============================================================ */
(() => {
    const R = 'modul/categ/compr/orcex/orcex-contr.php';

    const esc = v => String(v ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'",'&#039;');

    const ini = cont => {
        const raiz = cont.matches?.('[data-orcex]') ? cont : cont.querySelector?.('[data-orcex]');
        if (!raiz || raiz.dataset.iniciado === 'si') return;
        raiz.dataset.iniciado = 'si';

        const q = s => raiz.querySelector(s);
        const busc = q('[data-orcex-busc]');
        const cuerpo = q('[data-orcex-cuer]');
        const msg = q('[data-orcex-mens]');
        let tempo = null;

        const pedir = async (acci, params = {}, opciones = {}) => {
            const url = new URL(R, document.baseURI);
            url.searchParams.set('acci', acci);
            Object.entries(params).forEach(([k,v]) => url.searchParams.set(k, String(v ?? '')));
            const r = await fetch(url.href, {
                method: opciones.method || 'GET',
                body: opciones.body,
                cache: 'no-store',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            });
            const d = await r.json();
            if (!r.ok || !d.ok) throw new Error(d.mensaje || `HTTP ${r.status}`);
            return d;
        };

        const listar = async () => {
            msg.hidden = false;
            msg.textContent = 'Cargando órdenes...';
            try {
                const d = await pedir('lista', { busc: busc.value.trim(), limi: 50 });
                const filas = d.orcex || [];
                cuerpo.innerHTML = filas.map(x => {
                    const nume = Number(x.nume || 0);
                    const docu = nume > 0 ? String(nume).padStart(5, '0') : '—';
                    return `
                    <tr>
                        <td>N° ${esc(docu)}</td>
                        <td>${esc(x.fecha || '—')}</td>
                        <td>${esc(x.razon || '—')}</td>
                        <td>${esc(x.emiso || '—')}</td>
                        <td>${esc(x.estad || '—')}</td>
                        <td>${Number(x.total || 0).toFixed(2)}</td>
                    </tr>
                `;
                }).join('');
                msg.textContent = 'Sin registros';
                msg.hidden = filas.length > 0;
            } catch (e) {
                msg.hidden = false;
                msg.textContent = `Error: ${e instanceof Error ? e.message : String(e)}`;
                console.error('ORCEX:', e);
            }
        };

        const abrirMiniProd = async (miniPadre, agregar) => {
            const mini = await window.NURBAK_MINI.abrir({
                titulo: 'AGREGAR PRODUCTO',
                rutaVista: 'vista/categ/compr/orcex/produ-mini.php',
                modulo: 'orcex-productos',
                clase: 'orcex-prod-mini-vent'
            });
            if (!mini) throw new Error('No se pudo abrir el buscador de productos.');

            const buscar = mini.querySelector('[data-orcex-prod-buscar]');
            const info = mini.querySelector('[data-orcex-prod-info]');
            const cuerpoProd = mini.querySelector('[data-orcex-prod-resultados]');
            if (!buscar || !info || !cuerpoProd) throw new Error('No se encontró el buscador de productos.');

            let rows = [];
            let indice = 0;
            let timer = null;

            const pintar = () => {
                cuerpoProd.innerHTML = rows.map((r,i) => `
                    <tr data-orcex-prod-fila="${i}" class="${i === indice ? 'is-seleccionada' : ''}">
                        <td>${esc(r.motor || '—')}</td>
                        <td>${esc(r.model || '—')}</td>
                        <td>${esc(r.codig || '—')}</td>
                        <td>${esc(r.descr || '—')}</td>
                        <td>${esc(r.origi || '—')}</td>
                        <td>${esc(r.medid || '—')}</td>
                        <td>${esc(r.super || '—')}</td>
                        <td>${esc(r.anio || '—')}</td>
                        <td>${Number(r.pvent || 0).toFixed(2)}</td>
                    </tr>
                `).join('');
                info.textContent = rows.length ? `${rows.length} resultado(s).` : 'Sin resultados.';
            };

            const buscarDatos = async () => {
                const d = await pedir('prod', { busc: buscar.value.trim(), limi: 20 });
                rows = d.produ || [];
                indice = 0;
                pintar();
            };

            const seleccionar = () => {
                const r = rows[indice];
                if (!r) return;
                agregar({
                    produ: Number(r.id),
                    descr: String(r.descr || ''),
                    canti: 1,
                    preun: 0
                });
                window.NURBAK_MINI.cerrar(mini);
                miniPadre?.querySelector('[data-orcex-can-ult]')?.focus();
            };

            buscar.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => buscarDatos().catch(e => { info.textContent = e.message; }), 220);
            });

            mini.addEventListener('keydown', evento => {
                if (evento.key === 'ArrowDown') {
                    evento.preventDefault();
                    evento.stopPropagation();
                    if (rows.length) { indice = Math.min(indice + 1, rows.length - 1); pintar(); }
                } else if (evento.key === 'ArrowUp') {
                    evento.preventDefault();
                    evento.stopPropagation();
                    if (rows.length) { indice = Math.max(indice - 1, 0); pintar(); }
                } else if (evento.key === 'Enter') {
                    evento.preventDefault();
                    evento.stopPropagation();
                    seleccionar();
                } else if (evento.key === 'Escape') {
                    evento.preventDefault();
                    evento.stopPropagation();
                    window.NURBAK_MINI.cerrar(mini);
                }
            }, true);

            cuerpoProd.addEventListener('mousedown', evento => {
                const fila = evento.target.closest('[data-orcex-prod-fila]');
                if (!fila) return;
                indice = Number(fila.dataset.orcexProdFila);
                pintar();
                buscar.focus();
            });

            await buscarDatos();
            buscar.focus();
        };

        const abrirMini = async () => {
            if (!window.NURBAK_MINI?.abrir) throw new Error('La mini-ventana no está disponible.');

            const mini = await window.NURBAK_MINI.abrir({
                titulo: 'NUEVA ORDEN DE COMPRA',
                rutaVista: 'vista/categ/compr/orcex-mini.php',
                modulo: 'orcex-nueva',
                clase: 'orcex-mini-vent'
            });

            const form = mini.querySelector('[data-orcex-form]');
            if (!form) throw new Error('No se encontró el formulario de ORCEX.');
            const qf = s => form.querySelector(s);
            const nume = qf('[data-orcex-nume]');
            const ruc = qf('[data-orcex-ruc]');
            const rucMens = qf('[data-orcex-ruc-mens]');
            const prov = qf('[data-orcex-prove]');
            const razon = qf('[data-orcex-razon]');
            const direc = qf('[data-orcex-direc]');
            const fecha = qf('[data-orcex-fecha]');
            const deta = qf('[data-orcex-deta]');
            const total = qf('[data-orcex-total]');
            const emiso = qf('[data-orcex-emiso]');
            const emisoNomb = qf('[data-orcex-emiso-nomb]');
            const emisoMens = qf('[data-orcex-emiso-mens]');
            let detalle = [];
            let rucTimer = null;
            let emisoTimer = null;
            let rucReq = 0;
            let emisoReq = 0;
            let ultimaRuc = '';
            let ultimoEmiso = '';

            fecha.value = new Date().toISOString().slice(0,10);

            const cargarNumero = async () => {
                const d = await pedir('opciones');
                if (nume) {
                    const sig = Number(d.numero?.siguiente || 0);
                    nume.value = sig > 0 ? `N° ${String(sig).padStart(5,'0')}` : '';
                }
            };

            const pintar = () => {
                deta.innerHTML = detalle.map((x,i) => `
                    <tr>
                        <td><input type="number" min="0.001" step="0.001" value="${esc(x.canti)}" data-orcex-can="${i}" data-orcex-can-ult></td>
                        <td><input type="text" maxlength="300" value="${esc(x.descr)}" data-orcex-des="${i}"></td>
                        <td><input type="number" min="0" step="0.01" value="${Number(x.preun).toFixed(2)}" data-orcex-pre="${i}"></td>
                        <td>${Number(x.canti * x.preun).toFixed(2)}</td>
                        <td><button type="button" data-orcex-del="${i}" aria-label="Eliminar">×</button></td>
                    </tr>
                `).join('');
                total.textContent = detalle.reduce((s,x) => s + Number(x.canti) * Number(x.preun), 0).toFixed(2);
            };

            const sincronizar = () => {
                deta.querySelectorAll('[data-orcex-can]').forEach(e => {
                    const i = Number(e.dataset.orcexCan);
                    if (detalle[i]) detalle[i].canti = Number(e.value);
                });
                deta.querySelectorAll('[data-orcex-des]').forEach(e => {
                    const i = Number(e.dataset.orcexDes);
                    if (detalle[i]) detalle[i].descr = e.value;
                });
                deta.querySelectorAll('[data-orcex-pre]').forEach(e => {
                    const i = Number(e.dataset.orcexPre);
                    if (detalle[i]) detalle[i].preun = Number(e.value);
                });
                deta.querySelectorAll('tr').forEach((fila, i) => {
                    const dato = detalle[i];
                    const cel = fila.children[3];
                    if (dato && cel) cel.textContent = (Number(dato.canti) * Number(dato.preun)).toFixed(2);
                });
                total.textContent = detalle.reduce((s,x) => s + Number(x.canti) * Number(x.preun), 0).toFixed(2);
            };

            const buscarRuc = async () => {
                const valor = ruc.value.replace(/\D/g,'').slice(0,11);
                ruc.value = valor;
                if (valor.length !== 11 || valor === ultimaRuc) return;

                const req = ++rucReq;
                ultimaRuc = valor;
                rucMens.textContent = 'Buscando...';
                prov.value = '';
                razon.value = '';
                direc.value = '';

                try {
                    /*
                     * ORCEX usa el coordinador propio de RUC.
                     * El backend conserva la cadena existente:
                     * PROVE local -> OperaClienteExterno.
                     * No se llama directamente a Operaciones desde JS.
                     */
                    const d = await pedir('ruc', {}, { method: 'POST', body: new URLSearchParams({ ruc: valor }) });
                    if (req !== rucReq || ruc.value !== valor) return;

                    const x = d.prove || {};
                    prov.value = x.id || '';
                    razon.value = x.razon || '';
                    direc.value = x.direc || '';

                    if (d.nuevo && x.id) {
                        window.NURBAK_CATE_SINC?.emitir({
                            modu: 'orcex',
                            recu: 'prove',
                            acci: 'crear',
                            id: Number(x.id),
                            dato: x
                        });
                    }

                    rucMens.textContent = d.fuente === 'externa'
                        ? 'Proveedor registrado en PROVE desde fuente externa.'
                        : 'Proveedor encontrado.';
                } catch (e) {
                    if (req !== rucReq) return;
                    ultimaRuc = '';
                    prov.value = '';
                    razon.value = '';
                    direc.value = '';
                    rucMens.textContent = e.message;
                }
            };

            ruc.addEventListener('input', () => {
                clearTimeout(rucTimer);
                rucReq++;
                ultimaRuc = '';
                const valor = ruc.value.replace(/\D/g,'').slice(0,11);
                ruc.value = valor;
                if (valor.length === 11) {
                    rucMens.textContent = 'Buscando...';
                    rucTimer = setTimeout(() => void buscarRuc(), 250);
                } else {
                    rucMens.textContent = '';
                }
            });
            ruc.addEventListener('blur', () => {
                clearTimeout(rucTimer);
                void buscarRuc();
            });

            const buscarEmisor = async () => {
                const codig = emiso.value.trim().toUpperCase().replace(/\s/g,'');
                emiso.value = codig;
                if (!/^\d{3}$|^A\d{3}$/.test(codig) || codig === ultimoEmiso) return;
                const req = ++emisoReq;
                ultimoEmiso = codig;
                emisoNomb.textContent = 'Buscando...';
                if (emisoMens) emisoMens.textContent = '';
                try {
                    const d = await pedir('buscar-emisor', { codig });
                    if (req !== emisoReq || emiso.value !== codig) return;
                    if (!d.encontrado || !d.emisor) throw new Error('Usuario no encontrado o inactivo.');
                    emisoNomb.textContent = d.emisor.display || '';
                    if (emisoMens) emisoMens.textContent = 'Usuario encontrado.';
                } catch (e) {
                    ultimoEmiso = '';
                    emisoNomb.textContent = '';
                    if (emisoMens) emisoMens.textContent = e.message;
                }
            };

            emiso.addEventListener('input', () => {
                clearTimeout(emisoTimer);
                emisoReq++;
                ultimoEmiso = '';
                emisoNomb.textContent = '';
                emisoTimer = setTimeout(() => void buscarEmisor(), 250);
            });
            emiso.addEventListener('change', () => { clearTimeout(emisoTimer); void buscarEmisor(); });

            qf('[data-orcex-agre]').addEventListener('click', () => {
                abrirMiniProd(mini, item => {
                    detalle.push(item);
                    pintar();
                }).catch(e => alert(e.message));
            });

            qf('[data-orcex-libr]').addEventListener('click', () => {
                detalle.push({ produ: null, descr: '', canti: 1, preun: 0 });
                pintar();
                const i = detalle.length - 1;
                qf(`[data-orcex-des="${i}"]`)?.focus();
            });

            deta.addEventListener('change', sincronizar);
            deta.addEventListener('input', sincronizar);
            deta.addEventListener('click', e => {
                const btn = e.target.closest('[data-orcex-del]');
                if (!btn) return;
                detalle.splice(Number(btn.dataset.orcexDel), 1);
                pintar();
            });

            qf('[data-orcex-canc]').addEventListener('click', () => window.NURBAK_MINI.cerrar(mini), { once: true });

            form.addEventListener('submit', async e => {
                e.preventDefault();
                sincronizar();
                const canEmi = /^\d{3}$|^A\d{3}$/.test(emiso.value.trim().toUpperCase());
                if (!fecha.value || !canEmi) {
                    alert('La fecha y el emisor son obligatorios.');
                    return;
                }
                if (detalle.some(x => !x.descr.trim() || Number(x.canti) <= 0 || Number(x.preun) < 0)) {
                    alert('Revise descripción, cantidad y P.U. de los detalles.');
                    return;
                }
                const boton = form.querySelector('button[type="submit"]');
                if (boton) boton.disabled = true;
                try {
                    const d = await pedir('agreg', {}, {
                        method: 'POST',
                        body: new URLSearchParams({
                            prove: prov.value,
                            razon: razon.value.trim(),
                            ruc: ruc.value.trim(),
                            direc: direc.value.trim(),
                            emiso: emiso.value.trim().toUpperCase(),
                            fecha: fecha.value,
                            detox: JSON.stringify(detalle.map(x => ({ produ:x.produ, descr:x.descr, canti:x.canti, preun:x.preun })))
                        })
                    });
                    if (nume) nume.value = d.nume ? `N° ${String(d.nume).padStart(5,'0')}` : '';
                    window.NURBAK_CATE_SINC?.emitir({ modu:'orcex', recu:'orcex', acci:'crear', id:Number(d.id||0), dato:d });
                    window.NURBAK_MINI.cerrar(mini);
                } catch (e) {
                    alert(e.message);
                } finally {
                    if (boton) boton.disabled = false;
                }
            });

            await cargarNumero();
            razon.focus();
        };

        q('[data-orcex-nuev]').addEventListener('click', () => abrirMini().catch(e => alert(e.message)));
        busc.addEventListener('input', () => { clearTimeout(tempo); tempo = setTimeout(() => listar().catch(() => {}), 250); });
        window.NURBAK_CATE_SINC?.onCambio(() => listar().catch(() => {}), { recu:'orcex' }, cont);
        listar();
    };

    document.addEventListener('modu:cargado', e => {
        if (e.detail?.modulo === 'orden-compra-ext') ini(e.detail.modal);
    });
    document.querySelectorAll('[data-orcex]').forEach(ini);
})();
