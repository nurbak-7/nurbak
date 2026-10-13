/* ============================================================
   Ruta: publi/asset/js/core/sincr/esta.js
   Función: Mantener el estado común de la aplicación.

   El estado de negocio continúa dentro de cada módulo.
   Este archivo solamente mantiene estado compartido del núcleo.

   SINCRONIZACIÓN | FASE 1
============================================================ */

(() => {
    const dat = new Map();
    const modu = new Map();

    const poner = (clav, valo) => {
        dat.set(String(clav), valo);
        return valo;
    };

    const obte = clav => dat.get(String(clav));
    const quit = clav => dat.delete(String(clav));
    const exis = clav => dat.has(String(clav));
    const vaci = () => {
        dat.clear();
        modu.clear();
    };

    window.NURBAK_ESTA = {
        listo: true,
        modu,
        poner,
        obte,
        quit,
        exis,
        vaci,
    };
})();
