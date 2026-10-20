<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/cron/dispa.php
   Módulo: Catálogo / ASINC / CRON
   Función: Solicitar al WORKER la ejecución de un trabajo.
============================================================ */
declare(strict_types=1);

final class CronDispa
{
    /* ========================================================
       NIVEL 1 — DESPACHAR WORKER
    ======================================================== */

    public function ejecutar(int $id): bool
    {
        $root = dirname(__DIR__, 5);
        $worker = $root . '/modul/categ/catal/asinc/worke/worke.php';

        if (!is_file($worker)) {
            echo "WORKER_ERROR | No existe el archivo: {$worker}\n";
            return false;
        }

        /* ====================================================
           NIVEL 2 — RESOLVER PHP CLI
        ==================================================== */

        $php = PHP_BINARY;

        if (!is_file($php)) {
            echo "WORKER_ERROR | No existe PHP CLI: {$php}\n";
            return false;
        }

        $comando = escapeshellarg($php) . ' ' .
                   escapeshellarg($worker) . ' ' .
                   escapeshellarg((string) $id);

        /* ====================================================
           NIVEL 2 — WINDOWS
           Mantener el funcionamiento de XAMPP.
        ==================================================== */

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = getenv('COMSPEC')
                ?: 'C:\\Windows\\System32\\cmd.exe';

            if (!is_file($cmd)) {
                echo "WORKER_ERROR | No existe el intérprete de Windows.\n";
                return false;
            }

            $lanzar =
                '"' . $cmd . '" /D /C start "" /B '
                . $comando
                . ' >NUL 2>&1';

            exec(
                $lanzar,
                $salida,
                $codigo
            );

            if ($codigo !== 0) {
                echo "WORKER_ERROR | No se pudo iniciar WORKER. Código: {$codigo}\n";
                return false;
            }

            return true;
        }

        /* ====================================================
           NIVEL 2 — PREPARAR EJECUCIÓN EN RENDER
           Capturar STDOUT y STDERR para transmitir el error
           al coordinador y a la ventana IMEXT.
        ==================================================== */

        if (!function_exists('proc_open')) {
            echo "WORKER_ERROR | proc_open no está disponible.\n";
            return false;
        }

        try {
            $confi = require dirname(__DIR__) . '/job/confi.php';
        } catch (Throwable $e) {
            echo 'WORKER_ERROR | CONFIGURACION | ' . $e->getMessage() . "\n";
            return false;
        }

        $tiempo = (int) ($confi['tiempo'] ?? 300);

        if ($tiempo < 1) {
            $tiempo = 300;
        }

        $args = [
            $php,
            '-d',
            'display_errors=1',
            '-d',
            'display_startup_errors=1',
            '-d',
            'log_errors=0',
            '-d',
            'error_reporting=-1',
            $worker,
            (string) $id,
        ];

        $descriptores = [
            0 => ['file', '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        try {
            $proceso = proc_open(
                $args,
                $descriptores,
                $tuberias
            );
        } catch (Throwable $e) {
            echo 'WORKER_ERROR | INICIO | ' . $e->getMessage() . "\n";
            return false;
        }

        if (!is_resource($proceso)) {
            echo "WORKER_ERROR | PHP no pudo iniciar WORKER.\n";
            return false;
        }

        /* ====================================================
           NIVEL 2 — CAPTURAR SALIDA DEL WORKER
        ==================================================== */

        stream_set_blocking($tuberias[1], false);
        stream_set_blocking($tuberias[2], false);

        $salida = '';
        $error = '';
        $inicio = microtime(true);
        $superado = false;

        $estado = proc_get_status($proceso);

        while ($estado['running']) {
            $texto = stream_get_contents($tuberias[1]);

            if (is_string($texto)) {
                $salida .= $texto;
            }

            $texto = stream_get_contents($tuberias[2]);

            if (is_string($texto)) {
                $error .= $texto;
            }

            if ((microtime(true) - $inicio) > $tiempo) {
                $superado = true;
                proc_terminate($proceso);
                break;
            }

            usleep(100000);
            $estado = proc_get_status($proceso);
        }

        /* ====================================================
           NIVEL 2 — RECUPERAR SALIDA FINAL
        ==================================================== */

        $texto = stream_get_contents($tuberias[1]);

        if (is_string($texto)) {
            $salida .= $texto;
        }

        $texto = stream_get_contents($tuberias[2]);

        if (is_string($texto)) {
            $error .= $texto;
        }

        $codigo = (int) ($estado['exitcode'] ?? -1);

        fclose($tuberias[1]);
        fclose($tuberias[2]);

        $cierre = proc_close($proceso);

        if ($codigo < 0 || $codigo === 0 && $cierre > 0) {
            $codigo = $cierre;
        }

        /* ====================================================
           NIVEL 2 — VALIDAR RESULTADO
        ==================================================== */

        $resultado = trim($salida . "\n" . $error);

        if (
            !$superado
            && $codigo === 0
            && str_contains($resultado, "FINALIZADO {$id}")
        ) {
            return true;
        }

        /* ====================================================
           NIVEL 2 — DEVOLVER ERROR AL COORDINADOR
        ==================================================== */

        if ($superado) {
            $detalle = "ASINC_TIMEOUT | WORKER superó {$tiempo} segundos.";
        } elseif ($resultado !== '') {
            $detalle = preg_replace('/\s+/', ' ', $resultado) ?? $resultado;
        } else {
            $detalle = "WORKER terminó sin confirmar FINALIZADO {$id}. Código: {$codigo}.";
        }

        echo 'WORKER_ERROR | ' . substr(trim($detalle), 0, 380) . "\n";

        return false;
    }
}