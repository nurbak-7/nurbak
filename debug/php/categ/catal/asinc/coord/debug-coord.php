<?php
/* ============================================================
   Ruta: debug/php/categ/catal/asinc/coord/debug-coord.php
   Módulo: DEBUG / ASINC / COORD
   Función: Diagnosticar exclusivamente el estado observable
   del coordinador, la cola y el entorno PHP web.

   RESPONSABILIDAD:
   - Consultar el proceso persistido.
   - Consultar el estado de la cola.
   - Detectar el lock del coordinador.
   - Diagnosticar el entorno PHP que ejecuta Apache.
   - Comprobar existencia de PHP CLI y coord.php.
   - Informar funciones de ejecución disponibles.

   NO HACE:
   - No ejecuta COORD.
   - No ejecuta CRON.
   - No ejecuta WORKE.
   - No modifica procesos, lotes ni errores.
============================================================ */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/debug-asinc.php';


/* ========================================================
   NIVEL 1 — LOG
======================================================== */

function logArea(string $paso, array $datos = []): void
{
    $extra = $datos === []
        ? ''
        : (string) json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

    $linea = sprintf(
        '[%s] %s%s%s',
        date('Y-m-d H:i:s'),
        $paso,
        $extra !== '' ? ' ' : '',
        $extra
    ) . PHP_EOL;

    $log = __DIR__ . '/debug-coord.log';

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
        __DIR__ . '/debug-coord.log',
        $linea,
        FILE_APPEND | LOCK_EX
    );
}


/* ========================================================
   NIVEL 1 — DATOS SOLICITADOS
======================================================== */

$id = (int) ($_GET['id'] ?? 0);


try {

    /* ====================================================
       NIVEL 2 — BASE DE DATOS
    ==================================================== */

    $db = AsincDebug::db();

    $proceso = AsincDebug::proceso(
        $db,
        $id
    );


    /* ====================================================
       NIVEL 2 — COLA PENDIENTE
    ==================================================== */

    $pend = $db->query(
        "SELECT id
         FROM asinc
         WHERE estado = 'PENDIENTE'
         ORDER BY id
         LIMIT 1"
    )->fetch();


    /* ====================================================
       NIVEL 2 — COLA PROCESANDO
    ==================================================== */

    $proces = $db->query(
        "SELECT id
         FROM asinc
         WHERE estado = 'PROCESANDO'
         ORDER BY id
         LIMIT 1"
    )->fetch();


    /* ====================================================
       NIVEL 2 — LOCK DEL COORDINADOR
    ==================================================== */

    $lockPath =
        dirname(__DIR__, 5)
        . '/modul/categ/catal/asinc/coord/coord.lock';

    $activo = false;

    $fh = @fopen(
        $lockPath,
        'c+'
    );

    if ($fh !== false) {

        if (!flock(
            $fh,
            LOCK_EX | LOCK_NB
        )) {
            $activo = true;
        } else {
            flock(
                $fh,
                LOCK_UN
            );
        }

        fclose($fh);
    }


    /* ====================================================
       NIVEL 2 — ENTORNO PHP WEB
    ==================================================== */

    $phpBin = PHP_BINARY;
    $phpDir = PHP_BINDIR;

    $phpCli = PHP_OS_FAMILY === 'Windows'
        ? $phpDir . DIRECTORY_SEPARATOR . 'php.exe'
        : $phpDir . DIRECTORY_SEPARATOR . 'php';

    $comspec = getenv('COMSPEC');

    $funciones = [
        'exec'      => function_exists('exec'),
        'popen'     => function_exists('popen'),
        'proc_open' => function_exists('proc_open'),
        'shell_exec'=> function_exists('shell_exec'),
    ];

    $deshab = ini_get('disable_functions');

    $deshab = $deshab === false
        ? ''
        : (string) $deshab;


    /* ====================================================
       NIVEL 2 — ARCHIVOS DE EJECUCIÓN
    ==================================================== */

    $coordPath =
        dirname(__DIR__, 5)
        . '/modul/categ/catal/asinc/coord/coord.php';

    $archivos = [
        'coord' => [
            'ruta' => $coordPath,
            'existe' => is_file($coordPath),
        ],

        'php' => [
            'ruta' => $phpCli,
            'existe' => is_file($phpCli),
        ],

        'cmd' => [
            'ruta' => $comspec ?: '',
            'existe' => $comspec
                ? is_file($comspec)
                : false,
        ],
    ];


    /* ====================================================
       NIVEL 2 — ESTADO OBSERVABLE
    ==================================================== */

    $estado = $activo
        ? 'ACTIVO'
        : (
            $pend
                ? 'PENDIENTE'
                : (
                    $proces
                        ? 'ESPERANDO_FIN'
                        : 'SIN_COLA'
                )
        );


    /* ====================================================
       NIVEL 2 — FIRMA DEBUG
    ==================================================== */

    $firma = AsincDebug::firma([
        'area' => 'coord',
        'id' => $proceso
            ? (int) $proceso['id']
            : 0,
        'coord' => $estado,
        'id_pendiente' => $pend
            ? (int) $pend['id']
            : 0,
        'id_procesando' => $proces
            ? (int) $proces['id']
            : 0,
        'sapi' => PHP_SAPI,
        'binary' => $phpBin,
    ]);


    /* ====================================================
       NIVEL 2 — REGISTRAR
    ==================================================== */

    if (AsincDebug::registrar($firma)) {

        logArea(
            'ENTRADA',
            [
                'area' => 'coord',
                'id_solicitado' => $id,
            ]
        );

        logArea(
            'SALIDA',
            [
                'area' => 'coord',
                'estado' => $estado,
                'id_pendiente' => $pend
                    ? (int) $pend['id']
                    : 0,
                'id_procesando' => $proces
                    ? (int) $proces['id']
                    : 0,
                'sapi' => PHP_SAPI,
                'php_binary' => $phpBin,
            ]
        );
    }


    /* ====================================================
       NIVEL 1 — RESPUESTA
    ==================================================== */

    echo json_encode(
        [
            'area' => 'coord',

            'proceso' => $proceso,

            'id' => $proceso
                ? (int) $proceso['id']
                : 0,

            'salida' => $estado,

            'id_pendiente' => $pend
                ? (int) $pend['id']
                : 0,

            'id_procesando' => $proces
                ? (int) $proces['id']
                : 0,

            'activo' => $activo,

            'lock' => [
                'ruta' => $lockPath,
                'existe' => is_file($lockPath),
            ],

            'php' => [
                'sapi' => PHP_SAPI,
                'binary' => $phpBin,
                'bindir' => $phpDir,
                'cli' => $phpCli,
            ],

            'windows' => [
                'os' => PHP_OS_FAMILY,
                'comspec' => $comspec ?: '',
            ],

            'funciones' => $funciones,

            'disable_functions' => $deshab,

            'archivos' => $archivos,

            'firma' => $firma,

            'falla' => 'NINGUNA',

            'ubicacion' => 'coord/coord.php',
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode(
        [
            'area' => 'coord',
            'salida' => 'ERROR',
            'falla' => 'DEBUG',
            'pista' => $e->getMessage(),
            'error' => $e->getMessage(),
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
}