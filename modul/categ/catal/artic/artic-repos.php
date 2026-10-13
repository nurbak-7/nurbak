<?php
/* ============================================================
   Ruta: modul/categ/catal/artic/artic-repos.php
   Módulo: Catálogo / Artículos
   Función: Consulta consolidada de CATAL e INVENTARIO.

   RESPONSABILIDAD:
   - Consultar productos y su aplicación en vehículos desde CATAL.
   - Consultar stock físico desde INVENTARIO.
   - Entregar una sola estructura para la vista de Artículos.

   RELACIÓN:
   produ → equiv → vehic

   QTY:
   Suma del stock físico de almacenes activos.

   IMPORTANTE:
   CATAL e INVENTARIO mantienen conexiones independientes.
   No se realiza consulta cruzada entre bases.
============================================================ */

declare(strict_types=1);


/* ============================================================
   NIVEL 1
   REPOSITORIO
============================================================ */

final class ArtiR
{
    /* ========================================================
       NIVEL 2
       CONEXIONES
    ======================================================== */

    public function __construct(
        private PDO $cate,
        private PDO $inve
    ) {
    }


    /* ========================================================
       NIVEL 2
       LISTAR
    ======================================================== */

    public function lista(
        string $busc = '',
        int $limi = 50
    ): array {

        /* ====================================================
           NIVEL 3
           PREPARAR DATOS
        ==================================================== */

        $limi = max(1, min($limi, 100));
        $busc = trim($busc);

        $where = [];
        $pars = [];

        $word = $busc === ''
            ? []
            : preg_split(
                '/\s+/u',
                $busc,
                -1,
                PREG_SPLIT_NO_EMPTY
            );


        /* ====================================================
           NIVEL 3
           CAMPOS DE BÚSQUEDA
        ==================================================== */

        $camp = [
            'p.id',
            'p.descr',
            'p.codig',
            'p.origi',
            'p.medid',
            'p.equiv',
            'p.super',
            'v.motor',
            'v.model',
            'v.anio',
            'v.gdesd',
            'v.ghast'
        ];


        /* ====================================================
           NIVEL 3
           CONSTRUIR BÚSQUEDA
        ==================================================== */

        foreach ($word as $i => $term) {

            $grup = [];

            foreach ($camp as $col) {

                $parm = 'b_' . $i . '_' .
                    str_replace('.', '_', $col);

                $num = in_array(
                    $col,
                    [
                        'p.id',
                        'p.super',
                        'v.anio',
                        'v.gdesd',
                        'v.ghast'
                    ],
                    true
                );

                if ($num) {
                    $grup[] =
                        "CAST({$col} AS CHAR) LIKE :{$parm}";
                } else {
                    $grup[] =
                        "{$col} LIKE :{$parm}";
                }

                $pars[$parm] = '%' . $term . '%';
            }

            $where[] = '(' . implode(' OR ', $grup) . ')';
        }


        /* ====================================================
           NIVEL 3
           CONSULTAR CATAL

           MOTOR y MODEL salen de VEHIC mediante EQUIV.
           No se duplican en PRODU.
        ==================================================== */

        $sql = "
            SELECT
                p.id,
                p.descr,
                p.codig,
                p.origi,
                p.medid,
                p.super,
                p.equiv,
                p.pcomp,
                p.pvent,
                p.fecha,

                GROUP_CONCAT(
                    DISTINCT v.motor
                    ORDER BY v.motor
                    SEPARATOR ' | '
                ) motor,

                GROUP_CONCAT(
                    DISTINCT v.model
                    ORDER BY v.model
                    SEPARATOR ' | '
                ) model,

                GROUP_CONCAT(
                    DISTINCT CONCAT(
                        v.motor,
                        ' / ',
                        v.model
                    )
                    ORDER BY v.model, v.motor
                    SEPARATOR ' | '
                ) vehic,

                GROUP_CONCAT(
                    DISTINCT CONCAT(
                        LPAD(MOD(v.anio, 100), 2, '0'),
                        '-',
                        v.gdesd,
                        '-',
                        LPAD(MOD(v.ghast, 100), 2, '0')
                    )
                    ORDER BY v.anio, v.gdesd, v.ghast
                    SEPARATOR ' | '
                ) anio

            FROM produ p

            LEFT JOIN equiv e
                ON e.produ = p.id

            LEFT JOIN vehic v
                ON v.id = e.vehic
        ";


        /* ====================================================
           NIVEL 3
           APLICAR BÚSQUEDA
        ==================================================== */

        if ($where !== []) {
            $sql .=
                ' WHERE ' .
                implode(' AND ', $where);
        }


        /* ====================================================
           NIVEL 3
           AGRUPAR Y ORDENAR
        ==================================================== */

        $sql .= "
            GROUP BY p.id
            ORDER BY p.id DESC
            LIMIT {$limi}
        ";


        /* ====================================================
           NIVEL 3
           EJECUTAR CATAL
        ==================================================== */

        $stmt = $this->cate->prepare($sql);

        foreach ($pars as $name => $val) {
            $stmt->bindValue(
                ':' . $name,
                $val,
                PDO::PARAM_STR
            );
        }

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rows === []) {
            return [];
        }


        /* ====================================================
           NIVEL 3
           CONSULTAR STOCK EN INVENTARIO

           Se mantiene separado de CATAL.
           Solo se consideran almacenes activos.
        ==================================================== */

        $ids = array_map(
            static fn(array $row): int => (int) $row['id'],
            $rows
        );

        $mark = [];
        $pars = [];

        foreach ($ids as $i => $id) {
            $key = 'p' . $i;
            $mark[] = ':' . $key;
            $pars[$key] = $id;
        }

        $list = implode(',', $mark);

        $qsto = "
            SELECT
                s.produ,
                COALESCE(SUM(s.canti), 0) qty
            FROM stock s
            INNER JOIN almac a
                ON a.id = s.almac
            WHERE a.estad = 1
              AND s.produ IN ({$list})
            GROUP BY s.produ
        ";

        $stmt = $this->inve->prepare($qsto);
        $stmt->execute($pars);

        $stoc = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $stoc[(int) $fila['produ']] =
                (int) $fila['qty'];
        }


        /* ====================================================
           NIVEL 3
           NORMALIZAR RESPUESTA
        ==================================================== */

        foreach ($rows as &$row) {

            $id = (int) $row['id'];

            $row['motor'] =
                $row['motor'] ?: '—';

            $row['model'] =
                $row['model'] ?: '—';

            $row['vehic'] =
                $row['vehic'] ?: '—';

            $row['anio'] =
                $row['anio'] ?: '—';

            $row['qty'] =
                $stoc[$id] ?? 0;
        }

        unset($row);


        /* ====================================================
           NIVEL 3
           RETORNAR
        ==================================================== */

        return $rows;
    }
}
