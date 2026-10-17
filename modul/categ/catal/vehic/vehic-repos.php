<?php
/* ============================================================
   Ruta: modul/categ/catal/vehic/vehic-repos.php
   Módulo: Catálogo / Vehículos
   Función: Acceso a vehic.

   🔹 NIVEL 1: Buscar/listar.
   🔹 NIVEL 2: Obtener.
   🔹 NIVEL 3: Crear/editar y evitar duplicados.
   🔹 NIVEL 4: Eliminar.
============================================================ */

declare(strict_types=1);

final class VehiRepo
{
    public function __construct(private PDO $db) {}

    private function normalizarMarca(string $valor): string
    {
        $marca = strtoupper(trim($valor));

        if (preg_match('/^.{2}$/uD', $marca) !== 1) {
            throw new InvalidArgumentException('La marca es obligatoria y debe tener exactamente dos caracteres.');
        }

        return $marca;
    }

    private function normalizarAnio(string $valor): array
    {
        $v = trim($valor);

        if (!preg_match('/^(\d{2}|\d{4})-(\d{4})-(\d{2}|\d{4})$/', $v, $m)) {
            throw new InvalidArgumentException('El año debe tener formato 08-2000-12.');
        }

        $model = (int) $m[1];

        if ($model < 100) {
            $model += 2000;
        }

        $desde = (int) $m[2];
        $hasta = (int) $m[3];

        if ($hasta < 100) {
            $hasta += 2000;
        }

        if ($desde > $hasta) {
            throw new InvalidArgumentException('La generación inicial no puede ser mayor que la final.');
        }

        return [$model, $desde, $hasta];
    }

    private function anioTexto(int $model, int $desde, int $hasta): string
    {
        return str_pad((string) ($model % 100), 2, '0', STR_PAD_LEFT)
            . '-' . $desde
            . '-' . str_pad((string) ($hasta % 100), 2, '0', STR_PAD_LEFT);
    }

    public function listar(string $buscar = '', int $limite = 50): array
    {
        $limite = max(1, min($limite, 100));
        $buscar = trim($buscar);
        $where = [];
        $params = [];
        $words = $buscar === ''
            ? []
            : (preg_split('/\s+/u', $buscar, -1, PREG_SPLIT_NO_EMPTY) ?: []);

        foreach ($words as $i => $w) {
            $g = [];

            foreach (['id', 'marca', 'motor', 'model', 'anio', 'gdesd', 'ghast'] as $c) {
                $p = "b_{$i}_{$c}";

                $g[] = in_array($c, ['id', 'anio', 'gdesd', 'ghast'], true)
                    ? "CAST({$c} AS CHAR) LIKE :{$p}"
                    : "{$c} LIKE :{$p}";

                $params[$p] = "%{$w}%";
            }

            $where[] = '(' . implode(' OR ', $g) . ')';
        }

        $sql = 'SELECT id, marca, motor, model, anio, gdesd, ghast FROM vehic';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY id DESC LIMIT {$limite}";

        $st = $this->db->prepare($sql);

        foreach ($params as $n => $v) {
            $st->bindValue(':' . $n, $v, PDO::PARAM_STR);
        }

        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r['marca'] = strtoupper(trim((string) $r['marca']));
            $r['anio'] = $this->anioTexto(
                (int) $r['anio'],
                (int) $r['gdesd'],
                (int) $r['ghast']
            );
        }

        unset($r);

        return $rows;
    }

    public function obtener(int $id): array
    {
        $st = $this->db->prepare(
            'SELECT id, marca, motor, model, anio, gdesd, ghast
             FROM vehic
             WHERE id = :id'
        );

        $st->execute(['id' => $id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        if (!$r) {
            throw new RuntimeException('El vehículo no existe.');
        }

        $r['marca'] = strtoupper(trim((string) $r['marca']));
        $r['anio'] = $this->anioTexto(
            (int) $r['anio'],
            (int) $r['gdesd'],
            (int) $r['ghast']
        );

        return $r;
    }

    /* ============================================================
       NIVEL 3 — VALIDAR IDENTIDAD
       La identidad completa incluye marca y datos del vehículo.
    ============================================================ */

    private function duplicado(
        string $marca,
        string $motor,
        string $model,
        int $anio,
        int $desde,
        int $hasta,
        int $excluir = 0
    ): bool {
        $sql = 'SELECT id
                FROM vehic
                WHERE marca = :marca
                  AND motor = :motor
                  AND model = :model
                  AND anio = :anio
                  AND gdesd = :desde
                  AND ghast = :hasta';

        $params = [
            'marca' => $marca,
            'motor' => $motor,
            'model' => $model,
            'anio' => $anio,
            'desde' => $desde,
            'hasta' => $hasta,
        ];

        if ($excluir > 0) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excluir;
        }

        $sql .= ' LIMIT 1';

        $st = $this->db->prepare($sql);
        $st->execute($params);

        return $st->fetchColumn() !== false;
    }

    public function guardar(array $d): int
    {
        $id = (int) ($d['id'] ?? 0);
        $marca = $this->normalizarMarca((string) ($d['marca'] ?? ''));
        $motor = trim((string) ($d['motor'] ?? ''));
        $model = trim((string) ($d['model'] ?? ''));
        $anio = trim((string) ($d['anio'] ?? ''));

        if ($motor === '' || $model === '' || $anio === '') {
            throw new InvalidArgumentException('Marca, motor, model y año son obligatorios.');
        }

        [$am, $desde, $hasta] = $this->normalizarAnio($anio);

        if ($id > 0) {
            $st = $this->db->prepare('SELECT id FROM vehic WHERE id = :id');
            $st->execute(['id' => $id]);

            if ($st->fetchColumn() === false) {
                throw new RuntimeException('El vehículo no existe.');
            }
        }

        if ($this->duplicado($marca, $motor, $model, $am, $desde, $hasta, $id)) {
            throw new InvalidArgumentException('Ya existe un vehículo con la misma marca, motor, modelo y generación.');
        }

        try {
            if ($id > 0) {
                $st = $this->db->prepare(
                    'UPDATE vehic
                     SET marca = :marca,
                         motor = :motor,
                         model = :model,
                         anio = :am,
                         gdesd = :desde,
                         ghast = :hasta
                     WHERE id = :id'
                );

                $st->execute([
                    'marca' => $marca,
                    'motor' => $motor,
                    'model' => $model,
                    'am' => $am,
                    'desde' => $desde,
                    'hasta' => $hasta,
                    'id' => $id,
                ]);

                return $id;
            }

            $st = $this->db->prepare(
                'INSERT INTO vehic (marca, motor, model, anio, gdesd, ghast)
                 VALUES (:marca, :motor, :model, :am, :desde, :hasta)'
            );

            $st->execute([
                'marca' => $marca,
                'motor' => $motor,
                'model' => $model,
                'am' => $am,
                'desde' => $desde,
                'hasta' => $hasta,
            ]);

            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                throw new InvalidArgumentException(
                    'Ya existe un vehículo con la misma marca, motor, modelo y generación.',
                    0,
                    $e
                );
            }

            throw $e;
        }
    }

    public function eliminar(int $id): bool
    {
        $st = $this->db->prepare(
            'DELETE FROM vehic WHERE id = :id'
        );

        $st->execute(['id' => $id]);

        if (!$st->rowCount()) {
            throw new RuntimeException('El vehículo no existe.');
        }

        return true;
    }
}
