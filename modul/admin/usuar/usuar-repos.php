<?php
/* ============================================================
   Ruta: modul/admin/usuar/usuar-repos.php
   Función: Ejecutar las operaciones de persistencia de USUA.

   🔹 Nivel 1: Consultar usuarios.
   🔹 Nivel 2: Buscar usuarios.
   🔹 Nivel 3: Consultar usuario por ID.
   🔹 Nivel 4: Consultar DNI y roles.
   🔹 Nivel 5: Crear usuarios.
   🔹 Nivel 6: Actualizar usuarios.
   🔹 Nivel 7: Cambiar estado.
   🔹 Nivel 8: Eliminar usuario.
   🔹 Nivel 9: Registrar historial de operador.
============================================================ */

declare(strict_types=1);

final class UsuaRepo
{
    public function __construct(private PDO $db) {}

    /* ========================================================
       NIVEL 1 — LISTAR
    ======================================================== */
    public function listar(): array
    {
        $sql = "
            SELECT
                u.id,
                u.empre,
                u.rol,
                u.admic,
                u.operc,
                u.logi,
                u.nombr,
                u.apell,
                u.dni,
                u.celul,
                u.corre,
                u.activ,
                u.fallo,
                u.bloqu,
                u.ultim,
                u.cread,
                r.nombr AS rol_nomb,
                r.descr AS rol_desc,
                r.totac,
                r.totac AS acceso_total,
                u.admic AS admi_codi,
                u.operc AS oper_codi,
                u.logi AS usua_logi,
                u.nombr AS usua_nomb,
                u.apell AS usua_apel,
                u.dni AS usua_dni,
                u.celul AS usua_celu,
                u.corre AS usua_corr,
                u.rol AS rol_id
            FROM usuar u
            INNER JOIN rol r ON r.id = u.rol
            ORDER BY u.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ========================================================
       NIVEL 2 — BUSCAR
    ======================================================== */
    public function buscar(string $texto): array
    {
        $sql = "
            SELECT
                u.id,
                u.empre,
                u.rol,
                u.admic,
                u.operc,
                u.logi,
                u.nombr,
                u.apell,
                u.dni,
                u.celul,
                u.corre,
                u.activ,
                u.fallo,
                u.bloqu,
                u.ultim,
                u.cread,
                r.nombr AS rol_nomb,
                r.descr AS rol_desc,
                r.totac,
                r.totac AS acceso_total,
                u.admic AS admi_codi,
                u.operc AS oper_codi,
                u.logi AS usua_logi,
                u.nombr AS usua_nomb,
                u.apell AS usua_apel,
                u.dni AS usua_dni,
                u.celul AS usua_celu,
                u.corre AS usua_corr,
                u.rol AS rol_id
            FROM usuar u
            INNER JOIN rol r ON r.id = u.rol
            WHERE
                u.admic LIKE :texto_admi
                OR u.operc LIKE :texto_oper
                OR u.logi LIKE :texto_logi
                OR u.nombr LIKE :texto_nomb
                OR u.apell LIKE :texto_apel
                OR u.dni LIKE :texto_dni
                OR u.celul LIKE :texto_celu
                OR u.corre LIKE :texto_corr
                OR r.nombr LIKE :texto_rol
            ORDER BY u.id ASC
        ";

        $valor = '%' . trim($texto) . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':texto_admi' => $valor,
            ':texto_oper' => $valor,
            ':texto_logi' => $valor,
            ':texto_nomb' => $valor,
            ':texto_apel' => $valor,
            ':texto_dni' => $valor,
            ':texto_celu' => $valor,
            ':texto_corr' => $valor,
            ':texto_rol' => $valor,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ========================================================
       NIVEL 3 — POR ID
    ======================================================== */
    public function porId(int $id): ?array
    {
        $sql = "
            SELECT
                u.id,
                u.empre,
                u.rol,
                u.admic,
                u.operc,
                u.logi,
                u.nombr,
                u.apell,
                u.dni,
                u.celul,
                u.corre,
                u.activ,
                u.fallo,
                u.bloqu,
                u.ultim,
                u.cread,
                r.nombr AS rol_nomb,
                r.descr AS rol_desc,
                r.totac,
                r.totac AS acceso_total,
                u.admic AS admi_codi,
                u.operc AS oper_codi,
                u.logi AS usua_logi,
                u.nombr AS usua_nomb,
                u.apell AS usua_apel,
                u.dni AS usua_dni,
                u.celul AS usua_celu,
                u.corre AS usua_corr,
                u.rol AS rol_id
            FROM usuar u
            INNER JOIN rol r ON r.id = u.rol
            WHERE u.id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        return $registro !== false ? $registro : null;
    }

    /* ========================================================
       NIVEL 4 — DNI
    ======================================================== */
    public function buscarPorDni(string $dni): ?array
    {
        $sql = "
            SELECT
                id,
                nombr,
                apell,
                dni,
                celul,
                corre,
                nombr AS usua_nomb,
                apell AS usua_apel,
                dni AS usua_dni,
                celul AS usua_celu,
                corre AS usua_corr
            FROM usuar
            WHERE dni = :dni
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dni' => $dni]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        return $registro !== false ? $registro : null;
    }

    /* ========================================================
       NIVEL 4.1 — ROLES
    ======================================================== */
    public function roles(): array
    {
        $sql = "
            SELECT
                id,
                nombr,
                descr,
                totac,
                activ,
                nombr AS rol_nomb,
                descr AS rol_desc,
                totac AS acceso_total
            FROM rol
            WHERE activ = 1
            ORDER BY nombr ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function rolPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, nombr, descr, totac, activ FROM rol WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        return $registro !== false ? $registro : null;
    }

    /* ========================================================
       NIVEL 4.2 — CONTAR ADMINISTRADORES ACTIVOS
    ======================================================== */
    public function contarAdministradoresActivos(): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM usuar u
            INNER JOIN rol r ON r.id = u.rol
            WHERE r.totac = 1
              AND r.activ = 1
              AND u.activ = 1
        ";
        return (int) $this->db->query($sql)->fetchColumn();
    }

    /* ========================================================
       NIVEL 5 — CREAR
    ======================================================== */
    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO usuar (
                empre, rol, admic, operc, logi, nombr, apell,
                dni, celul, clave, corre, activ
            ) VALUES (
                :empre, :rol, :admic, :operc, :logi, :nombr, :apell,
                :dni, :celul, :clave, :corre, 1
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':empre' => $datos['empre'] ?? null,
            ':rol' => $datos['rol'],
            ':admic' => $datos['admic'] ?? null,
            ':operc' => $datos['operc'] ?? null,
            ':logi' => $datos['logi'],
            ':nombr' => $datos['nombr'],
            ':apell' => $datos['apell'],
            ':dni' => $datos['dni'],
            ':celul' => $datos['celul'] !== '' ? $datos['celul'] : null,
            ':clave' => $datos['clave'],
            ':corre' => $datos['corre'] !== '' ? $datos['corre'] : null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /* ========================================================
       NIVEL 6 — ACTUALIZAR
    ======================================================== */
    public function actualizar(int $id, array $datos): bool
    {
        $sql = "
            UPDATE usuar
            SET
                rol = :rol,
                admic = :admic,
                operc = :operc,
                logi = :logi,
                nombr = :nombr,
                apell = :apell,
                dni = :dni,
                celul = :celul,
                corre = :corre
        ";

        $parametros = [
            ':rol' => $datos['rol'],
            ':admic' => $datos['admic'] ?? null,
            ':operc' => $datos['operc'] ?? null,
            ':logi' => $datos['logi'],
            ':nombr' => $datos['nombr'],
            ':apell' => $datos['apell'],
            ':dni' => $datos['dni'],
            ':celul' => $datos['celul'] !== '' ? $datos['celul'] : null,
            ':corre' => $datos['corre'] !== '' ? $datos['corre'] : null,
            ':id' => $id,
        ];

        if (!empty($datos['clave'])) {
            $sql .= ', clave = :clave';
            $parametros[':clave'] = $datos['clave'];
        }

        $sql .= ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->rowCount() > 0;
    }

    /* ========================================================
       NIVEL 7 — ESTADO
    ======================================================== */
    public function cambiarEstado(int $id, int $activ): bool
    {
        $stmt = $this->db->prepare("UPDATE usuar SET activ = :activ WHERE id = :id");
        $stmt->execute([':activ' => $activ, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /* ========================================================
       NIVEL 8 — ELIMINAR
    ======================================================== */
    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM usuar WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /* ========================================================
       NIVEL 9 — HISTORIAL DE OPERADOR
    ======================================================== */
    public function registrarOperador(string $codig, int $usuaId): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO ophis (operc, usuar) VALUES (:operc, :usuar)"
        );
        $stmt->execute([':operc' => $codig, ':usuar' => $usuaId]);
    }

    public function porOperc(string $codig): ?array
    {
        $codig=strtoupper(trim($codig));
        if($codig==='')return null;
        $st=$this->db->prepare("SELECT u.id,u.admic,u.operc,u.nombr,u.apell,r.nombr AS rol_nomb
                                FROM usuar u INNER JOIN rol r ON r.id=u.rol
                                WHERE u.activ=1 AND r.activ=1
                                  AND r.nombr IN ('USUARIO','ADMINISTRADOR')
                                  AND (u.admic=:admi OR u.operc=:oper) LIMIT 1");
        $st->execute(['admi'=>$codig,'oper'=>$codig]);$r=$st->fetch(PDO::FETCH_ASSOC);
        if(!$r)return null;
        $nom=preg_split('/\s+/u',trim((string)$r['nombr']),-1,PREG_SPLIT_NO_EMPTY)?:[];
        $ape=preg_split('/\s+/u',trim((string)$r['apell']),-1,PREG_SPLIT_NO_EMPTY)?:[];
        return ['id'=>(int)$r['id'],'codig'=>$r['admic']?:$r['operc'],'rol'=>$r['rol_nomb'],'nombr'=>$nom[0]??'','apellido'=>$ape[0]??'','display'=>trim(($nom[0]??'').' '.($ape[0]??''))];
    }
}
