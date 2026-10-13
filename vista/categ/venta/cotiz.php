<?php
/* ============================================================
   Ruta: vista/categ/venta/cotiz.php
   Módulo: Ventas / Cotización

   RESPONSABILIDAD:
   Mostrar exclusivamente la consulta y gestión principal de
   Cotizaciones. La creación/edición se abre en MINI.
============================================================ */
?>
<section class="cotiz-lista" data-cotiz-list>
    <div class="cotiz-lista-barra">
        <input
            type="search"
            placeholder="Buscar cotización..."
            data-cotiz-list-busc
            autocomplete="off"
        >
        <button type="button" data-cotiz-list-nuevo>+ Nueva</button>
    </div>

    <div class="cotiz-lista-tabla">
        <table>
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Vigencia</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody data-cotiz-list-cuerpo></tbody>
        </table>
    </div>

    <p class="cotiz-lista-mensaje" data-cotiz-list-mensaje>Sin registros</p>
</section>
