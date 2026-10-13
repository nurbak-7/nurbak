/* ============================================================
   Ruta: publi/asset/js/categ/factu/ndebi.js
   Función: Recurso del sistema.
============================================================ */
/* Facturación / Notas de débito */
(()=>{
 const R='modul/categ/factu/ndebi/ndebi-contr.php';
 const esc=v=>String(v??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');
 const iniciar=c=>{const r=c?.matches?.('[data-fisc-modulo="ndeb"]')?c:c?.querySelector?.('[data-fisc-modulo="ndeb"]');if(!r||r.dataset.iniciado==='si')return;r.dataset.iniciado='si';const q=s=>r.querySelector(s),b=q('[data-fisc-buscar]'),tb=q('[data-fisc-cuerpo]'),m=q('[data-fisc-mensaje]');const listar=async()=>{m.hidden=false;m.textContent='Cargando...';tb.innerHTML='';try{const x=await fetch(R+'?accion=listar&buscar='+encodeURIComponent(b.value.trim())+'&limite=50',{cache:'no-store'}),d=await x.json();if(!x.ok||!d.ok)throw Error(d.mensaje||'Error');const a=d.registros||[];tb.innerHTML=a.map(x=>`<tr><td>${esc(x.id)}</td><td>${esc(x.serie)}-${esc(String(x.numer).padStart(6,"0"))}</td><td>${esc(x.origen_tipo)} #${esc(x.origen_id)}</td><td>${esc(x.estad)}</td><td>${esc(x.fecha)}</td><td>${Number(x.total).toFixed(2)}</td></tr>`).join('');m.textContent='Sin registros';m.hidden=a.length>0}catch(e){m.textContent='No se pudieron cargar los registros.';console.error('Notas de débito:',e)}};/* INTEGRACIÓN | FASE 3 */window.NURBAK_CATE_SINC?.onCambio(()=>listar().catch(()=>{}),{recu:'docum'},r);window.NURBAK_CATE_SINC?.onCambio(()=>listar().catch(()=>{}),{recu:'opera'},r);let t;b.addEventListener('input',()=>{clearTimeout(t);t=setTimeout(listar,250)});listar()};
 document.addEventListener('modu:cargado',e=>{if(e.detail?.modulo==='notas-debito')iniciar(e.detail.modal)});document.querySelectorAll('[data-fisc-modulo="ndeb"]').forEach(iniciar);
})();
