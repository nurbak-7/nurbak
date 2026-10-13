/* ============================================================
   Ruta: publi/asset/js/dash/logi/acces.js
   Función: Controlar el formulario y la inactividad del panel.

   Este archivo trabaja únicamente con las páginas que ya cargan
   este JavaScript:

   1. vista/authe/acce-login.php
      Contiene el formulario de ingreso.

   2. vista/dash/layo/head.php
      Contiene el usuario y el cierre de sesión del panel.

   3. contr/login/c-auth.php
      Controla la sesión en PHP y tiene el límite de inactividad.

   Prueba actual:

   A los 25 segundos → aparece el aviso.
   A los 30 segundos → se cierra la sesión si no hay actividad.

   Si el usuario acepta el aviso, mueve el mouse o presiona una
   tecla, el aviso desaparece y el contador comienza nuevamente.

   🔹 Nivel 1: Esperar a que cargue la página
   🔹 Nivel 2: Evitar envíos repetidos
   🔹 Nivel 3: Preparar el aviso y los tiempos
   🔹 Nivel 4: Detectar la actividad del usuario
   🔹 Nivel Final: Cerrar la sesión por inactividad
============================================================ */

document.addEventListener('DOMContentLoaded', () => {

    /* ========================================================
       NIVEL 1 — LOCALIZAR EL FORMULARIO DE ACCESO
    ======================================================== */

    const formulario = document.querySelector('.acce-form');
    const boton = formulario?.querySelector(
        'button[type="submit"]'
    );
    const campoClave = formulario?.querySelector(
        'input[name="clave"]'
    );

    let temporizadorEnvio;
    let envioAutomatico = false;


    /* ========================================================
       ENVÍO AUTOMÁTICO DE LA CONTRASEÑA
    ======================================================== */

    campoClave?.addEventListener('input', () => {

        window.clearTimeout(
            temporizadorEnvio
        );

        envioAutomatico = false;

        /* Máximo de 15 caracteres. */
        if (campoClave.value.length > 15) {
            campoClave.value =
                campoClave.value.slice(0, 15);
        }

        /* Todavía no alcanza el mínimo. */
        if (campoClave.value.length < 4) {
            return;
        }

        /* Esperar a que termine de escribir. */
        temporizadorEnvio = window.setTimeout(() => {

            if (
                campoClave.value.length >= 4 &&
                campoClave.value.length <= 15 &&
                formulario?.checkValidity() &&
                !envioAutomatico
            ) {
                envioAutomatico = true;
                formulario.requestSubmit();
            }

        }, 1000);
    });


    /* ========================================================
       NIVEL 2 — EVITAR QUE EL FORMULARIO SE ENVÍE DOS VECES
    ======================================================== */

    formulario?.addEventListener('submit', () => {

        if (boton) {
            boton.disabled = true;
            boton.textContent = 'Ingresando...';
        }
    });


    /* ========================================================
       NIVEL 3 — ACTIVAR EL CONTADOR SOLAMENTE EN EL PANEL
    ======================================================== */

    if (
        !document.body.classList.contains('acce-panel')
    ) {
        return;
    }

    const tiempoAviso = 25 * 1000;
    const tiempoCierre = 30 * 1000;

    let temporizadorAviso;
    let temporizadorCierre;
    let aviso;

    let ultimaNotificacion = 0;
    let actividadEnviando = false;

    const rutaAct = window.location.href;


    /* ========================================================
       NIVEL 3 — MOSTRAR AVISO
    ======================================================== */

    const mostrarAviso = () => {

        aviso = document.createElement('div');

        aviso.textContent =
            'La sesión se cerrará en 5 segundos por inactividad.';

        aviso.style.position = 'fixed';
        aviso.style.inset = '0';
        aviso.style.display = 'flex';
        aviso.style.alignItems = 'center';
        aviso.style.justifyContent = 'center';
        aviso.style.padding = '1.5rem';
        aviso.style.backgroundColor =
            'rgba(15, 23, 42, 0.70)';
        aviso.style.color = '#ffffff';
        aviso.style.textAlign = 'center';
        aviso.style.fontWeight = '700';
        aviso.style.zIndex = '9999';

        document.body.appendChild(aviso);
    };


    /* ========================================================
       NIVEL 4 — INFORMAR A PHP QUE EL USUARIO SIGUE ACTIVO

       El controlador existente recibe:
       POST act=1

       CAuth::iniciar(true) actualiza la actividad de la sesión.

       La petición se controla para evitar envíos simultáneos.

       La marca de última notificación se actualiza solamente
       después de recibir una respuesta válida del servidor.
    ======================================================== */

    const informarActividad = () => {

        const ahora = Date.now();

        /* Ya existe una petición de actividad en curso. */
        if (actividadEnviando) {
            return;
        }

        /*
           Cuando el aviso no está visible, se evita realizar
           varias peticiones prácticamente al mismo tiempo.
        */
        if (
            !aviso &&
            ahora - ultimaNotificacion < 1000
        ) {
            return;
        }

        actividadEnviando = true;

        fetch(rutaAct, {
            method: 'POST',
            body: new URLSearchParams({
                act: '1'
            }),
            credentials: 'same-origin',
            cache: 'no-store',
            keepalive: true,
            headers: {
                Accept: 'application/json'
            }
        })
            .then(respuesta => {

                if (!respuesta.ok) {
                    throw new Error(
                        `HTTP ${respuesta.status}`
                    );
                }

                return respuesta.json();
            })
            .then(resultado => {

                if (resultado.ok !== true) {
                    throw new Error(
                        'La sesión no pudo renovarse.'
                    );
                }

                /*
                   La última actividad queda marcada solamente
                   después de confirmar que PHP respondió correctamente.
                */
                ultimaNotificacion = Date.now();
            })
            .catch(() => {})
            .finally(() => {

                actividadEnviando = false;
            });
    };


    /* ========================================================
       NIVEL 4 — CERRAR SESIÓN
    ======================================================== */

    const cerrarSesion = () => {

        window.NURBAK_SINC?.emit(
            'sesion:cerrando',
            {
                motivo: 'inactividad'
            }
        );

        const url = new URL(
            window.location.href
        );

        url.searchParams.set(
            'salir',
            '1'
        );

        window.location.href =
            url.toString();
    };


    /* ========================================================
       NIVEL 4 — REINICIAR EL CONTADOR
    ======================================================== */

    const iniciarContador = () => {

        window.clearTimeout(
            temporizadorAviso
        );

        window.clearTimeout(
            temporizadorCierre
        );

        if (aviso) {
            aviso.remove();
            aviso = null;
        }

        temporizadorAviso =
            window.setTimeout(
                mostrarAviso,
                tiempoAviso
            );

        temporizadorCierre =
            window.setTimeout(() => {
                cerrarSesion();
            }, tiempoCierre);
    };


    /* ========================================================
       NIVEL 4 — RECONOCER LA ACTIVIDAD DEL USUARIO
    ======================================================== */

    const registrarActividad = () => {

        iniciarContador();

        informarActividad();
    };


    /* ========================================================
       ACTIVIDADES DEL USUARIO
    ======================================================== */

    document.addEventListener(
        'mousemove',
        registrarActividad
    );

    document.addEventListener(
        'keydown',
        registrarActividad
    );

    document.addEventListener(
        'touchstart',
        registrarActividad
    );

    document.addEventListener(
        'click',
        registrarActividad
    );

    document.addEventListener(
        'scroll',
        registrarActividad
    );


    /* ========================================================
       NIVEL FINAL — COMENZAR EL PRIMER CONTADOR
    ======================================================== */

    iniciarContador();

});