<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/coord/desp.php
   Módulo: Catálogo / ASINC / COORD
   Función: Activar el coordinador en segundo plano.

   RESPONSABILIDAD:
   - Lanzar únicamente coord.php.
   - No buscar trabajos.
   - No ejecutar CRON.
   - No ejecutar WORKE.
   - No modificar la base de datos.
============================================================ */
declare(strict_types=1);

final class CoordDesp
{
    /* ========================================================
       NIVEL 1 — ACTIVAR COORDINADOR
    ======================================================== */

    public function activar(): bool
    {
        $coord = __DIR__
            . DIRECTORY_SEPARATOR
            . 'coord.php';

        if (!is_file($coord)) {
            return false;
        }


        /* ====================================================
           NIVEL 2 — RESOLVER PHP CLI REAL
        ==================================================== */

        $php = $this->php();


        if ($php === null) {
            return false;
        }


        /* ====================================================
           NIVEL 2 — WINDOWS
        ==================================================== */

        if (PHP_OS_FAMILY === 'Windows') {

            $cmd = getenv('COMSPEC');

            if (!$cmd) {
                $cmd = 'C:\\Windows\\System32\\cmd.exe';
            }

            if (!is_file($cmd)) {
                return false;
            }


            $comando =
                '"' . $cmd . '"'
                . ' /D /C start "" /B '
                . '"' . $php . '"'
                . ' "' . $coord . '"'
                . ' >NUL 2>&1';


            exec(
                $comando,
                $salida,
                $codigo
            );


            return $codigo === 0;
        }


        /* ====================================================
           NIVEL 2 — UNIX / LINUX
        ==================================================== */

        /*
         * No descartar la salida del coordinador: si PHP CLI no puede
         * cargar CATAL o falla al arrancar, el proceso termina sin que
         * el endpoint pueda detectar el motivo. La salida queda en /tmp,
         * fuera del código de la aplicación.
         */
        $registro = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'nurbak_asinc_coord.log';

        $comando =
            'nohup '
            . escapeshellarg($php)
            . ' '
            . escapeshellarg($coord)
            . ' >> '
            . escapeshellarg($registro)
            . ' 2>&1 < /dev/null & echo $!';

        $salida = [];
        $codigo = 0;

        exec($comando, $salida, $codigo);

        if ($codigo !== 0 || $salida === []) {
            return false;
        }

        $pid = (int) trim((string) end($salida));

        if ($pid < 1) {
            return false;
        }

        /*
         * Esperar a que el intérprete arranque. La comprobación inmediata
         * anterior podía aceptar un proceso que moría al iniciar (por
         * ejemplo, por error de conexión o carga de dependencias).
         */
        usleep(250000);

        if (function_exists('posix_kill')) {
            if (!@posix_kill($pid, 0)) {
                return false;
            }
        } else {
            $verificar = [];
            $resultado = 1;
            exec('kill -0 ' . $pid . ' 2>/dev/null', $verificar, $resultado);

            if ($resultado !== 0) {
                return false;
            }
        }

        /* Confirmar que el PID corresponde al PHP coordinador esperado. */
        $cmdline = '/proc/' . $pid . '/cmdline';
        if (is_readable($cmdline)) {
            $proceso = (string) @file_get_contents($cmdline);
            if ($proceso === '' || !str_contains($proceso, $coord)) {
                return false;
            }
        }

        return true;
    }


    /* ========================================================
       NIVEL 1 — RESOLVER PHP CLI
    ======================================================== */

    private function php(): ?string
    {
        /* ====================================================
           NIVEL 2 — CLI DIRECTO
        ==================================================== */

        if (
            PHP_SAPI === 'cli'
            && is_file(PHP_BINARY)
        ) {
            return PHP_BINARY;
        }


        /* ====================================================
           NIVEL 2 — XAMPP DESDE DOCUMENT_ROOT
        ==================================================== */

        $doc = $_SERVER['DOCUMENT_ROOT'] ?? '';

        if ($doc !== '') {

            $xampp = dirname(
                rtrim(
                    $doc,
                    "\\/"
                )
            );

            $php = $xampp
                . DIRECTORY_SEPARATOR
                . 'php'
                . DIRECTORY_SEPARATOR
                . 'php.exe';

            if (is_file($php)) {
                return $php;
            }
        }


        /* ====================================================
           NIVEL 2 — RUTA ESTÁNDAR XAMPP
        ==================================================== */

        $php = 'C:\\xampp\\php\\php.exe';

        if (is_file($php)) {
            return $php;
        }


        /* ====================================================
           NIVEL 2 — PHPBINDIR
        ==================================================== */

        $php = PHP_BINDIR
            . DIRECTORY_SEPARATOR
            . (
                PHP_OS_FAMILY === 'Windows'
                    ? 'php.exe'
                    : 'php'
            );

        if (is_file($php)) {
            return $php;
        }


        return null;
    }
}