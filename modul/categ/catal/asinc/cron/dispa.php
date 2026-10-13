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
            return false;
        }

        /* ====================================================
           NIVEL 2 — RESOLVER PHP CLI
        ==================================================== */

        $php = PHP_BINARY;

        if (!is_file($php)) {
            return false;
        }

        $comando = escapeshellarg($php) . ' ' .
                   escapeshellarg($worker) . ' ' .
                   escapeshellarg((string) $id);

        /* ====================================================
           NIVEL 2 — LANZAR EN SEGUNDO PLANO
        ==================================================== */

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = getenv('COMSPEC')
                ?: 'C:\\Windows\\System32\\cmd.exe';

            if (!is_file($cmd)) {
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

            return $codigo === 0;
        }

        exec($comando . ' > /dev/null 2>&1 &');
        return true;
    }
}
