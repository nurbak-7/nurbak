/* ============================================================
   Ruta: publi/asset/js/categ/catal/artic.js
   Módulo: Catálogo / Artículos
   Función: Consulta automática consolidada.

   RESPONSABILIDAD:
   - Solicitar Artículos al controlador.
   - Mostrar MOTOR, MODEL, EQUIV, AÑO y QTY.
   - Mostrar resultados y errores del módulo.
============================================================ */

(() => {
    'use strict';

    /* ========================================================
       NIVEL 1
       RUTA
    ======================================================== */

    const ruta =
        'modul/categ/catal/artic/artic-contr.php';

    const max = 50;


    /* ========================================================
       NIVEL 1
       ESCAPE HTML
    ======================================================== */

    const esc = val => String(val ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');


    /* ========================================================
       NIVEL 1
       INICIAR
    ======================================================== */

    const iniciar = cont => {

        const root = cont?.matches?.('[data-arti]')
            ? cont
            : cont?.querySelector?.('[data-arti]');

        if (!root || root.dataset.iniciado === 'si') {
            return;
        }

        const busc =
            root.querySelector('[data-arti-busc]');

        const cuer =
            root.querySelector('[data-arti-cuer]');

        const vaci =
            root.querySelector('[data-arti-vaci]');
        const movil = root.querySelector('[data-arti-movil]');
        const movUno = root.querySelector('[data-arti-mov-uno]');
        const movDos = root.querySelector('[data-arti-mov-dos]');
        if (!busc || !cuer || !vaci || !movil || !movUno || !movDos) {
            return;
        }

        root.dataset.iniciado = 'si';


        /* ====================================================
           NIVEL 2
           LISTAR
        ==================================================== */

        const lista = async () => {

            vaci.hidden = false;
            vaci.textContent =
                'Cargando artículos...';

            cuer.innerHTML = '';
            movUno.innerHTML = '';
            movDos.innerHTML = '';

            try {

                const q = new URLSearchParams({
                    accio: 'lista',
                    busc: busc.value.trim(),
                    limi: String(max)
                });

                const res = await fetch(
                    `${ruta}?${q}`,
                    {
                        cache: 'no-store'
                    }
                );

                const dat = await res.json();

                if (!res.ok || !dat.ok) {
                    throw new Error(
                        dat.mensaje ||
                        'No se pudo consultar.'
                    );
                }

                const rows =
                    dat.artic || [];


                /* ============================================
                   NIVEL 3
                   MOSTRAR
                ============================================ */

                cuer.innerHTML = rows.map(row => `
                    <tr>
                        <td>${esc(row.id)}</td>
                        <td>${esc(row.motor || '—')}</td>
                        <td>${esc(row.model || '—')}</td>
                        <td>${esc(row.descr || '—')}</td>
                        <td>${esc(row.codig || '—')}</td>
                        <td>${esc(row.origi || '—')}</td>
                        <td>${esc(row.medid || '—')}</td>
                        <td>${esc(row.super || '—')}</td>
                        <td>${esc(row.equiv || '—')}</td>
                        <td>${esc(row.anio || '—')}</td>
                        <td>${esc(row.qty ?? 0)}</td>
                        <td>${Number(row.pcomp || 0).toFixed(2)}</td>
                        <td>${Number(row.pvent || 0).toFixed(2)}</td>
                        <td>${esc(row.fecha || '—')}</td>
                    </tr>
                `).join('');

                movUno.innerHTML = rows.map(row => `
                    <tr>
                        <td>${esc(row.id ?? '—')}</td>
                        <td>${esc(row.motor || '—')}</td>
                        <td>${esc(row.model || '—')}</td>
                        <td>${esc(row.descr || '—')}</td>
                        <td>${esc(row.codig || '—')}</td>
                        <td>${esc(row.origi || '—')}</td>
                        <td>${esc(row.medid || '—')}</td>
                    </tr>
                `).join('');

                movDos.innerHTML = rows.map(row => `
                    <tr>
                        <td>${esc(row.super || '—')}</td>
                        <td>${esc(row.equiv || '—')}</td>
                        <td>${esc(row.anio || '—')}</td>
                        <td>${esc(row.qty ?? 0)}</td>
                        <td>${Number(row.pcomp || 0).toFixed(2)}</td>
                        <td>${Number(row.pvent || 0).toFixed(2)}</td>
                        <td>${esc(row.fecha || '—')}</td>
                    </tr>
                `).join('');

                vaci.textContent =
                    busc.value.trim()
                        ? 'Sin coincidencias'
                        : 'Sin registros';

                vaci.hidden = rows.length > 0;
} catch (err) {

                vaci.textContent =
                    'No se pudieron cargar los artículos.';

                vaci.hidden = false;

                console.error(
                    'Artículo:',
                    err
                );
}
        };


        /* ====================================================
           NIVEL 2
           BÚSQUEDA AUTOMÁTICA
        ==================================================== */

        // SINCRONIZACIÓN | FASE 2
        window.NURBAK_CATE_SINC?.onCambio(() => lista().catch(() => {}), {recu:'produ'}, cont);
        window.NURBAK_CATE_SINC?.onCambio(() => lista().catch(() => {}), {recu:'equiv'}, cont);
        window.NURBAK_CATE_SINC?.onCambio(() => lista().catch(() => {}), {recu:'stock'}, cont);

        let temp;

        busc.addEventListener(
            'input',
            () => {
                clearTimeout(temp);
                temp = setTimeout(
                    lista,
                    250
                );
            }
        );

        lista();
    };


    /* ========================================================
       NIVEL 1
       CARGA DEL MÓDULO
    ======================================================== */

    document.addEventListener(
        'modu:cargado',
        evento => {
            if (evento.detail?.modulo === 'articulos') {
                iniciar(evento.detail.modal);
            }
        }
    );

    document
        .querySelectorAll('[data-arti]')
        .forEach(iniciar);
})();
