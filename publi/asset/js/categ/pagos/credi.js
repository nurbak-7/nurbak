/* ============================================================
   Ruta: publi/asset/js/categ/pagos/credi.js
   Función: Recurso del sistema.
============================================================ */
/* ============================================================
   Pagos / ID - consulta base
============================================================ */
(() => {
    const R = 'modul/categ/pagos/credi/credi-contr.php';
    const iniciar = c => {
        const r = c.matches?.('[data-pago-cred]') ? c : c.querySelector?.('[data-pago-cred]');
        if (!r || r.dataset.iniciado === 'si') return;
        r.dataset.iniciado = 'si';
        const q = s => r.querySelector(s);
        const b = q('[data-pago-busc]');
        const th = q('[data-pago-cabeza]');
        const tb = q('[data-pago-cuerpo]');
        const msg = q('[data-pago-mensaje]');
        th.innerHTML = `<th>ID</th><th>Operación</th><th>Documento</th><th>Cliente</th><th>Monto inicial</th><th>Saldo</th><th>Vencimiento</th><th>Estado</th>`;
        const esc = v => String(v ?? '—').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');
        const listar = async () => {
            msg.hidden = false; msg.textContent = 'Cargando...'; tb.innerHTML = '';
            try {
                const x = await fetch(`${R}?accion=listar&buscar=${encodeURIComponent(b.value.trim())}&limite=50`, {cache:'no-store'});
                const d = await x.json();
                if (!x.ok || !d.ok) throw Error(d.mensaje || 'Error');
                const a = d.cred || [];
                tb.innerHTML = a.map(x => `<td>${esc(x.id)}</td><td>${esc(x.opera)}</td><td>${esc(x.documento_tipo)}</td><td>${esc(x.clien)}</td><td>${esc(x.monto_inicial)}</td><td>${esc(x.saldo)}</td><td>${esc(x.fecha_vencimiento)}</td><td>${esc(x.estad)}</td>`).join('');
                msg.textContent = 'Sin registros'; msg.hidden = a.length > 0;
            } catch(e) {
                msg.textContent = 'No se pudieron cargar los registros.';
                console.error('Pagos:', e);
            }
        };
        /* INTEGRACIÓN | FASE 3 */
        window.NURBAK_CATE_SINC?.onCambio(() => listar().catch(() => {}), {recu:'opera'}, r);
        window.NURBAK_CATE_SINC?.onCambio(() => listar().catch(() => {}), {recu:'docum'}, r);
        let t;
        b.addEventListener('input', () => { clearTimeout(t); t = setTimeout(listar,250); });
        listar();
    };
    document.addEventListener('modu:cargado', e => { if (e.detail?.modulo) iniciar(e.detail.modal); });
    document.querySelectorAll('[data-pago-cred]').forEach(iniciar);
})();
