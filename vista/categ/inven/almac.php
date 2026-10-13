<?php
/* ============================================================
   Ruta: vista/categ/inven/almac.php
   Módulo: Inventario / Almacenes
   Función: Administrar ALMAC dentro del contexto TIEND.
============================================================ */
declare(strict_types=1);
?>
<section class="inve-alma" data-alma>
    <div class="inve-cabe">
        <select data-alma-tien aria-label="Tienda">
        </select>

        <input
            type="search"
            placeholder="Buscar almacén..."
            autocomplete="off"
            data-alma-busc
        >

        <button type="button" data-alma-nuevo>
            Nuevo almacén
        </button>
    </div>

    <div class="inve-tabla-cont">
        <table class="inve-tabla">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tienda</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Dirección</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acción</th>
                </tr>
            </thead>

            <tbody data-alma-cuerpo></tbody>
        </table>

        <p class="inve-vacio" data-alma-mensaje>
            Seleccione una tienda.
        </p>
    </div>
</section>
