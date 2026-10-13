/* ============================================================
   Ruta: publi/asset/js/categ/venta/cotiz/toast.js
   Módulo: Ventas / Cotización / Toast

   RESPONSABILIDAD:
   Confirmación y resultados temporales de Cotización.
============================================================ */
(() => {
    const MOD='vent/cotiz';
    const iniciar=({ventana}={})=>{
        const root=ventana?.querySelector('[data-cotiz]');
        if(!root) return null;
        if(root.__cotizToas) return root.__cotizToas;
        let capa=null;
        const obtenerCapa=()=>{
            if(capa?.isConnected) return capa;
            capa=document.createElement('div');
            capa.dataset.cotizToas='';
            document.body.appendChild(capa);
            return capa;
        };
        const quit=()=>capa?.querySelectorAll('[data-cotiz-toas-conf]').forEach(el=>el.remove());
        const resu=(texto,tipo='exi',ms=2200)=>{
            const c=obtenerCapa(); const t=document.createElement('div');
            t.dataset.cotizToasResu=tipo; t.textContent=String(texto??''); c.appendChild(t);
            requestAnimationFrame(()=>{t.dataset.cotizToasOn='si';});
            setTimeout(()=>{t.dataset.cotizToasOn='no';setTimeout(()=>t.remove(),180);},ms);
            return t;
        };
        const conf=(tipo,guar,canc)=>{
            const c=obtenerCapa(); quit(); const box=document.createElement('div'); box.dataset.cotizToasConf='';
            const esGuar=tipo==='guar';
            box.innerHTML=`<div data-cotiz-toas-conf-box role="dialog" aria-modal="true"><p>${esGuar?'La cotización actual se guardará y quedará registrada en el sistema.':'La cotización actual se cancelará y no será registrada.'}</p><div data-cotiz-toas-conf-btn><button type="button" data-cotiz-toas-act>${esGuar?'[ Guardar ]':'[ Cancelar ]'}</button><button type="button" data-cotiz-toas-vol>[ Volver ]</button></div></div>`;
            c.appendChild(box);
            const act=box.querySelector('[data-cotiz-toas-act]'); const vol=box.querySelector('[data-cotiz-toas-vol]');
            act?.addEventListener('click',async()=>{
                act.disabled=true;vol.disabled=true;
                try{await (esGuar?guar:canc)?.();box.remove();}
                catch(error){act.disabled=false;vol.disabled=false;resu(error?.message||'No se pudo completar la acción.','err');}
            });
            vol?.addEventListener('click',()=>box.remove());
        };
        const api={resu,conf,quit}; root.__cotizToas=api; window.NURBAK_TOAST?.registrar(MOD,api); return api;
    };
    window.NURBAK_TOAST_MODULOS=window.NURBAK_TOAST_MODULOS||{}; window.NURBAK_TOAST_MODULOS[MOD]=iniciar;
})();
