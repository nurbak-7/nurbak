/* ============================================================
   Ruta: publi/asset/js/dash/modal/modal.js
   Función: Inicializar y coordinar el sistema universal de
            modals del dashboard.

   ESTRUCTURA:

   modal/
   ├── modal.js → coordinador y estado de modales
   ├── event.js → eventos y apertura
   ├── carga.js → carga de vistas
   ├── recup.js → carga de JavaScript funcional
   ├── rutas.js → rutas de vistas
   ├── movim.js → movimiento
   └── accio.js → acciones

   CSS dinámico global: publi/asset/js/core/estil.js

   LOGI / AUT se mantiene fuera de DASH:
   publi/asset/js/login/autor/autor.js

   🔹 Nivel 1: Cargar los módulos internos de MODAL.
   🔹 Nivel 2: Obtener las estructuras del dashboard.
   🔹 Nivel 3: Crear el estad y dependencias.
   🔹 Nivel 4: Inicializar los eventos.
   🔹 Nivel Final: Registrar que Modal quedó cargado.
============================================================ */

const iniciarModal = async () => {

    /* ========================================================
       NIVEL 1 — CARGAR MÓDULOS INTERNOS DE MODAL
    ======================================================== */

    const archivos = [
        'rutas.js',
        'recup.js',
        'movim.js',
        'accio.js',
        'carga.js',
        'event.js',
    ];

    const cargarArchivo = (archivo) => new Promise((resolver, rechazar) => {

        const yaExiste = document.querySelector(`[data-modal-core="${archivo}"]`);

        if (yaExiste) {
            resolver();
            return;
        }

        const programa = document.createElement('script');
        programa.src = `publi/asset/js/dash/modal/${archivo}`;
        programa.dataset.modalCore = archivo;

        programa.addEventListener('load', resolver, { once: true });
        programa.addEventListener('error', () => {
            rechazar(new Error(`No se pudo cargar ${archivo}`));
        }, { once: true });

        document.body.appendChild(programa);
    });

    try {

        // SINCRONIZACIÓN | FASE 1
        // Los componentes internos solamente exponen sus APIs; no necesitan
        // ejecutarse uno detrás de otro. Se espera a todos en paralelo.
        await Promise.all(archivos.map(cargarArchivo));

    } catch (error) {

        console.error(error);
        throw error;
    }

    /* ========================================================
       NIVEL 2 — OBTENER ESTRUCTURAS DEL DASHBOARD
    ======================================================== */

    const areaModals = document.querySelector('#dash-modal-area');
    const plantilla = document.querySelector('#dash-modal-plantilla');
    const navbar = document.querySelector('.dash-head');

    if (!areaModals || !plantilla) {
        console.warn('No se encontró la estructura de las modals.');
        return;
    }


    const rutas = window.NURBAK_MODAL_RUTAS.obtener();

    /* ========================================================
       NIVEL 3 — ESTADO DE MODALES

       El estado compartido de las ventanas vive en ESTA.JS.
       MODAL solamente lo utiliza para registrar/reutilizar
       las ventanas abiertas.
    ======================================================== */

    const estad = (() => {

        // SINCRONIZACIÓN | FASE 1
        // La colección de ventanas abiertas pertenece al estado común del núcleo.
        const modalsAbiertas = window.NURBAK_ESTA?.modu || new Map();
        let ordenModal = 1000;

        const actualizarAltoNavbar = () => {

            const alto = navbar
                ? navbar.getBoundingClientRect().height
                : 44;

            document.documentElement.style.setProperty(
                '--alto-nav-real',
                `${alto}px`
            );
        };

        const traerAlFrente = (modal) => {

            document.querySelectorAll('[data-modal]').forEach((otraModal) => {
                otraModal.classList.remove('is-activa');
            });

            ordenModal += 1;
            modal.style.zIndex = String(ordenModal);
            modal.classList.add('is-activa');
        };

        const ajustarDentroDelArea = (modal) => {

            if (
                modal.hidden ||
                modal.classList.contains('is-maximizada') ||
                modal.classList.contains('is-minimizada')
            ) {
                return;
            }

            const maximoX = Math.max(
                0,
                areaModals.clientWidth - modal.offsetWidth
            );

            const maximoY = Math.max(
                0,
                areaModals.clientHeight - modal.offsetHeight
            );

            const actualX = Number.parseFloat(modal.style.left) || 0;
            const actualY = Number.parseFloat(modal.style.top) || 0;

            const nuevoX = Math.max(
                0,
                Math.min(maximoX, actualX)
            );

            const nuevoY = Math.max(
                0,
                Math.min(maximoY, actualY)
            );

            modal.style.left = `${nuevoX}px`;
            modal.style.top = `${nuevoY}px`;
        };

        const ordenarMinimizadas = () => {

            const minimizadas = [
                ...areaModals.querySelectorAll('.is-minimizada')
            ];

            const separacion = 10;

            minimizadas.forEach((modal, indice) => {

                const posicionX =
                    separacion +
                    (indice * (modal.offsetWidth + separacion));

                const maximoX = Math.max(
                    0,
                    areaModals.clientWidth - modal.offsetWidth
                );

                modal.style.left =
                    `${Math.min(maximoX, posicionX)}px`;
            });
        };

        const colocarModal = (modal) => {

            const separacion = 24;

            const numer =
                Math.max(0, modalsAbiertas.size - 1) % 8;

            const centroX = Math.max(
                0,
                (areaModals.clientWidth - modal.offsetWidth) / 2
            );

            const arribaInicial = 6;

            modal.style.left =
                `${centroX + (numer * separacion)}px`;

            modal.style.top =
                `${arribaInicial + (numer * separacion)}px`;

            ajustarDentroDelArea(modal);
        };

        return {
            modalsAbiertas,
            actualizarAltoNavbar,
            traerAlFrente,
            ajustarDentroDelArea,
            ordenarMinimizadas,
            colocarModal,
        };

    })();

    estad.actualizarAltoNavbar();

    /* ========================================================
       NIVEL 3 — CREAR RECURSOS Y MOVIMIENTO
    ======================================================== */

    const recursos = window.NURBAK_MODAL_RECUP.crear();

    const movimiento = window.NURBAK_MODAL_MOVIM.crear({
        areaModals,
        traerAlFrente: estad.traerAlFrente,
    });

    const acciones = window.NURBAK_MODAL_ACCIO.crear({
        modalsAbiertas: estad.modalsAbiertas,
        traerAlFrente: estad.traerAlFrente,
        ajustarDentroDelArea: estad.ajustarDentroDelArea,
        ordenarMinimizadas: estad.ordenarMinimizadas,
    });

    /*
       Publicar el cierre propietario para que los módulos puedan pedirlo
       después de completar su flujo, sin retirar el DOM ni tocar el estado.
    */
    window.NURBAK_MODAL_ACCIO.cerrarModal = modal =>
        acciones.cerrarModal(modal);

    /* SESIÓN — cierre global antes de abandonar el panel. */
    const cerrarPorSesion = () => {
        [...estad.modalsAbiertas.values()].forEach(modal => {
            acciones.cerrarModal(modal);
        });
    };
    window.NURBAK_SINC?.on('sesion:cerrando', cerrarPorSesion);

    const carga = window.NURBAK_MODAL_CARGA.crear({
        cargarJs: recursos.cargar,
        cargarCss: window.NURBAK_ESTIL.cargar,
    });

    /* ========================================================
       NIVEL 4 — CONECTAR MODALES
    ======================================================== */

    const eventos = window.NURBAK_MODAL_EVENT.crear({
        areaModals,
        plantilla,
        rutas,
        modalsAbiertas: estad.modalsAbiertas,
        prepararArrastre: movimiento.preparar,
        prepararRedimension: movimiento.prepararRedimension,
        prepararControles: acciones.prepararControles,
        colocarModal: estad.colocarModal,
        traerAlFrente: estad.traerAlFrente,
        restaurarModal: acciones.restaurarModal,
        cargarContenido: carga.cargar,
        actualizarAltoNavbar: estad.actualizarAltoNavbar,
        ajustarDentroDelArea: estad.ajustarDentroDelArea,
        ordenarMinimizadas: estad.ordenarMinimizadas,
    });

    eventos.iniciar();

    // SINCRONIZACIÓN | FASE 1
    window.NURBAK_ESTA?.poner('moda', { listo: true });
    window.NURBAK_SINC?.emit('moda:listo', { listo: true });

    /* ========================================================
       NIVEL FINAL — MODAL INICIALIZADO
    ======================================================== */
};

/* ============================================================
   NIVEL FINAL — INICIO SEGÚN ESTADO DEL DOM
============================================================ */

window.NURBAK_MODAL_READY = new Promise((resolver, rechazar) => {
    const iniciar = async () => {
        try {
            await iniciarModal();
            resolver(true);
        } catch (error) {
            rechazar(error);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar, { once: true });
    } else {
        iniciar();
    }
});
