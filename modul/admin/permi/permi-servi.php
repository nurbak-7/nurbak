<?php
/* ============================================================
   Ruta: modul/admin/permi/permi-servi.php
   Función: Archivo del sistema.
============================================================ */

/* ============================================================
   modul/admin/permi/permi-serv.php — Servicio de permisos
   ------------------------------------------------------------
   🔹 Nivel 1: Listar permisos
   🔹 Nivel 2: Buscar permisos
   🔹 Nivel 3: Consultar un permiso por ID
   🔹 Nivel 4: Validar y registrar un permiso
   🔹 Nivel 5: Validar y actualizar un permiso
   🔹 Nivel 6: Activar o inactivar un permiso
   🔹 Nivel 7: Consultar permisos de un usuario
   🔹 Nivel 8: Comprobar si un usuario tiene un permiso
   🔹 Nivel Final: Procesar la lógica del módulo PERM
============================================================ */

class PermServ
{
    public function __construct(
        private PermRepo $repo
    ) {}

    /* ========================================================
       NIVEL 1 — LISTAR PERMISOS
    ======================================================== */

    public function listar(): array
    {
        return $this->repo->listar();
    }

    /* ========================================================
       NIVEL 2 — BUSCAR PERMISOS
    ======================================================== */

    public function buscar(string $texto): array
    {
        $texto = trim($texto);

        if ($texto === '') {
            return $this->listar();
        }

        return $this->repo->buscar($texto);
    }

    /* ========================================================
       NIVEL 3 — CONSULTAR UN PERMISO POR ID
    ======================================================== */

    public function porId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->repo->porId($id);
    }

    /* ========================================================
       NIVEL 4 — VALIDAR Y REGISTRAR UN PERMISO
    ======================================================== */

    public function crear(
        string $permCodi,
        string $permNomb,
        ?string $permDesc
    ): int {
        $permCodi = strtoupper(trim($permCodi));
        $permNomb = trim($permNomb);
        $permDesc = $permDesc !== null ? trim($permDesc) : null;

        if ($permCodi === '' || $permNomb === '') {
            throw new InvalidArgumentException('Código y nombr son obligatorios.');
        }

        if (mb_strlen($permCodi) > 80) {
            throw new InvalidArgumentException('El código del permiso supera los 80 caracteres.');
        }

        if (mb_strlen($permNomb) > 100) {
            throw new InvalidArgumentException('El nombr del permiso supera los 100 caracteres.');
        }

        if ($permDesc !== null && $permDesc === '') {
            $permDesc = null;
        }

        if ($permDesc !== null && mb_strlen($permDesc) > 150) {
            throw new InvalidArgumentException('La descripción del permiso supera los 150 caracteres.');
        }

        return $this->repo->crear($permCodi, $permNomb, $permDesc);
    }

    /* ========================================================
       NIVEL 5 — VALIDAR Y ACTUALIZAR UN PERMISO
    ======================================================== */

    public function actualizar(
        int $id,
        string $permNomb,
        ?string $permDesc
    ): bool {
        $permNomb = trim($permNomb);
        $permDesc = $permDesc !== null ? trim($permDesc) : null;

        if ($id <= 0) {
            throw new InvalidArgumentException('ID de permiso inválido.');
        }

        if ($permNomb === '') {
            throw new InvalidArgumentException('El nombr del permiso es obligatorio.');
        }

        if (mb_strlen($permNomb) > 100) {
            throw new InvalidArgumentException('El nombr del permiso supera los 100 caracteres.');
        }

        if ($permDesc !== null && $permDesc === '') {
            $permDesc = null;
        }

        if ($permDesc !== null && mb_strlen($permDesc) > 150) {
            throw new InvalidArgumentException('La descripción del permiso supera los 150 caracteres.');
        }

        if ($this->repo->porId($id) === null) {
            throw new InvalidArgumentException('El permiso no existe.');
        }

        return $this->repo->actualizar($id, $permNomb, $permDesc);
    }

    /* ========================================================
       NIVEL 6 — ACTIVAR O INACTIVAR UN PERMISO
    ======================================================== */

    public function cambiarEstado(int $id, bool $activ): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID de permiso inválido.');
        }

        if ($this->repo->porId($id) === null) {
            throw new InvalidArgumentException('El permiso no existe.');
        }

        return $this->repo->cambiarEstado($id, $activ ? 1 : 0);
    }

    /* ========================================================
       NIVEL 7 — CONSULTAR PERMISOS DE UN USUARIO
    ======================================================== */

    public function permisosUsuario(int $usuaId): array
    {
        if ($usuaId <= 0) {
            return [];
        }

        return $this->repo->permisosUsuario($usuaId);
    }

    /* ========================================================
       NIVEL 9 — USUARIOS CON SUS PERMISOS
    ======================================================== */

    public function usuariosConPermisos(string $texto = ''): array
    {
        return $this->repo->usuariosConPermisos(trim($texto));
    }

    /* ========================================================
       NIVEL 10 — GUARDAR ASIGNACIÓN DE PERMISOS
    ======================================================== */

    public function guardarAsignacion(int $usuaId, array $permIds): void
    {
        if ($usuaId <= 0) {
            throw new InvalidArgumentException('Usuario inválido.');
        }

        $permIds = array_values(array_unique(array_filter(
            array_map('intval', $permIds),
            static fn(int $id): bool => $id > 0
        )));

        $this->repo->guardarAsignacion($usuaId, $permIds);
    }

    /* ========================================================
       NIVEL 11 — ELIMINAR ASIGNACIONES DE UN USUARIO
    ======================================================== */

    public function eliminarAsignacion(int $usuaId): array
    {
        if ($usuaId <= 0) {
            throw new InvalidArgumentException('Usuario inválido.');
        }

        return $this->repo->eliminarAsignacion($usuaId);
    }

    /* ========================================================
       NIVEL 8 — COMPROBAR SI UN USUARIO TIENE UN PERMISO
    ======================================================== */

    public function tienePermiso(
        int $usuaId,
        string $permCodi
    ): bool {
        if ($usuaId <= 0) {
            return false;
        }

        $permCodi = trim($permCodi);

        if ($permCodi === '') {
            return false;
        }

        return $this->repo->tienePermiso(
            $usuaId,
            $permCodi
        );
    }
}
