<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/imext/expor/lotes.php
   Módulo: Catálogo / ASINC / IMEXT / EXPORT
   Función: Procesar un lote de exportación.
============================================================ */
declare(strict_types=1);

require_once __DIR__ . '/repos.php';
require_once __DIR__ . '/archi.php';

final class ExpoLotes
{
    public function __construct(private PDO $db)
    {
    }

    public function ejecutar(array $trab, array $lote): array
    {
        $repo = new ExpoRepo($this->db);
        $archi = new ExpoArchi();

        $filas = $repo->filas(
            (string) $trab['tipo'],
            $this->buscar($trab),
            (int) $lote['inicio'],
            (int) $lote['cantidad']
        );

        $archi->agregar((string) $trab['archivo'], $filas);

        return [
            'proce' => count($filas),
            'corre' => count($filas),
            'erro' => 0,
        ];
    }

    private function buscar(array $trab): string
    {
        $mens = (string) ($trab['mens'] ?? '');

        if (str_starts_with($mens, 'BUSCAR=')) {
            return substr($mens, 7);
        }

        return '';
    }
}
