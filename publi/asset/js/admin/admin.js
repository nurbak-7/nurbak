/* ============================================================
   Ruta: publi/asset/js/admin/admin.js
   Función: Entregar las rutas reales de JavaScript de ADMIN.
   CSS pertenece exclusivamente a core/estil.js.
============================================================ */

(() => {
    const mapa = {
        'admi/permi': 'publi/asset/js/admin/permi/permi.js',
        'admi/usuar': 'publi/asset/js/admin/usuar/usuar.js',
        'admi/tiend': 'publi/asset/js/admin/tiend/tiend.js',
    };

    window.NURBAK_ADMI = Object.freeze({
        listo: true,
        rutaJs: ruta => mapa[ruta] || ''
    });
})();
