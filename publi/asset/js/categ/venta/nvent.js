/* ============================================================
   Ruta: publi/asset/js/categ/venta/nvent.js
   Módulo: Ventas / Nota de Venta

   RESPONSABILIDAD:
   Coordinar el listado y el formulario MINI propio de Nota de Venta.
   Usa los coordinadores MINI globales y los repositorios funcionales
   existentes de clientes, catálogo e inventario; no invoca Operaciones.
============================================================ */
(() => {
    const R = 'modul/categ/venta/nvent/nvent-contr.php';
    const esc = v => String(v ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');

    const iniciar = c => {
        const r = c.matches?.('[data-nven]') ? c : c.querySelector?.('[data-nven]');
        if (!r || r.dataset.iniciado === 'si') return;
        r.dataset.iniciado = 'si';
        const q = s => r.querySelector(s);
        const b=q('[data-nven-busc]'), tb=q('[data-nven-cuerpo]'), m=q('[data-nven-mensaje]'), nv=q('[data-nven-nueva]');

        const listar = async () => {
            if (m) {m.hidden=false;m.textContent='Cargando notas...';}
            try {
                const x=await fetch(`${R}?accion=listar&buscar=${encodeURIComponent(b?.value.trim()||'')}&limite=50`,{cache:'no-store',credentials:'same-origin',headers:{Accept:'application/json'}});
                const d=await x.json();
                if(!x.ok||!d.ok)throw new Error(d.mensaje||'No se pudieron consultar las notas.');
                const a=d.notas||[];
                if(tb)tb.innerHTML=a.map(x=>{
                    const docu=`${x.serie||''}-${String(x.numer??'').padStart(6,'0')}`;
                    return `<tr><td>${esc(x.id)}</td><td>${esc(docu)}</td><td>${esc(x.razon)}</td><td>${esc(x.estad)}</td><td>${esc(x.fecha)}</td><td>${Number(x.total).toFixed(2)}</td><td><button type="button" data-nven-editar="${Number(x.id)}">Editar</button> <button type="button" data-nven-ver="${Number(x.id)}">Ver</button></td></tr>`;
                }).join('');
                if(m){m.textContent='Sin registros';m.hidden=a.length>0;}
            }catch(e){if(m){m.hidden=false;m.textContent='No se pudieron cargar las notas.';}console.error('Notas de Venta:',e);}
        };

        const abrir = async (id=0) => {
            if(typeof window.NURBAK_MINI?.abrir!=='function')throw new Error('El coordinador MINI no está disponible.');
            const mini=await window.NURBAK_MINI.abrir({titulo:id?'Editar Nota de Venta':'Nueva Nota de Venta',rutaVista:'vista/categ/venta/nvent/form.php',modulo:id?`nvent-form-edit-${id}`:'nvent-form-nuev',clase:'nvent-mini'});
            if(!mini)throw new Error('No se pudo abrir Nota de Venta.');
            if(!mini.querySelector('[data-nven-form]'))throw new Error('La vista de Nota de Venta no se cargó correctamente.');
            mini.dataset.nvenEditId=String(id||0);
            document.dispatchEvent(new CustomEvent('nvent:form-listo',{detail:{mini}}));
        };
        nv?.addEventListener('click',()=>void abrir().catch(e=>{if(m){m.hidden=false;m.textContent=e.message;}console.error('Nota de Venta:',e);}));
        tb?.addEventListener('click',e=>{const ed=e.target.closest('[data-nven-editar]'),ve=e.target.closest('[data-nven-ver]');if(ed){void abrir(Number(ed.dataset.nvenEditar)).catch(err=>{if(m){m.hidden=false;m.textContent=err.message;}console.error('Editar Nota de Venta:',err);});return;}if(ve){const id=Number(ve.dataset.nvenVer);if(id>0){const w=window.open(`modul/pdf/pdf.php?modu=venta&docu=nvent&id=${encodeURIComponent(id)}`,'_blank');if(!w&&m){m.hidden=false;m.textContent='El navegador bloqueó la ventana del PDF.';}}}});
        let timer;
        b?.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(()=>void listar(),250);});
        window.NURBAK_NVENT=window.NURBAK_NVENT||{};
        window.NURBAK_NVENT.recargar=listar;
        listar();
    };

    document.addEventListener('modu:cargado',e=>{if(e.detail?.modulo==='notas-venta')iniciar(e.detail.modal);});
    document.querySelectorAll('[data-nven]').forEach(iniciar);

    document.addEventListener('nvent:form-listo', evento => {
        const mini=evento.detail?.mini;
        if(!mini||mini.dataset.nvenFormIniciado==='si')return;
        const form=mini.querySelector('[data-nven-formulario]');
        if(!form)return;
        mini.dataset.nvenFormIniciado='si';
        const q=s=>mini.querySelector(s);
        const msg=q('[data-nven-form-mensaje]'),alma=q('[data-nven-almac]'),btnProd=q('[data-nven-agregar]'),cuerpo=q('[data-nven-detalles]'),btnEmit=q('[data-nven-emitir]');
        const doc=q('[data-nven-docum]'),tip=q('[data-nven-tipd]'),raz=q('[data-nven-razon]'),dire=q('[data-nven-direc]'),clien=q('[data-nven-clien]');
        const vcod=q('[data-nven-vende-cod]'),vende=q('[data-nven-vende]'),placa=q('[data-nven-placa]'),fecha=q('[data-nven-fecha]'),serie=q('[data-nven-serie]'),numer=q('[data-nven-numer]'),formp=q('[data-nven-formp]'),medi=q('[data-nven-medi]'),obser=q('[data-nven-obser]');
        const lineas=[];
        const editId=Number(mini.dataset.nvenEditId||0);
        let miniProducto=null, solicitudCliente=0, timerCliente=null, ultimoCliente='', timerVende=null, solicitudProd=0, timerProd=null, fuente=null, cerrado=false, ocupado=false, guardado=false, apiToast=null, toastPendiente=null;
        const pedir=async(params={},op={})=>{
            const method=op.method||'GET',url=new URL(R,document.baseURI);let body;
            if(method==='GET')Object.entries(params).forEach(([k,v])=>url.searchParams.set(k,String(v??'')));
            else body=new URLSearchParams(Object.entries(params).map(([k,v])=>[k,String(v??'')]));
            const res=await fetch(url.href,{method,body,cache:'no-store',credentials:'same-origin',headers:{Accept:'application/json'}});
            const d=await res.json();if(!res.ok||d.ok!==true)throw new Error(d.mensaje||`HTTP ${res.status}`);return d;
        };
        const esperarCoordinadorToast=()=>new Promise((resolve,reject)=>{
            if(window.NURBAK_TOAST?.cargar){resolve();return;}
            const script=document.querySelector('[data-layo-toast="publi/asset/js/core/toast.js"]');
            if(!script){reject(new Error('El coordinador Toast global no está cargado.'));return;}
            const fin=()=>window.NURBAK_TOAST?.cargar?resolve():reject(new Error('El coordinador Toast no quedó disponible.'));
            script.addEventListener('load',fin,{once:true});
            script.addEventListener('error',()=>reject(new Error('No se pudo cargar el coordinador Toast.')),{once:true});
        });
        const obtenerToast=()=>{
            if(apiToast)return Promise.resolve(apiToast);
            if(!toastPendiente){
                toastPendiente=(async()=>{
                    await esperarCoordinadorToast();
                    const api=await window.NURBAK_TOAST.cargar('nvent',mini);
                    if(!api)throw new Error('El Toast de Nota de Venta no quedó conectado.');
                    apiToast=api;return api;
                })().finally(()=>{toastPendiente=null;});
            }
            return toastPendiente;
        };
        const notificar=async(texto,tipo='err',api=null)=>{
            if(msg)msg.textContent=String(texto||'');
            const toast=api||await obtenerToast().catch(()=>null);
            toast?.resu?.(texto,tipo);
        };
        // El tipo visible sigue la regla de Operaciones; Nota de Venta conserva su propia serie N001.
        const tipoDoc=valor=>{const d=String(valor||'').replace(/\D/g,'');return d.length===8?'DNI':d.length===11?'RUC':'';};
        const tipoComp=valor=>{
            const d=String(valor||'').replace(/\D/g,'');
            if(d.length===11)return 'FACTURA';
            if(d.length===8||d.length===0)return 'BOLETA';
            return '';
        };
        const setTipo=()=>{
            const comp=tipoComp(doc?.value);
            if(tip){
                for(const op of tip.options)op.selected=op.value===comp;
                if(!comp)tip.selectedIndex=-1;
            }
            return tipoDoc(doc?.value);
        };
        const formPlaca=valor=>{const b=String(valor||'').toUpperCase().replace(/[^A-Z0-9]/g,'').slice(0,6);return b.length>3?`${b.slice(0,3)}-${b.slice(3)}`:b;};
        const mostrarCliente=c=>{
            if(!c)return;
            setTipo();
            if(clien)clien.value=String(c.id||0);
            if(raz)raz.value=String(c.razon||c.razon_social||c.nombr||c.nombre_completo||'');
            if(dire)dire.value=String(c.direc||c.direccion||'');
            if(msg)msg.textContent='Datos del comprador cargados.';
            actualizarEstado();
        };
        const buscarCliente=async(force=false)=>{
            const d=String(doc?.value||'').replace(/\D/g,'');
            if(!tipoDoc(d)||(!force&&d===ultimoCliente&&(raz?.value.trim()||Number(clien?.value||0)>0)))return;
            const id=++solicitudCliente;
            try{
                const local=await pedir({accion:'buscar-cliente-local',docum:d});
                if(id!==solicitudCliente||String(doc.value).replace(/\D/g,'')!==d)return;
                if(local.clien){ultimoCliente=d;mostrarCliente(local.clien);return;}
                const ext=await pedir({accion:'buscar-cliente-externo',docum:d});
                if(id!==solicitudCliente||String(doc.value).replace(/\D/g,'')!==d)return;
                if(ext?.ok===true&&ext.clien){
                    ultimoCliente=d;
                    mostrarCliente({id:0,docum:d,razon:ext.clien.razon_social||ext.clien.razon||ext.clien.nombre_completo||'',direc:ext.clien.direccion||ext.clien.direc||''});
                }else{
                    ultimoCliente=d;
                    if(msg&&!raz.value.trim())msg.textContent='Documento válido; complete el nombre o razón social del comprador.';
                }
            }catch(e){
                // No fijar ultimoCliente cuando falla la consulta: al salir del campo se podrá reintentar.
                if(id!==solicitudCliente)return;
                if(msg&&!raz.value.trim())msg.textContent=`No se pudo consultar el comprador: ${e.message}. Puede completar los datos manualmente.`;
            }
        };
        const codigoValido=v=>/^\d{3}$|^A\d{3}$/.test(String(v||'').toUpperCase());
        const resolverVendedor=async()=>{
            const cod=String(vcod?.dataset.codig||vcod?.value||'').trim().toUpperCase().replace(/\s/g,'');
            if(vende?.value&&Number(vende.value)>0&&codigoValido(vcod?.dataset.codig||''))return {id:Number(vende.value),codig:vcod.dataset.codig};
            if(!codigoValido(cod))throw new Error('Ingrese un código de vendedor válido (3 dígitos o A + 3 dígitos).');
            const d=await pedir({accion:'buscar-vendedor',codig:cod});
            if(!d.encontrado||!d.vendedor)throw new Error('Vendedor no encontrado o inactivo.');
            vende.value=String(d.vendedor.id);vcod.value=d.vendedor.display||'';vcod.dataset.codig=d.vendedor.codig||cod;
            actualizarEstado();return d.vendedor;
        };
        const validarContexto=(requiereVendedor=true)=>{
            const d=String(doc?.value||'').replace(/\D/g,'');
            if(!tipoDoc(d))return 'Ingrese un DNI de 8 dígitos o un RUC de 11 dígitos.';
            if(!raz?.value.trim())return 'Ingrese el nombre o razón social del comprador.';
            if(!alma?.value)return 'Seleccione un almacén activo.';
            if(requiereVendedor&&(!vende?.value||Number(vende.value)<=0))return 'Identifique un vendedor activo.';
            if(!lineas.length)return 'Agregue al menos un producto.';
            return '';
        };
        const calcular=()=>{
            const total=Math.round(lineas.reduce((s,p)=>s+p.canti*p.preci,0)*100)/100;
            const subto=Math.round(total/1.18*100)/100,igv=Math.round((total-subto)*100)/100;
            q('[data-nven-subto]').textContent=subto.toFixed(2);q('[data-nven-igv]').textContent=igv.toFixed(2);q('[data-nven-total]').textContent=total.toFixed(2);
            return {subto,igv,total};
        };
        const actualizarEstado=()=>{if(btnEmit)btnEmit.disabled=ocupado||guardado;};
        const dibujar=()=>{
            if(!lineas.length)cuerpo.innerHTML='<tr><td colspan="7">Agrega productos para emitir la nota.</td></tr>';
            else cuerpo.innerHTML=lineas.map((p,i)=>`<tr data-nven-linea="${i}"><td>${i+1}</td><td>${esc(p.codig||'—')}</td><td>${esc(p.descr||'—')}</td><td><input type="number" min="0.001" step="0.001" value="${p.canti}" data-nven-cantidad="${i}"></td><td><input type="number" min="0" step="0.01" value="${p.preci.toFixed(2)}" data-nven-precio="${i}"></td><td>${(p.canti*p.preci).toFixed(2)}</td><td><button type="button" data-nven-quitar="${i}" aria-label="Quitar producto">×</button></td></tr>`).join('');
            calcular();actualizarEstado();
        };
        const cargarOpciones=async()=>{
            const d=await pedir({accion:'opciones'}),items=d.almacenes||[];
            alma.innerHTML='<option value="">Seleccione almacén</option>'+items.map(a=>`<option value="${Number(a.id)}" ${Number(a.prin)===1?'selected':''}>${esc(a.nombr||`Almacén ${a.id}`)}</option>`).join('');
            if(!alma.value&&items.length)alma.value=String(items[0].id);

            /* La numeración se consulta por su propia acción y nunca se
               obtiene del tipo Boleta/Factura: la serie es de Nota de Venta. */
            const n=await pedir({accion:'numeracion'});
            const siguiente=Number(n.numeracion?.siguiente);
            if(!n.numeracion?.serie||!Number.isInteger(siguiente)||siguiente<1){
                serie.value='';
                numer.value='';
                throw new Error('No existe una serie/numeración válida de Nota de Venta en VENTA.numer.');
            }
            serie.value=String(n.numeracion.serie);
            numer.value=String(siguiente).padStart(6,'0');
            setTipo();
            q('[data-nven-stock-resumen]').textContent=alma.value?'Existencias del almacén seleccionado.':'No hay almacenes activos.';actualizarEstado();
        };
        const cargarEdicion=async()=>{
            if(!editId)return;
            const d=await pedir({accion:'obtener',id:editId}),n=d.nota||{};
            if(!n.id)throw new Error('No se encontró la Nota de Venta seleccionada.');
            doc.value=String(n.docum||'');setTipo();raz.value=String(n.razon||'');dire.value=String(n.direc||'');clien.value=String(n.clien||0);
            fecha.value=String(n.fecha||'').slice(0,10);placa.value=String(n.placa||'');formp.value=String(n.formp||'CONTADO');medi.value=String(n.medi||'');obser.value=String(n.obser||'');serie.value=String(n.serie||'');numer.value=String(n.numer||'').padStart(6,'0');
            if(n.vende_cod){vcod.value=String(n.vende_cod);vcod.dataset.codig=String(n.vende_cod);}vende.value=String(n.vende||0);
            const almaId=String(n.almac||'');if(almaId&&!Array.from(alma.options).some(o=>o.value===almaId)){const op=document.createElement('option');op.value=almaId;op.textContent=`Almacén ${almaId} (histórico)`;alma.append(op);}alma.value=almaId;alma.disabled=true;
            for(const x of (n.detalles||[]))lineas.push({produ:Number(x.produ),codig:String(x.codig||''),descr:String(x.descr||''),canti:Number(x.canti||0),preci:Number(x.preun||0)});
            if(!lineas.length)throw new Error('La nota seleccionada no tiene productos para editar.');
            btnEmit.textContent='[ Actualizar ]';if(msg)msg.textContent=`Editando Nota de Venta ${n.serie}-${String(n.numer).padStart(6,'0')}.`;
            dibujar();actualizarEstado();
        };
        const cerrarSse=()=>{if(fuente){try{fuente.close();}catch{}fuente=null;}};
        const abrirProductos=async()=>{
            const error=validarContexto(false).replace(/^Agregue al menos un producto\.$/,'');
            if(error&&error!==''){msg.textContent=error;return;}
            if(!window.NURBAK_MINI?.abrir)throw new Error('El coordinador MINI no está disponible.');
            miniProducto=await window.NURBAK_MINI.abrir({titulo:'Agregar producto',rutaVista:'vista/categ/venta/nvent/produc-mini.php',modulo:'nvent-productos',clase:'nvent-mini-productos'});
            if(!miniProducto)throw new Error('No se pudo abrir el buscador de productos.');
            const buscar=miniProducto.querySelector('[data-nven-prod-buscar]'),info=miniProducto.querySelector('[data-nven-prod-info]'),stock=miniProducto.querySelector('[data-nven-prod-stock]'),reservado=miniProducto.querySelector('[data-nven-prod-reser]'),disponible=miniProducto.querySelector('[data-nven-prod-dispo]'),resultados=miniProducto.querySelector('[data-nven-prod-resultados]');
            if(!buscar||!info||!stock||!reservado||!disponible||!resultados)throw new Error('El MINI de productos no contiene los elementos esperados.');
            let filas=[],indice=0,observado=0,seq=0;
            const resumen=()=>{const p=filas[indice]||filas.find(x=>Number(x.id)===observado);stock.textContent=`STOCK: ${Number(p?.canti||0)}`;reservado.textContent=`RESERVADO: ${Number(p?.reser||0)}`;disponible.textContent=`DISPONIBLE: ${Number(p?.dispo||0)}`;};
            const pintar=()=>{resultados.innerHTML=filas.map((p,i)=>`<tr data-nven-prod-fila="${i}" class="${i===indice?'is-seleccionada':''}"><td>${esc(p.motor||'—')}</td><td>${esc(p.model||'—')}</td><td>${esc(p.codig||'—')}</td><td>${esc(p.descr||'—')}</td><td>${esc(p.origi||'—')}</td><td>${esc(p.medid||'—')}</td><td>${esc(p.super||'—')}</td><td>${esc(p.anio||'—')}</td><td>${Number(p.canti||0)}</td><td>${Number(p.preci||0).toFixed(2)}</td></tr>`).join('');info.textContent=`${filas.length} resultado(s)`;resumen();};
            const actualizarSse=()=>{cerrarSse();const ids=filas.map(p=>Number(p.id)).filter(x=>x>0).slice(0,50);if(!alma.value||!ids.length||!window.EventSource)return;const url=`modul/categ/venta/nvent/sse.php?${new URLSearchParams({almac:alma.value,ids:ids.join(',')})}`;fuente=new EventSource(url);fuente.addEventListener('stock',ev=>{try{const data=JSON.parse(ev.data||'{}');for(const e of (data.productos||[])){const p=filas.find(x=>Number(x.id)===Number(e.produ));if(p)Object.assign(p,{canti:Number(e.canti||0),reser:Number(e.reser||0),dispo:Number(e.dispo||0)});}pintar();}catch{}});};
            const buscarDatos=async()=>{const id=++seq;const d=await pedir({accion:'buscar-productos',buscar:buscar.value.trim(),almac:alma.value,limite:20});if(id!==seq||cerrado)return;filas=d.productos||[];indice=0;observado=Number(filas[0]?.id||0);pintar();actualizarSse();};
            const agregar=()=>{const p=filas[indice];if(!p)return;const ex=lineas.find(x=>x.produ===Number(p.id));if(ex)ex.canti=Math.round((ex.canti+1)*1000)/1000;else lineas.push({produ:Number(p.id),codig:p.codig||'',descr:p.descr||'',canti:1,preci:Number(p.preci||0)});dibujar();q('[data-nven-stock-resumen]').textContent=`Almacén: ${alma.options[alma.selectedIndex]?.text||''} · Stock ${Number(p.canti||0)} · Reservado ${Number(p.reser||0)} · Disponible ${Number(p.dispo||0)}`;};
            const salir=()=>{clearTimeout(timerProd);seq++;cerrarSse();};
            miniProducto.addEventListener('mini:cerrando',salir,{once:true});
            buscar.addEventListener('input',()=>{clearTimeout(timerProd);timerProd=setTimeout(()=>void buscarDatos().catch(e=>info.textContent=e.message),220);});
            buscar.addEventListener('keydown',e=>{if(e.key==='ArrowDown'&&filas.length){e.preventDefault();indice=(indice+1)%filas.length;pintar();}else if(e.key==='ArrowUp'&&filas.length){e.preventDefault();indice=(indice-1+filas.length)%filas.length;pintar();}else if(e.key==='Enter'){e.preventDefault();agregar();}else if(e.key==='Escape')window.NURBAK_MINI?.cerrar?.(miniProducto);});
            resultados.addEventListener('click',e=>{const tr=e.target.closest('[data-nven-prod-fila]');if(!tr)return;indice=Number(tr.dataset.nvenProdFila);observado=Number(filas[indice]?.id||0);pintar();agregar();});
            await buscarDatos();buscar.focus();
        };

        doc?.addEventListener('input',()=>{
            doc.value=doc.value.replace(/\D/g,'').slice(0,11);setTipo();
            clien.value='0';solicitudCliente++;ultimoCliente='';clearTimeout(timerCliente);
            if(!tipoDoc(doc.value)){
                if(raz)raz.value='';if(dire)dire.value='';
                if(msg)msg.textContent=doc.value.length===0?'Ingrese un DNI de 8 dígitos o un RUC de 11 dígitos.':'Complete 8 dígitos para DNI o 11 para RUC.';
                actualizarEstado();return;
            }
            timerCliente=setTimeout(()=>void buscarCliente(),450);actualizarEstado();
        });
        setTipo();
        doc?.addEventListener('change',()=>{
            setTipo();
            clearTimeout(timerCliente);
            if(tipoDoc(doc.value)) void buscarCliente(true);
        });
        doc?.addEventListener('blur',()=>{
            setTipo();
            clearTimeout(timerCliente);
            void buscarCliente(true);
        });
        raz?.addEventListener('input',actualizarEstado);dire?.addEventListener('input',actualizarEstado);fecha?.addEventListener('change',actualizarEstado);alma?.addEventListener('change',()=>{q('[data-nven-stock-resumen]').textContent=alma.value?'Existencias del almacén seleccionado.':'Seleccione un almacén.';actualizarEstado();});
        vcod?.addEventListener('input',()=>{const cod=vcod.value.toUpperCase().replace(/\s/g,'');vcod.value=cod;vcod.dataset.codig='';vende.value='0';clearTimeout(timerVende);if(codigoValido(cod))timerVende=setTimeout(()=>void resolverVendedor().catch(e=>{msg.textContent=e.message;}),250);actualizarEstado();});
        vcod?.addEventListener('change',()=>{clearTimeout(timerVende);void resolverVendedor().catch(e=>{msg.textContent=e.message;actualizarEstado();});});
        placa?.addEventListener('input',()=>{placa.value=formPlaca(placa.value);});
        placa?.addEventListener('change',()=>{placa.value=formPlaca(placa.value);});
        btnProd?.addEventListener('click',()=>void abrirProductos().catch(e=>{msg.textContent=e.message;}));
        cuerpo?.addEventListener('change',e=>{const cq=e.target.closest('[data-nven-cantidad]'),pq=e.target.closest('[data-nven-precio]');if(cq){const i=Number(cq.dataset.nvenCantidad);lineas[i].canti=Math.max(0.001,Number(cq.value)||0.001);}if(pq){const i=Number(pq.dataset.nvenPrecio);lineas[i].preci=Math.max(0,Number(pq.value)||0);}if(cq||pq)dibujar();});
        cuerpo?.addEventListener('click',e=>{const quitar=e.target.closest('[data-nven-quitar]');if(quitar){lineas.splice(Number(quitar.dataset.nvenQuitar),1);dibujar();}});
        const cancelarReal=async()=>{
            if(ocupado)return;
            const t=await obtenerToast().catch(()=>null);
            t?.quit?.();
            t?.resu?.('Nota de Venta cancelada.','exi');
            window.setTimeout(()=>window.NURBAK_MINI?.cerrar?.(mini),450);
        };
        const solicitarCancelar=async()=>{
            if(ocupado||guardado)return;
            const t=await obtenerToast().catch(()=>null);
            const hayDatos=!!(doc?.value.trim()||raz?.value.trim()||dire?.value.trim()||obser?.value.trim()||lineas.length);
            if(t&&hayDatos){t.conf('canc',()=>{},cancelarReal);return;}
            await cancelarReal();
        };
        const emitirReal=async()=>{
            if(ocupado||guardado)return;
            const previo=validarContexto(false);
            if(previo)throw new Error(previo);
            await resolverVendedor();
            const error=validarContexto(true);
            if(error)throw new Error(error);
            ocupado=true;actualizarEstado();
            if(msg)msg.textContent='Validando existencias y guardando Nota de Venta...';
            try{
                const datos=await pedir({accion:editId?'actualizar':'emitir',id:editId,clien:clien?.value||0,tipod:tipoDoc(doc.value),docum:doc.value,razon:raz.value.trim(),direc:dire?.value||'',vende:vende.value,codig:vcod?.dataset.codig||'',almac:alma.value,placa:placa?.value||'',fecha:fecha.value,formp:formp?.value||'CONTADO',medi:medi?.value||'',obser:obser?.value||'',detalles:JSON.stringify(lineas.map(p=>({produ:p.produ,canti:p.canti,preci:p.preci})))},{method:'POST'});
                const n=datos.nota||{};
                if(!(n.documento_id||n.id)||!n.serie||!Number(n.numer))throw new Error('El servidor no devolvió la Nota de Venta registrada.');
                guardado=true;
                serie.value=n.serie;
                numer.value=String(n.numer).padStart(6,'0');
                const mensaje=`Nota de Venta ${editId?'actualizada':'guardada'}: ${n.serie}-${String(n.numer).padStart(6,'0')} · TOTAL ${Number(n.total||0).toFixed(2)}`;
                if(msg)msg.textContent=mensaje;
                const t=await obtenerToast().catch(()=>null);
                t?.quit?.();
                t?.resu?.(mensaje,'exi');
                window.dispatchEvent(new Event('nvent:emitida'));
                window.setTimeout(()=>window.NURBAK_MINI?.cerrar?.(mini),700);
            }catch(error){
                if(msg)msg.textContent=error.message||'No se pudo guardar la Nota de Venta.';
                throw error;
            }finally{
                ocupado=false;actualizarEstado();
            }
        };
        const solicitarGuardar=async()=>{
            if(ocupado||guardado)return;
            const t=await obtenerToast().catch(()=>null);
            const error=validarContexto(false);
            if(error){await notificar(error,'err',t);return;}
            try{await resolverVendedor();}
            catch(errorV){await notificar(errorV.message||'No se pudo identificar el vendedor.','err',t);return;}
            const final=validarContexto(true);
            if(final){await notificar(final,'err',t);return;}
            if(t){t.conf('guar',emitirReal,()=>{});return;}
            try{await emitirReal();}catch(errorE){await notificar(errorE.message||'No se pudo guardar la Nota de Venta.','err');}
        };
        q('[data-nven-cancelar]')?.addEventListener('click',e=>{e.preventDefault();void solicitarCancelar().catch(error=>void notificar(error.message,'err'));});
        form.addEventListener('submit',e=>e.preventDefault());
        btnEmit?.addEventListener('click',e=>{e.preventDefault();void solicitarGuardar().catch(error=>void notificar(error.message,'err'));});
        mini.addEventListener('mini:cerrando',()=>{cerrado=true;clearTimeout(timerCliente);clearTimeout(timerVende);clearTimeout(timerProd);cerrarSse();},{once:true});
        cargarOpciones().then(cargarEdicion).catch(e=>{void notificar(e.message,'err');});
        dibujar();
        void obtenerToast().catch(e=>console.error('Nota de Venta / Toast:',e.message));
    });

    window.addEventListener('nvent:emitida',()=>{void window.NURBAK_NVENT?.recargar?.();});
})();
