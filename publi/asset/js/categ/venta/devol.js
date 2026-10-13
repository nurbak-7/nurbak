/* ============================================================
   Ruta: publi/asset/js/categ/venta/devol.js
   Función: Recurso del sistema.
============================================================ */
/* ============================================================
   Ventas / Devoluciones
   Consulta en esta primera etapa; una devolución queda pendiente
   antes de aplicar cualquier efecto a Inventario.
============================================================ */
(() => {
    const R='modul/categ/venta/devol/devol-contr.php',esc=v=>String(v??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');
    const iniciar=c=>{const r=c.matches?.('[data-devo]')?c:c.querySelector?.('[data-devo]');if(!r||r.dataset.iniciado==='si')return;r.dataset.iniciado='si';const q=s=>r.querySelector(s),b=q('[data-devo-busc]'),tb=q('[data-devo-cuerpo]'),m=q('[data-devo-mensaje]');
        const listar=async()=>{m.hidden=false;m.textContent='Cargando devoluciones...';tb.innerHTML='';try{const x=await fetch(`${R}?accion=listar&buscar=${encodeURIComponent(b.value.trim())}&limite=50`,{cache:'no-store'}),d=await x.json();if(!x.ok||!d.ok)throw Error(d.mensaje||'Error');const a=d.devoluciones||[];tb.innerHTML=a.map(x=>`<tr><td>${esc(x.id)}</td><td>${esc(x.opera||'—')}</td><td>${esc(x.razon)}</td><td>${esc(x.almacen)}</td><td>${esc(x.estad)}</td><td>${esc(x.fecha)}</td><td>${esc(x.motivo||'—')}</td></tr>`).join('');m.textContent='Sin registros';m.hidden=a.length>0;}catch(e){m.textContent='No se pudieron cargar las devoluciones.';console.error('Devoluciones:',e);}};
        /* INTEGRACIÓN | FASE 3 */window.NURBAK_CATE_SINC?.onCambio(()=>listar().catch(()=>{}),{recu:'opera'},r);
        let t;b.addEventListener('input',()=>{clearTimeout(t);t=setTimeout(listar,250)});listar();};
    document.addEventListener('modu:cargado',e=>{if(e.detail?.modulo==='devoluciones')iniciar(e.detail.modal)});document.querySelectorAll('[data-devo]').forEach(iniciar);
})();
