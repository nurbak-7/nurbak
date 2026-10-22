<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/imext/impor/lotes.php
   Módulo: Catálogo / ASINC / IMEXT / IMPORT
   Función: Procesar un lote de importación.
============================================================ */
declare(strict_types=1);

require_once __DIR__ . '/archi.php';
require_once __DIR__ . '/valid.php';
require_once __DIR__ . '/repos.php';
require_once dirname(__DIR__, 2) . '/job/repos.php';

final class ImpoLotes
{
    public function __construct(
        private PDO $db,
        private PDO $inve,
        private int $almac
    ) {
    }

    public function ejecutar(array $trab, array $lote): array
    {
        $archi = new ImpoArchi();
        $valid = new ImpoValid();
        $repo = new ImpoRepo(
            $this->db,
            $this->inve
        );
        $jrepo = new AsinRepo($this->db);

        $filas = $archi->filas(
            (string) $trab['archivo'],
            (int) $lote['inicio'],
            (int) $lote['cantidad']
        );

        $validas = [];
        $erro = 0;

        /* Primero valida todas las filas: solo datos válidos entran al lote SQL. */
        foreach ($filas as $indice => $item) {
            try {
                $fila = $valid->fila(
                    $item['datos'],
                    (string) ($item['hoja'] ?? '')
                );
                $validas[$indice] = [
                    'item' => $item,
                    'datos' => $fila,
                ];
            } catch (Throwable $e) {
                $erro++;
                $jrepo->error(
                    (int) $trab['id'],
                    (int) $lote['id'],
                    (int) $item['fila'],
                    'IMPO_FILA',
                    '[' . (string) ($item['hoja'] ?? '') . '!'
                    . (string) ($item['fila'] ?? '') . '] '
                    . $e->getMessage()
                );
            }
        }

        /*
         * Se conservan el orden del Excel y su comportamiento secuencial.
         * Cada grupo mantiene una sola modalidad de ID: explícito o automático.
         * Así el repositorio puede agrupar las consultas sin volver al guardado
         * individual de todas las filas cuando el libro mezcla ambos casos.
         */
        $grupos = [];
        $grupo = [];
        $grupoConId = null;

        foreach ($validas as $indice => $registro) {
            $conId = (int) ($registro['datos']['id'] ?? 0) > 0;

            if ($grupo !== [] && $conId !== $grupoConId) {
                $grupos[] = $grupo;
                $grupo = [];
            }

            $grupoConId = $conId;
            $grupo[$indice] = $registro;
        }

        if ($grupo !== []) {
            $grupos[] = $grupo;
        }

        $erroresRepo = [];
        foreach ($grupos as $grupo) {
            try {
                $resultado = $repo->guardarLote(
                    (string) $trab['modo'],
                    $grupo,
                    $this->almac,
                    (int) ($trab['tiend'] ?? 0)
                );

                foreach ($resultado['errores'] as $indice => $mensaje) {
                    $erroresRepo[$indice] = $mensaje;
                }
            } catch (Throwable $e) {
                foreach ($grupo as $indice => $registro) {
                    $erroresRepo[$indice] = $e->getMessage();
                }
            }
        }

        foreach ($erroresRepo as $indice => $mensaje) {
            $item = $validas[$indice]['item'] ?? null;
            if (!is_array($item)) {
                continue;
            }

            $erro++;
            $jrepo->error(
                (int) $trab['id'],
                (int) $lote['id'],
                (int) $item['fila'],
                'IMPO_FILA',
                '[' . (string) ($item['hoja'] ?? '') . '!'
                . (string) ($item['fila'] ?? '') . '] '
                . (string) $mensaje
            );
        }

        $corre = count($validas) - count($erroresRepo);

        return [
            'proce' => count($filas),
            'corre' => $corre,
            'erro' => $erro,
        ];
    }
}
