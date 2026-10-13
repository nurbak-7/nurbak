<?php
/* ============================================================
   Ruta: vista/categ/catal/imext/imext.php
   Módulo: Catálogo / ASINC / IMEXT
   Función: Coordinar la vista de importación y exportación.

   IMEXT es el único submódulo visible del menú.
   IMPORT y EXPORT son funciones internas de IMEXT.
============================================================ */
declare(strict_types=1);

/* ============================================================
   NIVEL 1 — ACTIVAR COORDINADOR

   IMEXT es el punto de entrada visible del procesamiento
   asíncrono del catálogo. Al abrir esta ventana se activa
   el coordinador para recuperar y procesar la cola existente.

   El coordinador utiliza un lock no bloqueante, por lo que
   varios usuarios pueden abrir IMEXT sin crear coordinadores
   simultáneos.
============================================================ */
require_once dirname(__DIR__, 4)
    . '/modul/categ/catal/asinc/coord/desp.php';

(new CoordDesp())->activar();
?>
<section class="imext" data-imext>

    <header class="imext-cab">
        <h2>Importar / Exportar</h2>
        <p>Procesos masivos del catálogo.</p>
    </header>

    <div class="imext-contenido">

        <?php require __DIR__ . '/impor.php'; ?>

        <?php require __DIR__ . '/expor.php'; ?>

    </div>

</section>
