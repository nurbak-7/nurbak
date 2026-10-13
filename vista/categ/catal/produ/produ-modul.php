<?php
/* ============================================================
   Ruta: vista/categ/catal/produ/produ/produ-modul.php
   Módulo: Catálogo / Productos

   FUNCIÓN:
   Vista principal de Productos.

   RESPONSABILIDAD:
   - Buscador.
   - Botón Nuevo.
   - Tabla.
   - Mensaje de estado.

   NO CONTIENE:
   - Formulario MINI.
   - SQL.
   - Lógica de persistencia.
============================================================ */
declare(strict_types=1);
?>
<section class="prod" data-prod>
    <div class="prod-cab">
        <div class="prod-busc">
            <label class="prod-etiq" for="prod-busc">Buscar productos</label>
            <input
                id="prod-busc"
                class="prod-entr"
                type="search"
                placeholder="Buscar descripción, código, original, medida..."
                autocomplete="off"
                data-prod-busc
            >
        </div>
        <button type="button" class="prod-nuevo" data-prod-nuevo>NUEVO</button>
    </div>

    <div class="prod-tabl-cont">
        <table class="prod-tabl">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>DESCRIPCION</th>
                    <th>CODIGO</th>
                    <th>ORIGINAL</th>
                    <th>MEDIDA</th>
                    <th>SUPER</th>
                    <th>EQUIVALENTE</th>
                    <th>PCOMP</th>
                    <th>PVENT</th>
                    <th>ACCIÓN</th>
                </tr>
            </thead>
            <tbody data-prod-cuer></tbody>
        </table>
        <p class="prod-vaci" data-prod-vaci>Cargando productos...</p>
    </div>
</section>
