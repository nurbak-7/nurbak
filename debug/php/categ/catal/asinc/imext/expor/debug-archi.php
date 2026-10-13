<?php
/* ============================================================
   Ruta: debug/php/categ/catal/asinc/imext/expor/debug-archi.php
   Módulo: DEBUG / CATAL / ASINC / IMEXT / EXPOR
   Función: Diagnóstico independiente de archi.

   RESPONSABILIDAD:
   - Recibir evidencia de diagnóstico.
   - Registrar únicamente en su propio LOG.
   - No ejecutar ni modificar el proceso funcional.

   NO HACE:
   - No incluye archivos de modul/.
   - No modifica CATAL, ASINC ni Inventario.
   - No participa en el flujo funcional.
============================================================ */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$log = __DIR__ . '/debug-archi.log';

$reg = static function (string $paso, array $ctx = []) use ($log): void {
    $json = $ctx === []
        ? ''
        : ' ' . (string) json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    /* ============================================================
   NIVEL 6
   DESTROY AUTOMÁTICO DEL LOG

   Si este LOG lleva 180 segundos o más sin recibir eventos,
   se limpia antes de registrar el nuevo evento.
   Cada LOG controla su propia inactividad.
============================================================ */
clearstatcache(true, $log);
if (is_file($log)) {
    $ult = filemtime($log);
    if ($ult !== false && (time() - $ult) >= 180) {
        file_put_contents($log, '', LOCK_EX);
    }
}

file_put_contents(
        $log,
        '[' . date('Y-m-d H:i:s') . '] ' . $paso . $json . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $paso = trim((string) ($_POST['paso'] ?? ''));
    $ctx = $_POST['contexto'] ?? [];
    if (!is_array($ctx)) { $ctx = []; }
    if ($paso === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'PASO requerido'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $reg($paso, $ctx);
    echo json_encode(['ok' => true, 'area' => 'expor', 'func' => 'archi'], JSON_UNESCAPED_UNICODE);
    exit;
}

$reg('CONSULTA', ['area' => 'expor', 'func' => 'archi']);
echo json_encode([
    'ok' => true,
    'area' => 'expor',
    'func' => 'archi',
    'log' => 'debug-archi.log',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
