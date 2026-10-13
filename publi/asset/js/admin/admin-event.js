/* ============================================================
   Ruta: publi/asset/js/admin/admin-emodal.js
   Función: Coordinar los eventos internos de ADMI.

   ADMI →
   ├── permi/ → Permisos
   ├── usuar/ → Usuarios
   └── tiend/ → Tiendas

   Este archivo NO carga CSS ni JavaScript.
   Solo detecta el submódulo y solicita su vista.

   🔹 Nivel 1: Detectar ADMI
   🔹 Nivel 2: Detectar submódulo
   🔹 Nivel 3: Cargar vista
   🔹 Nivel Final: Solicitar recursos a RECU
============================================================ */

(() => {

    /* ========================================================
       NIVEL 1 — RUTAS INTERNAS DE ADMI
       ======================================================== */

    const rutas = {
        perm: 'vista/admin/permi/permi.php',
        usua: 'vista/admin/usuar/usuar.php',
        tiend: 'vista/admin/tiend/tiend.php'
    };

    /* ========================================================
       NIVEL 2 — CARGAR VISTA
       ======================================================== */

    const cargarVista = async (ventana, modulo) => {

        const rutaVista = rutas[modulo];
        const contenido = ventana?.querySelector('[data-modal-contenido]');

        if (!rutaVista || !contenido) return;

        contenido.innerHTML = '<p>Cargando contenido...</p>';

        try {
            const respuesta = await fetch(rutaVista, {
                method: 'GET',
                cache: 'no-store'
            });

            if (!respuesta.ok) {
                throw new Error(`HTTP ${respuesta.status}`);
            }

            contenido.innerHTML = await respuesta.text();

            /* ====================================================
               NIVEL 3 — SOLICITAR RECURSOS A RECU
               ==================================================== */

            document.dispatchEvent(new CustomEvent('admi:recursos', {
                detail: {
                    rutaVista,
                    modulo: `admi-${modulo}`,
                    ventana
                }
            }));

        } catch (error) {
            contenido.innerHTML =
                '<p>No se pudo cargar el módulo administrativo.</p>';
            console.error('ADMI:', error);
        }
    };

    /* ========================================================
       NIVEL 4 — CONECTAR BOTONES
       ======================================================== */

    const conectar = ventana => {

        if (!ventana || ventana.dataset.admiEventos === 'si') return;

        ventana.dataset.admiEventos = 'si';

        ventana.addEventListener('click', evento => {

            const boton = evento.target.closest('[data-admi]');
            if (!boton || !ventana.contains(boton)) return;

            const modulo = boton.dataset.admi;
            if (!rutas[modulo]) return;

            evento.preventDefault();

            ventana.querySelectorAll('[data-admi]').forEach(elemento =>
                elemento.classList.remove('is-activ')
            );

            boton.classList.add('is-activ');
            cargarVista(ventana, modulo);
        });
    };

    /* ========================================================
       NIVEL FINAL — ADMI CARGADO
       ======================================================== */

    document.addEventListener('modu:cargado', evento => {

        if (evento.detail?.modulo !== 'admi') return;

        conectar(evento.detail.modal);
    });

})();
