<?php
/* ============================================================
   Ruta: modul/admin/tiend/tiend-repos.php
   Módulo: Administración / Tiendas
   Función: Ejecutar la persistencia de TIEND.

   🔹 Nivel 1: Listar y buscar tiendas.
   🔹 Nivel 2: Consultar una tienda.
   🔹 Nivel 3: Reservar código.
   🔹 Nivel 4: Crear y actualizar.
   🔹 Nivel 5: Consultar usuarios.
   🔹 Nivel 6: Consultar usuarios disponibles.
   🔹 Nivel 7: Relacionar usuario / tienda.
   🔹 Nivel 8: Gestionar responsable.
   🔹 Nivel 9: Cambiar estado.
   🔹 Nivel 10: Resumen y eliminación.
============================================================ */

declare(strict_types=1);

final class TiendRepo
{
    public function __construct(private PDO $db) {}

    /* ========================================================
       NIVEL 1 — LISTAR / BUSCAR
    ======================================================== */
    private function consultaTiendas(?string $texto = null): array
    {
        $sql = "
            SELECT
                t.id,
                t.codig,
                t.nombr,
                t.direc,
                t.telef,
                t.activ,
                t.cread,
                t.codig AS tiend_codi,
                t.nombr AS tiend_nomb,
                t.direc AS tiend_dire,
                t.telef AS tiend_tele,
                t.cread AS tiend_crea,
                COUNT(ut.usuar) AS usuarios,
                SUM(CASE WHEN ut.respo = 1 THEN 1 ELSE 0 END) AS responsables,
                MAX(CASE WHEN ut.respo = 1 THEN u.nombr ELSE NULL END) AS responsable_nomb,
                MAX(CASE WHEN ut.respo = 1 THEN u.logi ELSE NULL END) AS responsable_logi
            FROM tiend t
            LEFT JOIN ustie ut ON ut.tiend = t.id
            LEFT JOIN usuar u ON u.id = ut.usuar
        ";

        $parametros = [];
        if ($texto !== null && trim($texto) !== '') {
            $sql .= " WHERE t.codig LIKE :texto_codi
                        OR t.nombr LIKE :texto_nomb
                        OR t.direc LIKE :texto_dire
                        OR t.telef LIKE :texto_tele";
            $valor = '%' . trim($texto) . '%';
            $parametros = [
                ':texto_codi' => $valor,
                ':texto_nomb' => $valor,
                ':texto_dire' => $valor,
                ':texto_tele' => $valor,
            ];
        }

        $sql .= " GROUP BY
                    t.id, t.codig, t.nombr, t.direc, t.telef, t.activ, t.cread
                  ORDER BY t.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar(): array { return $this->consultaTiendas(); }
    public function buscar(string $texto): array { return $this->consultaTiendas($texto); }

    /* ========================================================
       NIVEL 2 — POR ID
    ======================================================== */
    public function porId(int $id): ?array
    {
        $sql = "SELECT
                    t.id, t.codig, t.nombr, t.direc, t.telef, t.activ, t.cread,
                    t.codig AS tiend_codi,
                    t.nombr AS tiend_nomb,
                    t.direc AS tiend_dire,
                    t.telef AS tiend_tele,
                    t.cread AS tiend_crea
                FROM tiend t
                WHERE t.id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        return $registro !== false ? $registro : null;
    }

    /* ========================================================
       NIVEL 3 — CÓDIGO
    ======================================================== */
    public function primerLibreCodigo(): int
    {
        $stmt = $this->db->query('SELECT codig FROM tiend WHERE codig IS NOT NULL');
        $usados = [];
        while (($valor = $stmt->fetchColumn()) !== false) {
            if (preg_match('/^T(\d+)$/i', (string) $valor, $match)) {
                $numer = (int) $match[1];
                if ($numer > 0) $usados[$numer] = true;
            }
        }
        $numer = 1;
        while (isset($usados[$numer])) $numer++;
        return $numer;
    }

    public function reservarCodigo(): string
    {
        $stmt = $this->db->query('SELECT ultim FROM tisec WHERE id = 1 FOR UPDATE');
        $ultimo = $stmt->fetchColumn();
        if ($ultimo === false) throw new RuntimeException('No existe la secuencia de tiendas.');

        $numer = $this->primerLibreCodigo();
        if ($numer > (int) $ultimo) {
            $actualizar = $this->db->prepare('UPDATE tisec SET ultim = :ultim WHERE id = 1');
            $actualizar->execute([':ultim' => $numer]);
        }
        return 'T' . str_pad((string) $numer, 3, '0', STR_PAD_LEFT);
    }

    /* ========================================================
       NIVEL 4 — CREAR / ACTUALIZAR
    ======================================================== */
    public function crear(array $datos): int
    {
        $stmt = $this->db->prepare("INSERT INTO tiend (codig, nombr, direc, telef, activ)
                                    VALUES (:codig, :nombr, :direc, :telef, 0)");
        $stmt->execute([
            ':codig' => $datos['codig'],
            ':nombr' => $datos['nombr'],
            ':direc' => $datos['direc'] !== '' ? $datos['direc'] : null,
            ':telef' => $datos['telef'] !== '' ? $datos['telef'] : null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): bool
    {
        $stmt = $this->db->prepare("UPDATE tiend
                                    SET nombr = :nombr, direc = :direc, telef = :telef
                                    WHERE id = :id");
        $stmt->execute([
            ':nombr' => $datos['nombr'],
            ':direc' => $datos['direc'] !== '' ? $datos['direc'] : null,
            ':telef' => $datos['telef'] !== '' ? $datos['telef'] : null,
            ':id' => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    /* ========================================================
       NIVEL 5 — USUARIOS
    ======================================================== */
    public function usuarios(int $tiendId): array
    {
        $sql = "SELECT
                    u.id, u.admic, u.operc, u.logi, u.nombr, u.activ, ut.respo,
                    u.admic AS admi_codi,
                    u.operc AS oper_codi,
                    u.logi AS usua_logi,
                    u.nombr AS usua_nomb,
                    ut.respo AS es_resp
                FROM ustie ut
                INNER JOIN usuar u ON u.id = ut.usuar
                WHERE ut.tiend = :tiend
                ORDER BY ut.respo DESC, u.nombr ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tiend' => $tiendId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ========================================================
       NIVEL 6 — USUARIOS DISPONIBLES
    ======================================================== */
    public function usuariosDisponibles(int $tiendId, string $texto = ''): array
    {
        $sql = "SELECT
                    u.id, u.admic, u.operc, u.logi, u.nombr, u.activ,
                    u.admic AS admi_codi,
                    u.operc AS oper_codi,
                    u.logi AS usua_logi,
                    u.nombr AS usua_nomb
                FROM usuar u
                WHERE u.activ = 1
                  AND NOT EXISTS (
                      SELECT 1 FROM ustie ut
                      WHERE ut.usuar = u.id AND ut.tiend = :tiend
                  )";
        $parametros = [':tiend' => $tiendId];

        if (trim($texto) !== '') {
            $sql .= " AND (
                        u.admic LIKE :texto_admi
                        OR u.operc LIKE :texto_oper
                        OR u.logi LIKE :texto_logi
                        OR u.nombr LIKE :texto_nomb
                      )";
            $valor = '%' . trim($texto) . '%';
            $parametros += [
                ':texto_admi' => $valor,
                ':texto_oper' => $valor,
                ':texto_logi' => $valor,
                ':texto_nomb' => $valor,
            ];
        }
        $sql .= ' ORDER BY u.nombr ASC LIMIT 50';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function usuarioPorId(int $usuaId): ?array
    {
        $stmt = $this->db->prepare("SELECT
                                        id, admic, operc, logi, nombr, activ,
                                        admic AS admi_codi,
                                        operc AS oper_codi,
                                        logi AS usua_logi,
                                        nombr AS usua_nomb
                                    FROM usuar WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $usuaId]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        return $registro !== false ? $registro : null;
    }

    /* ========================================================
       NIVEL 7 — RELACIÓN
    ======================================================== */
    public function relacion(int $usuaId, int $tiendId): ?array
    {
        $stmt = $this->db->prepare("SELECT usuar, tiend, respo
                                    FROM ustie
                                    WHERE usuar = :usuar AND tiend = :tiend LIMIT 1");
        $stmt->execute([':usuar' => $usuaId, ':tiend' => $tiendId]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        return $registro !== false ? $registro : null;
    }

    public function asignar(int $usuaId, int $tiendId, int $esResp = 0): bool
    {
        $stmt = $this->db->prepare('INSERT INTO ustie (usuar, tiend, respo) VALUES (:usuar, :tiend, :respo)');
        $stmt->execute([':usuar' => $usuaId, ':tiend' => $tiendId, ':respo' => $esResp]);
        return $stmt->rowCount() > 0;
    }

    public function quitar(int $usuaId, int $tiendId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM ustie WHERE usuar = :usuar AND tiend = :tiend');
        $stmt->execute([':usuar' => $usuaId, ':tiend' => $tiendId]);
        return $stmt->rowCount() > 0;
    }

    public function quitarResponsabilidad(int $tiendId): void
    {
        $stmt = $this->db->prepare('UPDATE ustie SET respo = 0 WHERE tiend = :tiend');
        $stmt->execute([':tiend' => $tiendId]);
    }

    public function marcarResponsable(int $usuaId, int $tiendId): bool
    {
        $stmt = $this->db->prepare('UPDATE ustie SET respo = 1 WHERE usuar = :usuar AND tiend = :tiend');
        $stmt->execute([':usuar' => $usuaId, ':tiend' => $tiendId]);
        return $stmt->rowCount() > 0;
    }

    /* ========================================================
       NIVEL 8 — ESTADO
    ======================================================== */
    public function cambiarEstado(int $id, int $activ): bool
    {
        $stmt = $this->db->prepare('UPDATE tiend SET activ = :activ WHERE id = :id');
        $stmt->execute([':activ' => $activ, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /* ========================================================
       NIVEL 9 — RESUMEN / ELIMINAR
    ======================================================== */
    public function resumenUsuarios(int $tiendId): array
    {
        $stmt = $this->db->prepare("SELECT
                                        COUNT(*) AS usuarios,
                                        SUM(CASE WHEN respo = 1 THEN 1 ELSE 0 END) AS responsables
                                    FROM ustie WHERE tiend = :tiend");
        $stmt->execute([':tiend' => $tiendId]);
        $dato = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'usuarios' => (int) ($dato['usuarios'] ?? 0),
            'responsables' => (int) ($dato['responsables'] ?? 0),
        ];
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM tiend WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
