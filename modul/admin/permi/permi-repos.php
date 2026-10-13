<?php
/* ============================================================
   Ruta: modul/admin/permi/permi-repos.php
   Función: Ejecutar la persistencia de PERM y sus asignaciones.

   🔹 Nivel 1: Listar permisos.
   🔹 Nivel 2: Buscar permisos.
   🔹 Nivel 3: Consultar permiso.
   🔹 Nivel 4: Crear permiso.
   🔹 Nivel 5: Actualizar permiso.
   🔹 Nivel 6: Cambiar estado.
   🔹 Nivel 7: Consultar permisos de usuario.
   🔹 Nivel 8: Consultar usuarios con permisos.
   🔹 Nivel 9: Guardar asignaciones.
   🔹 Nivel 10: Eliminar asignaciones.
   🔹 Nivel 11: Comprobar permiso.
============================================================ */

declare(strict_types=1);

final class PermRepo
{
    public function __construct(private PDO $db) {}

    public function listar(): array
    {
        $sql = "SELECT id, codig, nombr, descr, activ,
                       codig AS perm_codi, nombr AS perm_nomb, descr AS perm_desc
                FROM permi ORDER BY nombr ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscar(string $texto): array
    {
        $sql = "SELECT id, codig, nombr, descr, activ,
                       codig AS perm_codi, nombr AS perm_nomb, descr AS perm_desc
                FROM permi
                WHERE codig LIKE :texto OR nombr LIKE :texto OR descr LIKE :texto
                ORDER BY nombr ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':texto' => '%' . $texto . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $sql = "SELECT id, codig, nombr, descr, activ,
                       codig AS perm_codi, nombr AS perm_nomb, descr AS perm_desc
                FROM permi WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $dato = $stmt->fetch(PDO::FETCH_ASSOC);
        return $dato !== false ? $dato : null;
    }

    public function crear(string $permCodi, string $permNomb, ?string $permDesc): int
    {
        $stmt = $this->db->prepare("INSERT INTO permi (codig, nombr, descr, activ)
                                    VALUES (:codig, :nombr, :descr, 1)");
        $stmt->execute([
            ':codig' => $permCodi,
            ':nombr' => $permNomb,
            ':descr' => $permDesc,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, string $permNomb, ?string $permDesc): bool
    {
        $stmt = $this->db->prepare("UPDATE permi SET nombr = :nombr, descr = :descr WHERE id = :id");
        $stmt->execute([
            ':id' => $id,
            ':nombr' => $permNomb,
            ':descr' => $permDesc,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function cambiarEstado(int $id, int $activ): bool
    {
        $stmt = $this->db->prepare("UPDATE permi SET activ = :activ WHERE id = :id");
        $stmt->execute([':id' => $id, ':activ' => $activ]);
        return $stmt->rowCount() > 0;
    }

    public function permisosUsuario(int $usuaId): array
    {
        $sql = "SELECT
                    p.id, p.codig, p.nombr, p.descr, p.activ,
                    p.codig AS perm_codi, p.nombr AS perm_nomb, p.descr AS perm_desc
                FROM usape up
                INNER JOIN permi p ON p.id = up.permi
                WHERE up.usuar = :usuar AND p.activ = 1
                ORDER BY p.nombr ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':usuar' => $usuaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function usuariosConPermisos(string $texto = ''): array
    {
        $where = '';
        $parametros = [];

        if ($texto !== '') {
            $where = "WHERE u.admic LIKE :texto_admi
                         OR u.operc LIKE :texto_oper
                         OR u.logi LIKE :texto_logi
                         OR u.nombr LIKE :texto_nomb
                         OR r.nombr LIKE :texto_rol";
            $valor = '%' . $texto . '%';
            $parametros = [
                ':texto_admi' => $valor,
                ':texto_oper' => $valor,
                ':texto_logi' => $valor,
                ':texto_nomb' => $valor,
                ':texto_rol' => $valor,
            ];
        }

        $sql = "SELECT
                    u.id,
                    u.admic,
                    u.operc,
                    u.logi,
                    u.nombr AS usua_nomb,
                    u.activ AS usua_activ,
                    r.nombr AS rol_nomb,
                    r.totac AS acceso_total,
                    p.id AS permi,
                    p.codig AS perm_codi,
                    p.nombr AS perm_nomb,
                    p.descr AS perm_desc,
                    p.activ AS perm_activo
                FROM usuar u
                INNER JOIN rol r ON r.id = u.rol
                LEFT JOIN usape up ON up.usuar = u.id
                LEFT JOIN permi p ON p.id = up.permi
                $where
                ORDER BY u.id ASC, p.nombr ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        $usuarios = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (int) $fila['id'];
            if (!isset($usuarios[$id])) {
                $usuarios[$id] = [
                    'id' => $id,
                    'admic' => $fila['admic'],
                    'operc' => $fila['operc'],
                    'logi' => $fila['logi'],
                    'nombr' => $fila['usua_nomb'],
                    'activ' => (int) $fila['usua_activ'],
                    'rol_nomb' => $fila['rol_nomb'],
                    'acceso_total' => (int) $fila['acceso_total'],
                    'admi_codi' => $fila['admic'],
                    'oper_codi' => $fila['operc'],
                    'usua_logi' => $fila['logi'],
                    'usua_nomb' => $fila['usua_nomb'],
                    'permisos' => [],
                ];
            }

            if ($fila['permi'] !== null) {
                $usuarios[$id]['permisos'][] = [
                    'id' => (int) $fila['permi'],
                    'codig' => $fila['perm_codi'],
                    'nombr' => $fila['perm_nomb'],
                    'descr' => $fila['perm_desc'],
                    'activ' => (int) $fila['perm_activo'],
                    'perm_codi' => $fila['perm_codi'],
                    'perm_nomb' => $fila['perm_nomb'],
                    'perm_desc' => $fila['perm_desc'],
                ];
            }
        }

        return array_values($usuarios);
    }

    public function guardarAsignacion(int $usuaId, array $permIds): void
    {
        $this->db->beginTransaction();
        try {
            $borrar = $this->db->prepare('DELETE FROM usape WHERE usuar = :usuar');
            $borrar->execute([':usuar' => $usuaId]);

            if ($permIds) {
                $insertar = $this->db->prepare(
                    'INSERT INTO usape (usuar, permi) VALUES (:usuar, :permi)'
                );
                foreach ($permIds as $permId) {
                    $insertar->execute([':usuar' => $usuaId, ':permi' => $permId]);
                }
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function eliminarAsignacion(int $usuaId): array
    {
        $contar = $this->db->prepare('SELECT COUNT(*) FROM usape WHERE usuar = :usuar');
        $contar->execute([':usuar' => $usuaId]);
        $antes = (int) $contar->fetchColumn();

        $stmt = $this->db->prepare('DELETE FROM usape WHERE usuar = :usuar');
        $stmt->execute([':usuar' => $usuaId]);
        $eliminados = $stmt->rowCount();

        $contar->execute([':usuar' => $usuaId]);
        $despues = (int) $contar->fetchColumn();

        return ['antes' => $antes, 'eliminados' => $eliminados, 'despues' => $despues];
    }

    public function tienePermiso(int $usuaId, string $permCodi): bool
    {
        $sql = "SELECT 1
                FROM usape up
                INNER JOIN permi p ON p.id = up.permi
                WHERE up.usuar = :usuar
                  AND p.codig = :codig
                  AND p.activ = 1
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':usuar' => $usuaId, ':codig' => $permCodi]);
        return $stmt->fetchColumn() !== false;
    }
}
