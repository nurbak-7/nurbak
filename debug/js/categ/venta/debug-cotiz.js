/* ============================================================
   Ruta: debug/js/categ/venta/debug-cotiz.js
   Módulo: DEBUG / Ventas / Cotización

   RESPONSABILIDAD:
   Escuchar exclusivamente los eventos DEBUG de Cotización.
============================================================ */
(() => {
    'use strict';
    const key='__NURBAK_DEBUG_CATEG_VENTA_COTIZ';
    const endpoint=new URL('debug/php/categ/venta/debug-cotiz.php',document.baseURI).href;
    if(window[key])return;window[key]=true;
    const enviar=(paso,contexto={})=>{try{const dat=new FormData();dat.append('paso',paso);Object.entries(contexto).forEach(([n,v])=>dat.append(`contexto[${n}]`,String(v??'')));fetch(endpoint,{method:'POST',body:dat,keepalive:true,cache:'no-store',credentials:'same-origin'}).catch(()=>{});}catch(_) {}};
    const archivos=[
        'debug/js/categ/venta/cotiz/debug-docum.js',
        'debug/js/categ/venta/cotiz/debug-clien.js',
        'debug/js/categ/venta/cotiz/debug-vende.js',
        'debug/js/categ/venta/cotiz/debug-produ.js',
        'debug/js/categ/venta/cotiz/debug-tabla.js',
        'debug/js/categ/venta/cotiz/debug-total.js',
        'debug/js/categ/venta/cotiz/debug-accio.js'
    ];
    enviar('COTIZ_ACTI',{archivo:'debug-cotiz.js'});
    (async()=>{for (const ruta of archivos) {
            if (document.querySelector(`[data-debug-js="${ruta}"]`)) continue;
            const script = document.createElement('script');
            script.src = ruta;
            script.dataset.debugJs = ruta;
            await new Promise((ok) => {
                script.addEventListener('load', ok, { once: true });
                script.addEventListener('error', ok, { once: true });
                document.body.appendChild(script);
            });
        }})();
    window.addEventListener('error',e=>enviar('COTIZ_JSER',{mensaje:e.message||'Error JavaScript.',archivo:e.filename||'',linea:e.lineno||0,columna:e.colno||0}),true);
    window.addEventListener('unhandledrejection',e=>{const r=e.reason instanceof Error?e.reason:new Error(String(e.reason??'Promise rechazada.'));enviar('COTIZ_PROM',{mensaje:r.message,archivo:r.stack||''});},true);
    
})();
