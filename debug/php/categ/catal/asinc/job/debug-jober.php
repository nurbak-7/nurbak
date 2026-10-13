<?php
/* ============================================================
   Ruta: debug/php/categ/catal/asinc/job/debug-jober.php
   Módulo: DEBUG / ASINC / JOB
   Función: Diagnosticar exclusivamente el estado persistido
   del proceso correspondiente al área JOB.

   RESPONSABILIDAD:
   - Consultar el proceso.
   - Consultar sus lotes.
   - Consultar sus errores.
   - Obtener el diagnóstico desde DEBUG ASINC.
   - Entregar información propia de JOB.

   NO HACE:
   - No crea procesos.
   - No crea lotes.
   - No modifica procesos.
   - No modifica lotes.
   - No ejecuta JOB.
   - No participa en el flujo funcional de ASINC.
============================================================ */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/debug-asinc.php';

/* ============================================================
   NIVEL 0
   LOG PROPIO DEL ÁREA

   El diagnóstico del área mantiene su propio log.
   No utiliza el log del coordinador ASINC.
============================================================ */
function logArea(string $paso, array $datos = []): void
{
    $extra = $datos === []
        ? ''
        : (string) json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

    $linea = sprintf(
        '[%s] %s%s%s',
        date('Y-m-d H:i:s'),
        $paso,
        $extra !== '' ? ' ' : '',
        $extra
    ) . PHP_EOL;

    $log = __DIR__ . '/debug-jober.log';

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
        __DIR__ . '/debug-jober.log',
        $linea,
        FILE_APPEND | LOCK_EX
    );
}

$id = (int) ($_GET['id'] ?? 0);

try {

    /* ========================================================
       NIVEL 1
       CONEXIÓN Y PROCESO
    ======================================================== */

    $db = AsincDebug::db();

    $proceso = AsincDebug::proceso(
        $db,
        $id
    );

    /* ========================================================
       NIVEL 2
       INFORMACIÓN PERSISTIDA
    ======================================================== */

    $lotes = [];

    $errores = [];

    if ($proceso) {
        $procesoId = (int) $proceso['id'];

        $lotes = AsincDebug::lotes(
            $db,
            $procesoId
        );

        $errores = AsincDebug::errores(
            $db,
            $procesoId
        );
    }

    /* ========================================================
       NIVEL 3
       DIAGNÓSTICO
    ======================================================== */

    $diag = AsincDebug::diagnostico(
        $proceso,
        $lotes,
        $errores
    );

    $areaDiag = $diag['areas']['job'] ?? [];

    if (is_array($areaDiag)) {
        $areaEstado = (string) ($areaDiag['estado'] ?? '');
        $areaPista = (string) ($areaDiag['pista'] ?? '');
    } else {
        $areaEstado = (string) $areaDiag;
        $areaPista = '';
    }

    $falla = (string) ($diag['falla'] ?? '');

    /* ========================================================
       NIVEL 4
       FIRMA DEL ESTADO OBSERVADO
    ======================================================== */

    $firma = AsincDebug::firma([
        'area' => 'job',
        'id' => $proceso
            ? (int) $proceso['id']
            : 0,
        'estado' => $areaEstado,
        'pista' => $areaPista,
        'falla' => $falla,
        'proceso' => $proceso,
        'lotes' => $diag['lotes'] ?? null,
        'errores' => $diag['errores'] ?? null,
    ]);

    /* ========================================================
       NIVEL 5
       REGISTRO DE CAMBIO

       Solo registra ENTRADA/SALIDA cuando la firma
       observada cambia.
    ======================================================== */

    if (AsincDebug::registrar($firma)) {

        logArea(
            'ENTRADA',
            [
                'area' => 'job',
                'id_solicitado' => $id,
            ]
        );

        logArea(
            'SALIDA',
            [
                'area' => 'job',
                'id_recibido' => $proceso
                    ? (int) $proceso['id']
                    : 0,
                'estado' => $areaEstado,
            ]
        );
    }

    /* ========================================================
       NIVEL 6
       RESPUESTA DEBUG
    ======================================================== */

    echo json_encode(
        [
            'area' => 'job',
            'proceso' => $proceso,
            'id' => $proceso
                ? (int) $proceso['id']
                : 0,
            'lotes' => $diag['lotes'] ?? [],
            'salida' => $areaEstado,
            'firma' => $firma,
            'pista' => $areaPista,
            'falla' => $falla === 'JOB'
                ? 'JOB'
                : 'NINGUNA',
            'ubicacion' => $falla === 'JOB'
                ? ($diag['ubicacion'] ?? '')
                : 'Sin falla localizada en JOB.',
        ],
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );

} catch (Throwable $e) {

    /* ========================================================
       NIVEL 7
       ERROR DEL PROPIO DEBUG

       No se atribuye el error a jober.php automáticamente.
       El error pertenece al diagnóstico mientras no exista
       información persistida que permita localizarlo.
    ======================================================== */

    AsincDebug::log(
        'ENTRADA',
        [
            'area' => 'job',
            'id_solicitado' => $id,
        ]
    );

    AsincDebug::log(
        'SALIDA',
        [
            'area' => 'job',
            'id_recibido' => $id,
            'estado' => 'ERROR',
            'mensaje' => $e->getMessage(),
        ]
    );

    http_response_code(500);

    echo json_encode(
        [
            'area' => 'job',
            'id' => $id,
            'salida' => 'ERROR',
            'pista' => $e->getMessage(),
            'falla' => 'DEBUG',
            'ubicacion' => 'debug/php/categ/catal/asinc/job/debug-jober.php',
            'error' => $e->getMessage(),
        ],
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );
}