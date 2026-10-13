<?php
/* ============================================================
   Ruta: index.php
   Función: Entrada raíz del sistema y conexión automática con
   el DEBUG raíz.

   RESPONSABILIDAD:
   - Mantener el flujo funcional existente.
   - Registrar la entrada al sistema en el DEBUG.
   - Cargar publi/index.php sin modificarlo.
   - Conectar el coordinador raíz del DEBUG a la respuesta HTML.

   El DEBUG permanece físicamente independiente dentro de:

   debug/

   No se carga ningún archivo funcional desde DEBUG.
============================================================ */

declare(strict_types=1);

/* ============================================================
   NIVEL 1
   Registrar la entrada al sistema en el LOG raíz del DEBUG.
============================================================ */

$debugLog = __DIR__ . '/debug/php/debug-layo.log';
$debugDir = dirname($debugLog);

if (is_dir($debugDir)) {
    $debugCtx = [
        'metodo' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        'ruta'   => $_SERVER['REQUEST_URI'] ?? '/',
        'script' => $_SERVER['SCRIPT_NAME'] ?? '/index.php'
    ];

    $debugDat = (string) json_encode(
        $debugCtx,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_PARTIAL_OUTPUT_ON_ERROR
    );

    $debugLine = sprintf(
        '[%s] INDEX_ACTI %s%s',
        date('Y-m-d H:i:s'),
        $debugDat !== '' ? $debugDat : '{}',
        PHP_EOL
    );

    @file_put_contents(
        $debugLog,
        $debugLine,
        FILE_APPEND | LOCK_EX
    );
}

/* ============================================================
   NIVEL 2
   Ejecutar exactamente el flujo público existente.

   No se modifica publi/index.php ni ningún archivo funcional.
============================================================ */

ob_start();

require __DIR__ . '/publi/index.php';

$salida = (string) ob_get_clean();

/* ============================================================
   NIVEL 3
   Conectar automáticamente el coordinador raíz del DEBUG.

   /debug/ conserva su propia entrada independiente y por eso
   no recibe una segunda inyección desde este punto.
============================================================ */

$uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$rutaUri = (string) (parse_url($uri, PHP_URL_PATH) ?? '/');
$esDebug = $rutaUri === '/debug' || str_starts_with($rutaUri, '/debug/');


$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$debugEnv = getenv('APP_DEBUG');
$esLoc = $host === 'localhost' || str_starts_with($host, 'localhost:')
    || $host === '127.0.0.1' || str_starts_with($host, '127.0.0.1:');
$debugAct = $debugEnv === '1' || ($debugEnv === false && $esLoc);

$esHtml = stripos($salida, '<html') !== false
    || stripos($salida, '<!doctype') !== false
    || stripos($salida, '<body') !== false;

if ($debugAct && !$esDebug && $esHtml && stripos($salida, '/debug/js/debug-layo.js') === false) {
    $js = '<script src="/debug/js/debug-layo.js" defer></script>';

    if (stripos($salida, '<head') !== false) {
        $salida = preg_replace(
            '/(<head\b[^>]*>)/i',
            '$1' . $js,
            $salida,
            1
        );
    } elseif (stripos($salida, '</body>') !== false) {
        $salida = preg_replace(
            '/<\/body>/i',
            $js . '</body>',
            $salida,
            1
        );
    } else {
        $salida .= $js;
    }
}

/* ============================================================
   NIVEL FINAL
   Entregar al navegador la respuesta original más la conexión
   automática del DEBUG, sin alterar el flujo funcional.
============================================================ */

echo $salida;
