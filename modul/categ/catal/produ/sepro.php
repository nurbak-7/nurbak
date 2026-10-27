<?php
/* ============================================================
   Ruta: modul/categ/catal/produ/sepro.php
   Módulo: Catálogo / Productos
   Función: Reservar IDs de productos dentro de la transacción
            que inserta los registros.

   REGLAS:
   - Usa exclusivamente el contador de la tabla sepro.
   - Sincroniza el contador con el mayor ID existente en produ.
   - La reserva y la inserción deben compartir transacción.
   - Un ROLLBACK revierte también el contador.
============================================================ */
declare(strict_types=1);

final class SePro
{
    private const FILA = 1;
    private const MAX_ID = 4294967295;

    /**
     * Reserva una cantidad de IDs consecutivos para produ.
     * La conexión debe tener una transacción activa.
     *
     * @return list<int>
     */
    public static function reservar(PDO $db, int $cantidad = 1): array
    {
        if (!$db->inTransaction()) {
            throw new LogicException(
                'La reserva de IDs de productos requiere una transacción activa.'
            );
        }

        if ($cantidad < 1) {
            return [];
        }

        $st = $db->prepare(
            'SELECT valor FROM sepro WHERE id = :id FOR UPDATE'
        );
        $st->execute(['id' => self::FILA]);
        $valor = $st->fetchColumn();

        if ($valor === false) {
            throw new RuntimeException(
                'No existe el contador de productos en sepro. Verifica datos/categ/catal/produ.sql.'
            );
        }

        $st = $db->query(
            'SELECT COALESCE(MAX(id), 0) FROM produ'
        );
        $mayor = (int) $st->fetchColumn();
        $base = max((int) $valor, $mayor);

        if ($cantidad > self::MAX_ID - $base) {
            throw new OverflowException(
                'Se alcanzó el límite de identificadores de productos.'
            );
        }

        $ultimo = $base + $cantidad;
        $st = $db->prepare(
            'UPDATE sepro SET valor = :valor WHERE id = :id'
        );
        $st->execute([
            'valor' => $ultimo,
            'id' => self::FILA,
        ]);

        if ($st->rowCount() !== 1) {
            throw new RuntimeException(
                'No se pudo actualizar el contador de productos en sepro.'
            );
        }

        return range($base + 1, $ultimo);
    }
}
