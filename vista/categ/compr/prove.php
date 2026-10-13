<?php
/* ============================================================
   Ruta: vista/categ/compr/prove.php
   Módulo: Compras / Proveedores

   RESPONSABILIDAD DE LA VISTA:
   Presentar la interfaz. No consulta bases de datos y no
   contiene reglas de negocio.

   El JavaScript asociado realiza las peticiones al controlador.
============================================================ */
declare(strict_types=1);
?>
<section class="prove-prov" data-prov>
    <div class="prove-cabe">
        <input
            type="search"
            placeholder="Buscar proveedor..."
            autocomplete="off"
            data-prov-busc
        >

        <button type="button" data-prov-nuevo>
            Nuevo proveedor
        </button>
    </div>

    <div class="prove-tabla-cont">
        <table class="prove-tabla">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Razón social</th>
                    <th>RUC</th>
                    <th>Contacto</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>

            <tbody data-prov-cuerpo></tbody>
        </table>

        <p data-prov-mensaje>
            Cargando proveedores...
        </p>
    </div>
</section>
