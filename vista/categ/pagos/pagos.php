<?php
/* ============================================================
   Ruta: vista/categ/pagos/pagos.php
   Módulo: Pagos / Registro de pagos

   FUNCIÓN:
   Mostrar la consulta base del submódulo.
   La vista no consulta MySQL; JavaScript llama al controlador.
============================================================ */
?>
<section class="pago-panel" data-pago-regp>
    <div class="pago-barra">
        <input type="search" placeholder="Buscar..." data-pago-busc autocomplete="off">
    </div>
    <table class="pago-tabla">
        <thead data-pago-cabeza></thead>
        <tbody data-pago-cuerpo></tbody>
    </table>
    <p class="pago-mensaje" data-pago-mensaje>Sin registros</p>
</section>
