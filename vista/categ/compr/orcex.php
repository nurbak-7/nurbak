<?php
/* ============================================================
   Ruta: vista/categ/compr/orcex.php
   Módulo: Compras / Orden Compra Ext
============================================================ */
declare(strict_types=1);
?>
<section class="orcex" data-orcex>
    <div class="orcex-cabe">
        <input type="search" placeholder="Buscar orden..." autocomplete="off" data-orcex-busc>
        <button type="button" data-orcex-nuev>Nueva orden</button>
    </div>

    <div class="orcex-tabla">
        <table>
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Fecha</th>
                    <th>Proveedor</th>
                    <th>Emisor</th>
                    <th>Estado</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody data-orcex-cuer></tbody>
        </table>
        <p data-orcex-mens>Cargando...</p>
    </div>
</section>
