<?php
/* ============================================================
   Ruta: vista/admin/tiend/tiend-alma.php
   Módulo: Administración / Tiendas / Almacenes
   Función: Administrar los almacenes de una tienda.
============================================================ */
?>

<section class="admi-tiend-alma" data-tiend-alma>
    <div class="admi-tiend-alma-cab">
        <div>
            <strong data-tiend-alma-tit>ALMACENES</strong>
            <span data-tiend-alma-est></span>
        </div>
        <button type="button" data-tiend-alma-nuevo>NUEVO ALMACÉN</button>
    </div>

    <div class="admi-tiend-alma-info" data-tiend-alma-info></div>

    <div class="admi-tiend-alma-tabla">
        <table>
            <thead>
                <tr>
                    <th>NOMBRE</th>
                    <th>DIRECCIÓN</th>
                    <th>TIPO</th>
                    <th>ESTADO</th>
                    <th>ACCIÓN</th>
                </tr>
            </thead>
            <tbody data-tiend-alma-lista>
                <tr><td colspan="5">Cargando...</td></tr>
            </tbody>
        </table>
    </div>

    <form class="admi-tiend-alma-form" data-tiend-alma-form hidden>
        <div>
            <label>NOMBRE *</label>
            <input type="text" name="nombr" maxlength="100" required autocomplete="off">
        </div>
        <div>
            <label>DESCRIPCIÓN</label>
            <input type="text" name="descr" maxlength="200" autocomplete="off">
        </div>
        <div>
            <label>DIRECCIÓN</label>
            <input type="text" name="direc" maxlength="200" autocomplete="off">
        </div>
        <label class="admi-tiend-alma-check">
            <input type="checkbox" name="prin" value="1">
            Crear como almacén principal
        </label>
        <div class="admi-tiend-form-acciones">
            <button type="button" data-tiend-alma-cancelar>CANCELAR</button>
            <button type="submit">GUARDAR</button>
        </div>
    </form>

    <div class="admi-tiend-usuarios-acciones">
        <button type="button" data-tiend-alma-cerrar>CERRAR</button>
    </div>
</section>
