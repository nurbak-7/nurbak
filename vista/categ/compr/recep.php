<?php
declare(strict_types=1);
?>
<section class="rece-rece" data-rece>
    <div class="rece-cabe">
        <input
            type="search"
            placeholder="Buscar recepción, proveedor o almacén..."
            data-rece-busc
            autocomplete="off"
        >

        <button type="button" data-rece-nuevo>
            Nueva recepción
        </button>
    </div>

    <div class="rece-tabla-cont">
        <table class="rece-tabla">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Proveedor</th>
                    <th>Almacén</th>
                    <th>Compra</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Observación</th>
                    <th>Acción</th>
                </tr>
            </thead>

            <tbody data-rece-cuerpo></tbody>
        </table>

        <p data-rece-mensaje>
            Cargando recepciones...
        </p>
    </div>
</section>
