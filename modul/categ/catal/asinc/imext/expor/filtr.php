<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/imext/expor/filtr.php
   Módulo: Catálogo / ASINC / IMEXT / EXPORT
   Función: Normalizar el filtro del Excel integral.

   EXPORTACIÓN:
   - Siempre genera el catálogo integral.
   - Una hoja por MARCA.
   - MARCA pertenece exclusivamente al intercambio Excel.
============================================================ */
declare(strict_types=1);

final class ExpoFiltr
{
    public function leer(array $src): array
    {
        return [
            'tipo' => 'EXPO_INTEG',
            'buscar' => trim((string) ($src['buscar'] ?? '')),
        ];
    }
}
