<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/coord/coord.php
   Módulo: Catálogo / ASINC / COORD
   Función: Mantener el ciclo automático de ASINC.
   Ejecución: CLI.

   RESPONSABILIDAD:
   - Mantener un solo coordinador activo.
   - Recuperar procesos PROCESANDO inconsistentes.
   - Ejecutar CRON para cada proceso PENDIENTE.
   - Esperar la finalización de WORKER.
   - Continuar con el siguiente proceso.
   - Finalizar cuando la cola queda vacía.

   NO HACE:
   - No crea trabajos.
   - No procesa XLSX.
   - No procesa lotes.
============================================================ */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI solamente.\n");
}

require_once dirname(__DIR__, 5) . '/contr/categ/catal-db.php';
require_once dirname(__DIR__) . '/job/repos.php';
require_once dirname(__DIR__) . '/job/confi.php';
require_once __DIR__ . '/busca.php';


/* ========================================================
   NIVEL 1 — CONEXIÓN
======================================================== */

$db = CATALDB::abrir();
$busca = new CoordBusca($db);
$confi = require dirname(__DIR__) . '/job/confi.php';

$tiempo = (int) ($confi['tiempo'] ?? 300);

if ($tiempo < 1) {
    $tiempo = 300;
}


/* ========================================================
   NIVEL 1 — LOCK ÚNICO
======================================================== */

$lockPath = __DIR__ . '/coord.lock';
$lock = fopen($lockPath, 'c+');

if ($lock === false) {
    exit("COORD_LOCK_ERROR\n");
}

if (!flock($lock, LOCK_EX | LOCK_NB)) {
    fclose($lock);
    exit("COORD_OCUPADO\n");
}


/* ========================================================
   NIVEL 1 — CALCULAR EDAD
======================================================== */

$edad = static function (?string $fecha): int {
    if (!$fecha) {
        return PHP_INT_MAX;
    }

    $marca = strtotime($fecha);

    if ($marca === false) {
        return PHP_INT_MAX;
    }

    return max(0, time() - $marca);
};


