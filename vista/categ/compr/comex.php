<?php
/* ============================================================
   Ruta: vista/categ/compr/comex.php
   Módulo: Compras / Compra externa
============================================================ */
declare(strict_types=1);
?>
<section class="comex" data-comx>
    <div class="cpbar">
        <input
            type="search"
            placeholder="Buscar compra..."
            data-comx-busc
            autocomplete="off"
        >

        <button type="button" data-comx-nuev>
            Nueva compra
        </button>
    </div>

    <div class="cptc">
        <table class="cptab">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Proveedor</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Observación</th>
                </tr>
            </thead>

            <tbody data-comx-cuer></tbody>
        </table>

        <p data-comx-mens>
            Cargando...
        </p>
    </div>
</section>
