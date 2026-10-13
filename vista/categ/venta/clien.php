<?php
/* ============================================================
   Ruta: vista/categ/venta/clie.php
   Módulo: Ventas / Clientes

   FUNCIÓN:
   Interfaz para consultar y registrar clientes.

   La vista NO consulta MySQL. El JavaScript llama al controlador
   modul/categ/venta/clien/clien-cont.php.
============================================================ */
?>
<section class="vent-panel" data-clie>
    <div class="vent-barra">
        <input
            type="search"
            placeholder="Buscar cliente..."
            data-clie-busc
            autocomplete="off"
        >

        <button type="button" data-clie-nuevo>
            Nuevo cliente
        </button>
    </div>

    <table class="vent-tabla">
        <thead>
            <tr>
                <th>ID</th>
                <th>Documento</th>
                <th>Nombre / Razón social</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Estado</th>
            </tr>
        </thead>

        <tbody data-clie-cuerpo></tbody>
    </table>

    <p class="vent-mensaje" data-clie-mensaje>
        Sin registros
    </p>
</section>
