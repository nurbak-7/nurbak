/* ============================================================
   Ruta: publi/asset/js/core/sincr/sinc.js
   Función: Proporcionar la comunicación común de la aplicación.

   No contiene lógica de negocio.
   Un módulo publica cambios y otros módulos pueden escucharlos
   sin conocerse directamente entre sí.

   SINCRONIZACIÓN | FASE 1
============================================================ */

(() => {
    const normalizar = tipo => String(tipo || '').trim();
    const registros = new Map();

    const on = (tipo, func, opt = false) => {
        const nombre = normalizar(tipo);

        if (!nombre || typeof func !== 'function') {
            return () => {};
        }

        const oyente = evento => func(evento.detail ?? {}, evento);
        const entrada = { func, oyente, opt, activo: true };

        if (!registros.has(nombre)) {
            registros.set(nombre, new Set());
        }

        registros.get(nombre).add(entrada);
        document.addEventListener(nombre, oyente, opt);

        return () => {
            if (!entrada.activo) return;

            entrada.activo = false;
            document.removeEventListener(nombre, oyente, opt);

            const grupo = registros.get(nombre);
            if (!grupo) return;

            grupo.delete(entrada);
            if (!grupo.size) {
                registros.delete(nombre);
            }
        };
    };

    const off = (tipo, func, opt = false) => {
        const nombre = normalizar(tipo);

        if (!nombre || typeof func !== 'function') {
            return;
        }

        const grupo = registros.get(nombre);
        if (!grupo) return;

        [...grupo].forEach(entrada => {
            if (entrada.func !== func || entrada.opt !== opt) return;

            entrada.activo = false;
            document.removeEventListener(nombre, entrada.oyente, entrada.opt);
            grupo.delete(entrada);
        });

        if (!grupo.size) {
            registros.delete(nombre);
        }
    };

    const emit = (tipo, deta = {}) => {
        const nombre = normalizar(tipo);

        if (!nombre) {
            return false;
        }

        return document.dispatchEvent(
            new CustomEvent(nombre, {
                detail: deta,
            })
        );
    };

    window.NURBAK_SINC = {
        listo: true,
        on,
        off,
        emit,
    };
})();
