<?php
/* ============================================================
   Ruta: debug/index.php
   Función: Punto de entrada independiente del DEBUG.

   RESPONSABILIDAD:
   - Ejecutar el sistema existente sin modificar su código.
   - Añadir únicamente el coordinador raíz del DEBUG a la respuesta.
   - Mantener el DEBUG fuera de Producción.

   Al eliminar la carpeta debug/, Producción queda intacta.
============================================================ */

declare(strict_types=1);

ob_start();

require dirname(__DIR__) . '/index.php';

$salida = (string) ob_get_clean();

/*
   El sistema funcional trabaja desde la raíz del proyecto.
   Por eso el documento DEBUG conserva la raíz "/" como base.
*/
$base = '<base href="/">';

if (stripos($salida, '<head') !== false) {
    $salida = preg_replace(
        '/(<head\b[^>]*>)/i',
        '$1' . $base,
        $salida,
        1
    );
}

$js = '<script src="debug/js/debug-layo.js" defer></script>';

if (stripos($salida, '</body>') !== false) {
    $salida = preg_replace(
        '/<\/body>/i',
        $js . '</body>',
        $salida,
        1
    );
} else {
    $salida .= $js;
}

echo $salida;
