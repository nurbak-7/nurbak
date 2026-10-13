<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/imext/impor/servi.php
   Módulo: Catálogo / ASINC / IMEXT / IMPORT
   Función: Coordinar el procesamiento de importaciones.

   CONTEXTO:
   - La importación trabaja sobre una TIENDA concreta.
   - QTY se asigna únicamente al ALMACÉN PRINCIPAL ACTIVO
     de esa tienda.
   - El contexto se valida al crear el proceso y nuevamente
     al procesar cada lote.
============================================================ */
declare(strict_types=1);

require_once __DIR__ . '/lotes.php';

final class ImpoServi
{
    public function __construct(
        private PDO $db,
        private PDO $inve,
        private PDO $log
    ) {
    }

    public function tiendas(): array
    {
        $st = $this->log->query(
            'SELECT id, codig, nombr
             FROM tiend
             WHERE activ = 1
             ORDER BY nombr, id'
        );

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contexto(int $tiend): array
    {
        if ($tiend <= 0) {
            throw new InvalidArgumentException(
                'Tienda no válida.'
            );
        }

        $st = $this->log->prepare(
            'SELECT id, codig, nombr, activ
             FROM tiend
             WHERE id = :id
             LIMIT 1'
        );

        $st->execute([
            ':id' => $tiend,
        ]);

        $tien = $st->fetch(PDO::FETCH_ASSOC);

        if (!$tien) {
            throw new InvalidArgumentException(
                'La tienda no existe.'
            );
        }

        if ((int) $tien['activ'] !== 1) {
            throw new InvalidArgumentException(
                'La tienda está inactiva.'
            );
        }

        $st = $this->inve->prepare(
            'SELECT id, tiend, nombr, estad, prin
             FROM almac
             WHERE tiend = :tiend
               AND estad = 1
               AND prin = 1
             LIMIT 1'
        );

        $st->execute([
            ':tiend' => $tiend,
        ]);

        $alma = $st->fetch(PDO::FETCH_ASSOC);

        if (!$alma) {
            throw new InvalidArgumentException(
                'La tienda no tiene un almacén principal activo.'
            );
        }

        return [
            'tienda' => $tien,
            'almacen' => $alma,
        ];
    }

    public function lote(array $trab, array $lote): array
    {
        $tiend = (int) ($trab['tiend'] ?? 0);

        $ctx = $this->contexto($tiend);

        return (new ImpoLotes(
            $this->db,
            $this->inve,
            (int) $ctx['almacen']['id']
        ))->ejecutar($trab, $lote);
    }
}
