/* ============================================================
   Ruta: publi/asset/js/login/autor/autor.js
   Módulo: LOGI / Autorización
   Función: Consultar al servidor si el usuario actual está
            autorizado para ejecutar una acción o abrir un módulo.

   CADENA:
   DASH / VENT / EVEN
          ↓
   LOGI / AUTOR.JS
          ↓ fetch
   modul/login/autor/autor-contr.php
          ↓
   AUT-SERV
          ↓
   AUT-REPO
          ↓
   LOGI

   AUTOR.JS no decide permisos por su cuenta.
   La decisión siempre pertenece al servidor.

   🔹 Nivel 1: Preparar la ruta de autorización.
   🔹 Nivel 2: Emitir el inicio de la consulta.
   🔹 Nivel 3: Solicitar autorización al servidor.
   🔹 Nivel 4: Entregar eventos del proceso.
   🔹 Nivel Final: Entregar la decisión al componente solicitante.
============================================================ */

window.NURBAK_LOGI_AUT = (() => {

    /* ========================================================
       NIVEL 1 — RUTA CENTRAL DE AUTORIZACIÓN
    ======================================================== */

    const ruta = 'modul/login/autor/autor-contr.php';
    const pend = new Map();

    /* ========================================================
       NIVEL 3 — CONSULTAR AUTORIZACIÓN
    ======================================================== */

    const autorizar = async (permCodi) => {

        permCodi = String(permCodi || '').trim().toUpperCase();

        if (!permCodi) {
            const salida = {
                ok: false,
                autorizado: false,
                mensaje: 'Código de permiso no especificado.'
            };

            return salida;
        }

        // SINCRONIZACIÓN | FASE 1
        // Doble clic o solicitudes simultáneas del mismo permiso comparten
        // una sola petición al servidor; no se cachea la decisión.
        if (pend.has(permCodi)) {
            return pend.get(permCodi);
        }

        const prom = (async () => {
            const datos = new FormData();
            datos.append('codig', permCodi);

            try {

            const respuesta = await fetch(ruta, {
                method: 'POST',
                body: datos,
                credentials: 'same-origin',
                cache: 'no-store'
            });

            if (!respuesta.ok) {
                throw new Error(`HTTP ${respuesta.status}`);
            }

            const resultado = await respuesta.json();

            const salida = {
                ok: resultado.ok === true,
                autorizado: resultado.autorizado === true,
                perm_codi: resultado.codig || permCodi,
                mensaje: resultado.mensaje || ''
            };

                return salida;

            } catch (error) {

                const salida = {
                    ok: false,
                    autorizado: false,
                    perm_codi: permCodi,
                    mensaje: 'No se pudo comprobar la autorización.',
                    error: error.message
                };

                return salida;
            }
        })();

        pend.set(permCodi, prom);

        try {
            return await prom;
        } finally {
            pend.delete(permCodi);
        }
    };

    /* ========================================================
       NIVEL FINAL — API PÚBLICA DE AUTOR
    ======================================================== */

    return {
        autorizar
    };
})();
