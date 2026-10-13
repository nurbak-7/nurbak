<?php
/* ============================================================
   Ruta: vista/categ/venta/opera/produc-mini.php
   Módulo: Ventas / Operaciones / Productos / Buscador MINI

   RESPONSABILIDAD:
   Proporcionar únicamente la estructura visual del buscador.
   La consulta y selección pertenecen a produ.js.
============================================================ */
?>

<section class="opera-prod-mini" data-opera-prod-mini>

    <div class="opera-prod-mini-buscador">
        <label for="opera-prod-buscar">BUSCAR PRODUCTO:</label>
        <input
            id="opera-prod-buscar"
            type="search"
            data-opera-prod-buscar
            placeholder="Código, descripción, OEM, medid, motor, model..."
            autocomplete="off"
            autofocus
        >
    </div>

    <div class="opera-prod-mini-info" data-opera-prod-info>
        <span class="opera-prod-mini-resultados" data-opera-prod-resultados-info>
            Sin resultados.
        </span>

        <span class="opera-prod-mini-stock" data-opera-prod-stock-info>
            STOCK: 0
        </span>

        <span class="opera-prod-mini-reservado" data-opera-prod-reservado-info>
            RESERVADO: 0
        </span>

        <span class="opera-prod-mini-disponible" data-opera-prod-disponible-info>
            DISPONIBLE: 0
        </span>
    </div>

    <div class="opera-prod-mini-estad" data-opera-prod-estad></div>

    <div class="opera-prod-mini-tabla-wrap">
        <table class="opera-prod-mini-tabla">
            <thead>
                <tr>
                    <th>MOTOR</th>
                    <th>MODEL</th>
                    <th>CODIG</th>
                    <th>DESCR</th>
                    <th>ORIGI</th>
                    <th>MEDID</th>
                    <th>SUPER</th>
                    <th>AÑO</th>
                    <th>STOCK</th>
                    <th>P.UNIT</th>
                </tr>
            </thead>
            <tbody data-opera-prod-resultados></tbody>
        </table>
    </div>

    <div class="opera-prod-mini-ayuda">
        ↑ ↓ seleccionar · ENTER agregar · ESC cerrar
    </div>

</section>
