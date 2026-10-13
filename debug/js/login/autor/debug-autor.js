/* ============================================================
   Ruta: debug/js/login/autor/debug-autor.js
   Función: Escuchar exclusivamente el proceso de AUTOR.

   Este archivo NO autoriza.
   Este archivo NO consulta permisos.
   Este archivo NO decide resultados.
   Solo escucha los eventos emitidos por AUTOR.JS y los registra
   en su propio endpoint de DEBUG.
============================================================ */
(() => {
    if (window.__NURBAK_DEBUG_AUTOR_ACTIVO__) return;
    window.__NURBAK_DEBUG_AUTOR_ACTIVO__ = true;

    const endpoint = new URL(
        'debug/php/login/autor/debug-autor.php',
        document.baseURI
    ).href;

    /* ========================================================
       NIVEL 1 — ENVIAR EVENTO AL DEBUG PHP
    ======================================================== */

    const enviar = (paso, contexto = {}) => {
        const datos = new FormData();
        datos.append('paso', paso);

        Object.entries(contexto).forEach(([clave, valor]) => {
            datos.append(
                `contexto[${clave}]`,
                String(valor ?? '')
            );
        });

        fetch(endpoint, {
            method: 'POST',
            body: datos,
            keepalive: true,
            cache: 'no-store',
            credentials: 'same-origin'
        }).catch(() => {});
    };

    /* ========================================================
       NIVEL 2 — ESCUCHAR EL PROCESO DE AUTOR
    ======================================================== */

    document.addEventListener('logi:autorizacion-proceso', evento => {
        const detalle = evento.detail || {};
        const paso = detalle.paso || 'AUTOR_EVENTO';
        const contexto = { ...detalle };

        delete contexto.paso;

        enviar(paso, contexto);
    });

    /* ========================================================
       NIVEL 3 — ESCUCHAR LA DECISIÓN FINAL
    ======================================================== */

    document.addEventListener('logi:autorizacion', evento => {
        const detalle = evento.detail || {};

        enviar(
            detalle.autorizado ? 'AUTOR_AUTORIZADO' : 'AUTOR_RECHAZADO',
            {
                permiso: detalle.perm_codi || '',
                ok: detalle.ok === true ? '1' : '0',
                autorizado: detalle.autorizado === true ? '1' : '0',
                mensaje: detalle.mensaje || '',
                error: detalle.error || ''
            }
        );
    });

    /* ========================================================
       NIVEL FINAL — DEBUG ACTIVO
    ======================================================== */

    enviar('DEBUG_AUTOR_ACTIVO');
})();
