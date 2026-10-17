<?php
/* ============================================================
   Ruta: modul/categ/catal/equiv/equiv-repos.php
   Módulo: Catálogo / Equiv

   RESPONSABILIDAD:
   Administrar la relación Producto ↔ Vehículo.

   - Listar relaciones existentes.
   - Obtener productos para seleccionar.
   - Obtener vehículos para seleccionar.
   - Registrar una relación.
   - Eliminar una relación.

   NO RESPONSABILIDAD:
   - No recibe HTTP.
   - No genera HTML.
   - No genera JSON.
   - No crea conexiones.
============================================================ */

declare(strict_types=1);

final class EquivRepo
{
    public function __construct(private PDO $db)
    {
    }

    /* ========================================================
       LISTAR RELACIONES
    ======================================================== */
    public function listar(string $buscar = '', int $limite = 50): array
    {
        $limite = max(1, min($limite, 100));
        $buscar = trim($buscar);
        $condiciones = [];
        $parametros = [];

        $palabras = $buscar === ''
            ? []
            : preg_split('/\s+/u', $buscar, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($palabras as $i => $palabra) {
            $grupo = [];
            $campos = [
                'p.id', 'p.descr', 'p.codig', 'p.origi',
                'p.medid', 'p.super', 'p.equiv',
                'v.id', 'v.marca', 'v.motor', 'v.model', 'v.anio',
                'v.gdesd', 'v.ghast'
            ];

            foreach ($campos as $campo) {
                $nombr = str_replace('.', '_', $campo);
                $param = "b_{$i}_{$nombr}";
                $grupo[] = in_array($campo, [
                    'p.id', 'p.super', 'v.id', 'v.anio',
                    'v.gdesd', 'v.ghast'
                ], true)
                    ? "CAST({$campo} AS CHAR) LIKE :{$param}"
                    : "{$campo} LIKE :{$param}";
                $parametros[$param] = "%{$palabra}%";
            }

            $condiciones[] = '(' . implode(' OR ', $grupo) . ')';
        }

        $sql = "
            SELECT
                c.id,
                c.produ,
                c.vehic,
                p.descr,
                p.codig,
                p.origi,
                p.medid,
                p.super,
                p.equiv,
                v.marca,
                v.motor,
                v.model,
                v.anio,
                v.gdesd,
                v.ghast
            FROM equiv c
            INNER JOIN produ p ON p.id = c.produ
            INNER JOIN vehic v ON v.id = c.vehic
        ";

        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $sql .= " ORDER BY c.id DESC LIMIT {$limite}";

        

        $stmt = $this->db->prepare($sql);
        foreach ($parametros as $nombr => $valor) {
            $stmt->bindValue(':' . $nombr, $valor, PDO::PARAM_STR);
        }
        $stmt->execute();

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        

        return $resultado;
    }

    /* ========================================================
       PRODUCTOS PARA SELECCIÓN
    ======================================================== */
    public function productos(): array
    {
        $stmt = $this->db->query(
            'SELECT id, descr, codig, origi
             FROM produ
             ORDER BY descr ASC, id ASC'
        );

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        

        return $resultado;
    }

    /* ========================================================
       VEHÍCULOS PARA SELECCIÓN
    ======================================================== */
    public function vehiculos(): array
    {
        $stmt = $this->db->query(
            'SELECT id, marca, motor, model, anio,
                    gdesd, ghast
             FROM vehic
             ORDER BY marca ASC, model ASC, motor ASC, anio ASC, gdesd ASC, ghast ASC, id ASC'
        );

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        

        return $resultado;
    }

    /* ========================================================
       REGISTRAR RELACIÓN
    ======================================================== */
    public function agregar(int $prodId, int $vehiId): int
    {
        if ($prodId <= 0 || $vehiId <= 0) {
            throw new InvalidArgumentException('Producto o vehículo no válido.');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO equiv (produ, vehic)
             VALUES (:produ, :vehic)'
        );

        $stmt->execute([
            'produ' => $prodId,
            'vehic' => $vehiId,
        ]);

        $id = (int) $this->db->lastInsertId();

        

        return $id;
    }

    /* ========================================================
       ELIMINAR RELACIÓN
    ======================================================== */
    public function eliminar(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Equivalente no válido.');
        }

        $stmt = $this->db->prepare(
            'DELETE FROM equiv WHERE id = :id'
        );

        $stmt->execute(['id' => $id]);

        $eliminado = $stmt->rowCount() > 0;

        

        return $eliminado;
    }
}
