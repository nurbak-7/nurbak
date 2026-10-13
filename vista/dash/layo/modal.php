<?php
/* ============================================================
   Ruta: vista/dash/layo/modal.php
   Función: Preparar la estructura reutilizable de las modales.

   Este archivo no contiene datos de Productos, Almacenes,
   Facturas ni de otros módulos. Solo prepara los lugares que
   utilizará modal.js para crear y controlar las modales.

   Archivos que trabajan con esta estructura:

   - modal.css → diseña la modal normal, maximizada y minimizada.
   - modal.js  → crea, mueve, maximiza, minimiza y elimina.
   - vista/categ/... → entrega el contenido real de cada módulo.

   Ejemplo del proceso:

   Usuario pulsa Productos
          ↓
   modal.js copia la plantilla
          ↓
   crea una modal centrada
          ↓
   carga vista/categ/catal/produ.php dentro de la modal

   🔹 Nivel 1: Crear el área de trabajo
   🔹 Nivel 2: Preparar una plantilla reutilizable
   🔹 Nivel 3: Preparar la cabecera y sus controles
   🔹 Nivel Final: Reservar el contenido del módulo
============================================================ */
?>

<!--
   🔹 Nivel 1: Área de trabajo de las modales.

   Todas las modales creadas por modal.js se colocarán aquí.
   modal.css mantiene esta área debajo del navbar y dentro del
   tamaño visible del navegador.
-->
<div id="dash-modal-area" class="dash-modal-area" aria-live="polite"></div>

<!--
   🔹 Nivel 2: Plantilla de una modal.

   template no se muestra directamente en la página.
   modal.js realiza una copia cada vez que se abre un módulo.

   Al minimizar, la misma modal se reduce y permanece visible.
   Por eso conserva sus controles para restaurar, maximizar o cerrar.
-->
<template id="dash-modal-plantilla">
    <section class="dash-modal" data-modal>

        <!-- 🔹 Nivel 3: Cabecera usada para mover la modal. -->
        <header class="dash-modal-head" data-modal-arrastre>
            <h2 class="dash-modal-titulo" data-modal-titulo>Modal del sistema</h2>

            <!-- Controles que también permanecen al minimizar. -->
            <div class="dash-modal-acciones">
                <button
                    type="button"
                    data-modal-accion="minimizar"
                    aria-label="Minimizar o restaurar modal"
                    title="Minimizar o restaurar"
                >−</button>

                <button
                    type="button"
                    data-modal-accion="maximizar"
                    aria-label="Maximizar o restaurar modal"
                    title="Maximizar o restaurar"
                >□</button>

                <button
                    type="button"
                    data-modal-accion="cerrar"
                    aria-label="Cerrar y eliminar modal"
                    title="Cerrar"
                >×</button>
            </div>
        </header>

        <!--
           🔹 Nivel Final: Contenido real del módulo.

           modal.js reemplaza el mensaje de carga por el archivo PHP
           relacionado con el submenú seleccionado.
        -->
        <div class="dash-modal-contenido" data-modal-contenido>
            <p>Cargando contenido...</p>
        </div>

    </section>
</template>
