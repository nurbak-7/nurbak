<?php
/* ============================================================
   Ruta: contr/error.php
   Función: Manejo normal de errores del sistema.

   RESPONSABILIDAD:
   - Detectar excepciones no controladas.
   - Detectar errores PHP fatales antes de entregar la respuesta.
   - Entregar a la VIEW un resumen real de la falla.

   NO HACE:

   VIEW:
   Muestra un resumen con código, archivo, línea y mensaje.

   Es independiente y conserva el detalle técnico por su propia
   vía de escucha.
============================================================ */

declare(strict_types=1);

/* ============================================================
   NIVEL 1 — DATOS DE ERROR
============================================================ */

function errMsg(Throwable $e): string
{
    $msg = trim($e->getMessage());
    $msg = $msg !== '' ? $msg : get_class($e);

    $tip = $e instanceof PDOException
        ? 'SQL_ERROR'
        : ($e instanceof InvalidArgumentException ? 'VALID_ERROR' : 'PHP_ERROR');

    return $tip
        . ' | '
        . errFil($e->getFile())
        . ':'
        . $e->getLine()
        . ' | '
        . $msg;
}

function errFil(string $fil): string
{
    $bas = dirname(__DIR__);
    $fil = str_replace('\\', '/', $fil);
    $bas = str_replace('\\', '/', $bas);

    if (str_starts_with($fil, $bas . '/')) {
        return substr($fil, strlen($bas) + 1);
    }

    return $fil;
}

/* ============================================================
   NIVEL 2 — RESPUESTA JSON NORMAL
============================================================ */

function errRes(Throwable $e, int $sta = 500): never
{
    http_response_code($sta);

    header('Content-Type: application/json; charset=utf-8');

    $detalle = $sta >= 500
        ? 'Ocurrió un error interno. Revise el DEBUG del sistema.'
        : errMsg($e);

    echo json_encode(
        [
            'ok' => false,
            'mensaje' => $detalle,
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

/* ============================================================
   NIVEL 3 — ERRORES PHP RECUPERABLES
============================================================ */

set_error_handler(
    static function (
        int $niv,
        string $msg,
        string $fil,
        int $lin
    ): bool {
        if (!(error_reporting() & $niv)) {
            return false;
        }

        if (!in_array(
            $niv,
            [
                E_WARNING,
                E_USER_WARNING,
                E_RECOVERABLE_ERROR,
                E_USER_ERROR,
            ],
            true
        )) {
            return false;
        }

        throw new ErrorException(
            $msg,
            0,
            $niv,
            $fil,
            $lin
        );
    }
);

/* ============================================================
   NIVEL 3 — EXCEPCIÓN NO CONTROLADA
============================================================ */

set_exception_handler(
    static function (Throwable $e): void {
        errRes($e, 500);
    }
);

/* ============================================================
   NIVEL 4 — ERROR FATAL
============================================================ */

register_shutdown_function(
    static function (): void {
        $err = error_get_last();

        if (!$err) {
            return;
        }

        $tip = (int) ($err['type'] ?? 0);

        if (!in_array(
            $tip,
            [
                E_ERROR,
                E_PARSE,
                E_CORE_ERROR,
                E_COMPILE_ERROR,
                E_USER_ERROR,
            ],
            true
        )) {
            return;
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');

        $fil = (string) ($err['file'] ?? '');
        $lin = (int) ($err['line'] ?? 0);
        $msg = trim((string) ($err['message'] ?? 'Error PHP fatal.'));

        echo json_encode(
            [
                'ok' => false,
                'mensaje' => 'PHP_FATAL | '
                    . errFil($fil)
                    . ':'
                    . $lin
                    . ' | '
                    . $msg,
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
);
