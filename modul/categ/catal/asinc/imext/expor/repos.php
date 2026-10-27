<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/imext/expor/repos.php
   Módulo: Catálogo / ASINC / IMEXT / EXPORT

   Función:
   Consultar el catálogo integral por lotes.

   CONEXIONES:
   - CATALDB → produ, equiv, vehic.
   - INVENDB → stock.
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__, 6) . '/contr/categ/inven-db.php';

final class ExpoRepo
{
    private ?PDO $inve = null;

    public function __construct(private PDO $db)
    {
    }

    public function contar(
        string $tipo,
        string $buscar
    ): int {
        if ($tipo !== 'EXPO_INTEG') {
            throw new InvalidArgumentException(
                'Tipo de exportación no válido.'
            );
        }

        $where = '';
        $params = [];

        $this->filtro(
            $buscar,
            $where,
            $params
        );

        $sql = "SELECT COUNT(*)
                FROM produ p
                LEFT JOIN equiv c ON c.produ=p.id
                LEFT JOIN vehic v ON v.id=c.vehic
                WHERE ((v.id IS NOT NULL AND UPPER(TRIM(p.descr))<>'VARIOS') OR (
                    UPPER(TRIM(p.descr))='VARIOS'
                    AND NOT EXISTS (SELECT 1 FROM equiv c2 WHERE c2.produ=p.id)
                ))";

        if ($where !== '') {
            $sql .= ' AND (' . $where . ')';
        }

        $st = $this->db->prepare($sql);
        $st->execute($params);

        return (int) $st->fetchColumn();
    }

    public function filas(
        string $tipo,
        string $buscar,
        int $inicio,
        int $cantidad
    ): array {
        if ($tipo !== 'EXPO_INTEG') {
            throw new InvalidArgumentException(
                'Tipo de exportación no válido.'
            );
        }

        $off = max(0, $inicio - 1);
        $where = '';
        $params = [];

        $this->filtro(
            $buscar,
            $where,
            $params
        );

        $sql = "SELECT
                    p.id,
                    v.marca,
                    v.motor,
                    v.model,
                    p.descr,
                    p.codig,
                    p.origi,
                    p.medid,
                    p.super,
                    p.equiv,
                    CONCAT(
                        LPAD(MOD(v.anio,100),2,'0'),
                        '-',
                        v.gdesd,
                        '-',
                        LPAD(MOD(v.ghast,100),2,'0')
                    ) AS anio,
                    p.pcomp,
                    p.pvent,
                    p.fecha
                FROM produ p
                LEFT JOIN equiv c ON c.produ=p.id
                LEFT JOIN vehic v ON v.id=c.vehic
                WHERE ((v.id IS NOT NULL AND UPPER(TRIM(p.descr))<>'VARIOS') OR (
                    UPPER(TRIM(p.descr))='VARIOS'
                    AND NOT EXISTS (SELECT 1 FROM equiv c2 WHERE c2.produ=p.id)
                ))";

        if ($where !== '') {
            $sql .= ' AND (' . $where . ')';
        }

        $sql .= ' ORDER BY (v.id IS NULL),v.marca,v.id,p.id
                  LIMIT :lim OFFSET :off';

        $st = $this->db->prepare($sql);

        foreach ($params as $nombre => $valor) {
            $st->bindValue(
                ':' . $nombre,
                $valor,
                PDO::PARAM_STR
            );
        }

        $st->bindValue(
            ':lim',
            $cantidad,
            PDO::PARAM_INT
        );

        $st->bindValue(
            ':off',
            $off,
            PDO::PARAM_INT
        );

        $st->execute();

        $filas = $st->fetchAll(PDO::FETCH_ASSOC);

        if ($filas === []) {
            return [];
        }

        $ids = array_values(
            array_unique(
                array_map(
                    static fn(array $fila): int => (int) $fila['id'],
                    $filas
                )
            )
        );

        $qty = $this->stock($ids);

        foreach ($filas as &$fila) {
            $id = (int) $fila['id'];
            $esVar = strtoupper(trim((string) ($fila['descr'] ?? ''))) === 'VARIOS'
                && trim((string) ($fila['marca'] ?? '')) === '';
            $fila['qty'] = $esVar ? 0 : ($qty[$id] ?? 0);
            $fila['fecha'] = (string) (
                $fila['fecha'] ?? ''
            );
        }

        unset($fila);

        return $filas;
    }

    private function filtro(
        string $buscar,
        string &$where,
        array &$params
    ): void {
        $buscar = trim($buscar);

        if ($buscar === '') {
            return;
        }

        $words = preg_split(
            '/\s+/u',
            $buscar,
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        $condiciones = [];

        foreach ($words as $i => $word) {
            $key = 'b_' . $i;
            $patron = '%' . $word . '%';

            $condiciones[] = "(
                CAST(p.id AS CHAR) LIKE :{$key}_id
                OR v.marca LIKE :{$key}_marca
                OR v.motor LIKE :{$key}_motor
                OR v.model LIKE :{$key}_model
                OR p.descr LIKE :{$key}_descr
                OR p.codig LIKE :{$key}_codig
                OR p.origi LIKE :{$key}_origi
                OR p.medid LIKE :{$key}_medid
                OR p.super LIKE :{$key}_super
                OR p.equiv LIKE :{$key}_equiv
            )";

            foreach (
                [
                    'id',
                    'marca',
                    'motor',
                    'model',
                    'descr',
                    'codig',
                    'origi',
                    'medid',
                    'super',
                    'equiv',
                ] as $campo
            ) {
                $params[$key . '_' . $campo] = $campo === 'id'
                    ? $patron
                    : $patron;
            }
        }

        $where = implode(
            ' AND ',
            $condiciones
        );
    }

    private function stock(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        if (!$this->inve instanceof PDO) {
            $this->inve = INVENDB::abrir();
        }

        $ph = [];
        $params = [];

        foreach ($ids as $i => $id) {
            $key = 'p' . $i;
            $ph[] = ':' . $key;
            $params[$key] = $id;
        }

        $st = $this->inve->prepare(
            'SELECT produ,SUM(canti) qty
             FROM stock
             WHERE produ IN (' . implode(',', $ph) . ')
             GROUP BY produ'
        );

        $st->execute($params);

        $salida = [];

        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $salida[(int) $fila['produ']] =
                (float) $fila['qty'];
        }

        return $salida;
    }
}