try {

    /* ====================================================
       NIVEL 1 — CICLO PRINCIPAL
    ==================================================== */

    while (true) {

        /* ==================================================
           NIVEL 2 — RECUPERAR PROCESO ACTIVO
        ================================================== */

        $activo = $busca->proc();

        if ($activo) {

            $id = (int) $activo['id'];
            $res = $busca->resum($id);

            /* ==============================================
               NIVEL 3 — YA TERMINÓ PERO NO FUE CERRADO
            ============================================== */

            if ((int) $activo['proce'] >= (int) $activo['total']) {
                $estado = (int) $activo['erro'] > 0
                    ? 'COMPLETADO_ERROR'
                    : 'COMPLETADO';

                $mens = $estado === 'COMPLETADO'
                    ? 'Proceso completado.'
                    : 'Proceso completado con errores.';

                (new AsinRepo($db))->finalizar(
                    $id,
                    $estado,
                    $mens
                );

                continue;
            }

            /* ==============================================
               NIVEL 3 — INCONSISTENCIA SIN LOTES
            ============================================== */

            $lotes =
                $res['pend']
                + $res['proce']
                + $res['comp']
                + $res['erro'];

            if ($lotes < 1) {
                $busca->fallo(
                    $id,
                    'ASINC_INCONSISTENCIA: el proceso no tiene lotes.'
                );

                continue;
            }

            /* ==============================================
               NIVEL 3 — NO QUEDA NADA EJECUTABLE
            ============================================== */

            if ($res['proce'] === 0 && $res['pend'] === 0) {
                $busca->fallo(
                    $id,
                    'ASINC_INCONSISTENCIA: no existen lotes ejecutables.'
                );

                continue;
            }

            /* ==============================================
               NIVEL 3 — PROCESO DETENIDO O BLOQUEADO
            ============================================== */

            if ($edad((string) $activo['fechi']) > $tiempo) {
                $busca->fallo(
                    $id,
                    'ASINC_TIMEOUT: el proceso superó el tiempo máximo de ejecución.'
                );

                continue;
            }

            /* ==============================================
               NIVEL 3 — WORKER TODAVÍA EN EJECUCIÓN
            ============================================== */

            usleep(500000);
            continue;
        }


        /* ==================================================
           NIVEL 2 — BUSCAR SIGUIENTE PENDIENTE
        ================================================== */

        $trab = $busca->pendiente();

        if (!$trab) {
            echo "COORD_FINALIZADO\n";
            break;
        }

        $id = (int) $trab['id'];


        /* ==================================================
           NIVEL 2 — VALIDAR LOTES
        ================================================== */

        $res = $busca->resum($id);

        if ((int) $trab['total'] > 0 && $res['pend'] === 0) {
            $busca->fallo(
                $id,
                'ASINC_INCONSISTENCIA: el proceso no tiene lotes pendientes.'
            );

            continue;
        }


        /* ==================================================
           NIVEL 2 — RUTA CRON
        ================================================== */

        $cron =
            dirname(__DIR__, 5)
            . '/modul/categ/catal/asinc/cron/crono.php';

        if (!is_file($cron)) {
            $busca->fallo(
                $id,
                'COORD_ERROR: CRON no existe.'
            );

            continue;
        }


        /* ==================================================
           NIVEL 2 — PHP CLI
        ================================================== */

        $php = PHP_BINARY;

        if (!is_file($php)) {
            $busca->fallo(
                $id,
                'COORD_ERROR: PHP CLI no existe.'
            );

            continue;
        }


        /* ==================================================
           NIVEL 2 — EJECUTAR CRON
        ================================================== */

        $comando =
            escapeshellarg($php)
            . ' '
            . escapeshellarg($cron);

        $salida = [];
        $codigo = 0;

        exec(
            $comando,
            $salida,
            $codigo
        );

        $resultado = trim(
            implode("\n", $salida)
        );


        /* ==================================================
           NIVEL 2 — VALIDAR CRON
        ================================================== */

        if (
            $codigo !== 0
            || !str_starts_with(
                $resultado,
                'DESPACHADO '
            )
        ) {
            $detalle = $resultado !== ''
                ? $resultado
                : 'Sin respuesta de CRON.';

            $busca->fallo(
                $id,
                'COORD_ERROR: ' . $detalle
            );

            continue;
        }


        /* ==================================================
           NIVEL 2 — VALIDAR ID DESPACHADO
        ================================================== */

        $despachado = (int) trim(
            substr(
                $resultado,
                strlen('DESPACHADO ')
            )
        );

        if ($despachado !== $id) {
            $busca->fallo(
                $id,
                'COORD_ERROR: CRON despachó un proceso distinto al esperado.'
            );

            continue;
        }


        /* ==================================================
           NIVEL 2 — ESPERAR WORKER
        ================================================== */

        while (true) {
            usleep(500000);

            $actual = $busca->obtener($id);

            if (!$actual) {
                echo
                    'COORD_ERROR TRABAJO_NO_EXISTE '
                    . $id
                    . "\n";

                break 2;
            }

            $estado = strtoupper(
                (string) $actual['estado']
            );

            if ($estado === 'PENDIENTE') {
                $busca->fallo(
                    $id,
                    'COORD_ERROR: el proceso volvió a PENDIENTE durante la ejecución.'
                );

                break;
            }

            if ($estado !== 'PROCESANDO') {
                break;
            }

            if ($edad((string) $actual['fechi']) > $tiempo) {
                $busca->fallo(
                    $id,
                    'ASINC_TIMEOUT: WORKER no finalizó el proceso dentro del tiempo permitido.'
                );

                break;
            }
        }
    }

} catch (Throwable $e) {
    echo 'COORD_ERROR ' . $e->getMessage() . "\n";

} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
