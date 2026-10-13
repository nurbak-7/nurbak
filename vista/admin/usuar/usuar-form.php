<?php
/* ============================================================
   Ruta: vista/admin/usuar/usuar-form.php
   Función: Mostrar el formulario de usuario dentro de MINI.

   Este archivo pertenece a USUA. MINI únicamente lo presenta.

   🔹 Nivel 1: Mostrar datos de identificación.
   🔹 Nivel 2: Consultar automáticamente el DNI.
   🔹 Nivel 3: Mostrar datos de acceso.
   🔹 Nivel Final: Entregar el formulario a usuar.js.
============================================================ */
?>

<form class="admi-usua-form" data-usua-form data-usua-modo="crear">
    <div class="admi-usua-form-campo">
        <label for="usua-codi">Código</label>
        <input id="usua-codi" type="text" data-usua-campo="codi" readonly>
    </div>

    <div class="admi-usua-form-campo">
        <label for="usua-logi">Usuario</label>
        <input id="usua-logi" type="text" name="logi" data-usua-campo="logi" required autocomplete="username">
    </div>

    <div class="admi-usua-form-campo">
        <label for="usua-dni">DNI</label>
        <input id="usua-dni" type="text" name="dni" data-usua-campo="dni"
               inputmode="numeric" maxlength="8" autocomplete="off" required>
    </div>

    <div class="admi-usua-form-campo">
        <label for="usua-nomb">Nombres</label>
        <input id="usua-nomb" type="text" name="nombr" data-usua-campo="nombr" required>
    </div>

    <div class="admi-usua-form-campo">
        <label for="usua-apel">Apellidos</label>
        <input id="usua-apel" type="text" name="apell" data-usua-campo="apell" required>
    </div>

    <div class="admi-usua-form-campo">
        <label for="usua-celu">Celular</label>
        <input id="usua-celu" type="tel" name="celul" data-usua-campo="celul"
               inputmode="numeric" maxlength="15" autocomplete="tel">
    </div>

    <div class="admi-usua-form-campo">
        <label for="usua-corr">Correo</label>
        <input id="usua-corr" type="email" name="corre" data-usua-campo="corre">
    </div>

    <div class="admi-usua-form-campo">
        <label for="usua-clav">Contraseña</label>
        <input id="usua-clav" type="password" name="clave" data-usua-campo="clave" required autocomplete="new-password">
    </div>

    <div class="admi-usua-form-campo">
        <label for="usua-rol">Rol</label>
        <select id="usua-rol" name="rol" data-usua-campo="rol" required>
            <option value="">Seleccionar...</option>
        </select>
    </div>

    <div class="admi-usua-form-acciones">
        <button type="submit">GUARDAR</button>
    </div>
</form>
