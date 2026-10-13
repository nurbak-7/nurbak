<?php
/* ============================================================
   Ruta: vista/categ/catal/vehic-mini.php
   Módulo: Catálogo / Vehículos / MINI
   Función: Formulario de alta y edición.
============================================================ */
$id=(int)($_GET['id']??0); ?>
<form class="catal-mini-form" data-vehi-mini-form data-id="<?= $id ?>">
    <div class="catal-mini-grid">
        <label>Motor *<input name="motor" required autocomplete="off"></label>
        <label>Modelo *<input name="model" required autocomplete="off"></label>
        <label>Año *<input name="anio" required placeholder="08-2000-12" maxlength="11" autocomplete="off"></label>
    </div>
    <small>Formato: 08-2000-12 = model 2008, generación 2000–2012.</small>
    <div class="catal-mini-msg" data-mini-msg></div>
    <div class="catal-mini-acciones"><button type="submit">GUARDAR</button><button type="button" data-mini-cerrar>CERRAR</button></div>
</form>
