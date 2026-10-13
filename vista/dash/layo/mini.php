<?php
/* ============================================================
   Ruta: vista/dash/layo/mini.php
   Función: Preparar la estructura reutilizable de la mini-ventana.

   MINI pertenece a DASH y es independiente de ADMI, CATE,
   INVE, VENT y de cualquier otro módulo.

   El coordinador mini.js utiliza esta estructura para crear,
   mover y cerrar las mini-ventanas solicitadas por los módulos.

   🔹 Nivel 1: Crear el área de MINI
   🔹 Nivel 2: Preparar la plantilla reutilizable
   🔹 Nivel 3: Preparar cabecera y control
   🔹 Nivel Final: Reservar el contenido solicitado
============================================================ */
?>

<!-- ==========================================================
     NIVEL 1 — ÁREA DE MINI-VENTANAS
========================================================== -->
<div id="dash-mini-area" class="dash-mini-area" aria-live="polite"></div>

<!-- ==========================================================
     NIVEL 2 — PLANTILLA DE MINI-VENTANA
========================================================== -->
<template id="dash-mini-plantilla">
    <section class="dash-mini" data-mini-ventana>

        <!-- ==================================================
             NIVEL 3 — CABECERA
        ================================================== -->
        <header class="dash-mini-head" data-mini-arrastre>
            <h2 class="dash-mini-titulo" data-mini-titulo>Mini-ventana</h2>

            <div class="dash-mini-acciones">
                <button
                    type="button"
                    data-mini-accion="cerrar"
                    aria-label="Cerrar mini-ventana"
                    title="Cerrar"
                >×</button>
            </div>
        </header>

        <!-- ==================================================
             NIVEL FINAL — CONTENIDO
        ================================================== -->
        <div class="dash-mini-contenido" data-mini-contenido>
            <p>Cargando contenido...</p>
        </div>

    </section>
</template>
