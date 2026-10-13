<?php
/* ============================================================
   Ruta: modul/admin/tiend/tiend-servi.php
   Módulo: Administración / Tiendas
   Función: Aplicar las reglas de negocio de TIEND.

   🔹 Nivel 1: Consultar tiendas.
   🔹 Nivel 2: Crear tienda.
   🔹 Nivel 3: Actualizar tienda.
   🔹 Nivel 4: Consultar usuarios.
   🔹 Nivel 5: Asignar usuario.
   🔹 Nivel 6: Cambiar responsable.
   🔹 Nivel 7: Quitar usuario.
   🔹 Nivel 8: Activar / inactivar.
   🔹 Nivel 9: Eliminar tienda.
   🔹 Nivel Final: Mantener la integridad de TIEND.
============================================================ */

declare(strict_types=1);

final class TiendServ
{
    public function __construct(
        private TiendRepo $repo,
        private PDO $db,
        private AlmaServ $alma
    ) {
    }

    /* ========================================================
       NIVEL 1 — CONSULTAR
    ======================================================== */

    public function listar(): array
    {
        return $this->repo->listar();
    }

    public function buscar(string $texto): array
    {
        $texto = trim($texto);
        return $texto === '' ? $this->repo->listar() : $this->repo->buscar($texto);
    }

    public function porId(int $id): ?array
    {
        return $id > 0 ? $this->repo->porId($id) : null;
    }

    public function usuarios(int $tiendId): array
    {
        $this->validarTienda($tiendId);
        return $this->repo->usuarios($tiendId);
    }

    public function usuariosDisponibles(int $tiendId, string $texto = ''): array
    {
        $this->validarTienda($tiendId);
        return $this->repo->usuariosDisponibles($tiendId, $texto);
    }

    /* ========================================================
       NIVEL 1.1 — ALMACENES DE TIENDA
    ======================================================== */

    public function almas(int $tiendId): array
    {
        $this->validarTienda($tiendId);
        return $this->alma->listar($tiendId, '', 100);
    }

    public function alcre(int $tiendId, array $entrada): array
    {
        $this->validarTienda($tiendId);

        $nombr = trim((string) ($entrada['nombr'] ?? ''));
        $descr = trim((string) ($entrada['descr'] ?? ''));
        $direc = trim((string) ($entrada['direc'] ?? ''));
        $prin = (int) ($entrada['prin'] ?? 0);

        if ($nombr === '') {
            throw new InvalidArgumentException('El nombr del almacén es obligatorio.');
        }
        if (!in_array($prin, [0, 1], true)) {
            throw new InvalidArgumentException('Principal no válido.');
        }

        return $this->alma->agregar($tiendId, $nombr, $descr, $direc, $prin);
    }

    public function alest(int $id, int $estad): array
    {
        if (!in_array($estad, [0, 1], true)) {
            throw new InvalidArgumentException('Estado de almacén no válido.');
        }
        return $this->alma->cambiarEstado($id, $estad);
    }

    public function alpri(int $id): array
    {
        return $this->alma->principal($id);
    }

    public function siguienteCodigo(): string
    {
        $numer = $this->repo->primerLibreCodigo();
        return 'T' . str_pad((string) $numer, 3, '0', STR_PAD_LEFT);
    }

    /* ========================================================
       NIVEL 2 — CREAR TIENDA
    ======================================================== */

    public function crear(array $entrada): array
    {
        $nombr = trim((string) ($entrada['nombr'] ?? ''));
        $direc = trim((string) ($entrada['direc'] ?? ''));
        $telef = trim((string) ($entrada['telef'] ?? ''));

        $this->validarDatos($nombr, $direc, $telef);

        try {
            $this->db->beginTransaction();

            $codig = $this->repo->reservarCodigo();

            $id = $this->repo->crear([
                'codig' => $codig,
                'nombr' => $nombr,
                'direc' => $direc,
                'telef' => $telef,
            ]);

            $this->db->commit();

            return $this->repo->porId($id) ?? [
                'id' => $id,
                'codig' => $codig,
                'nombr' => $nombr,
                'direc' => $direc,
                'telef' => $telef,
                'activ' => 0,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /* ========================================================
       NIVEL 3 — ACTUALIZAR TIENDA
    ======================================================== */

    public function actualizar(int $id, array $entrada): array
    {
        $this->validarTienda($id);

        $nombr = trim((string) ($entrada['nombr'] ?? ''));
        $direc = trim((string) ($entrada['direc'] ?? ''));
        $telef = trim((string) ($entrada['telef'] ?? ''));

        $this->validarDatos($nombr, $direc, $telef);

        $this->repo->actualizar($id, [
            'nombr' => $nombr,
            'direc' => $direc,
            'telef' => $telef,
        ]);

        return $this->repo->porId($id) ?? [];
    }

    /* ========================================================
       NIVEL 4 — ASIGNAR USUARIO
    ======================================================== */

    public function asignarUsuario(int $tiendId, int $usuaId, int $esResp = 0): array
    {
        $tienda = $this->validarTienda($tiendId);

        if ($usuaId <= 0) {
            throw new InvalidArgumentException('Usuario no válido.');
        }

        $usuario = $this->repo->usuarioPorId($usuaId);
        if (!$usuario || (int) $usuario['activ'] !== 1) {
            throw new InvalidArgumentException('El usuario no existe o está inactivo.');
        }

        if ($this->repo->relacion($usuaId, $tiendId)) {
            throw new InvalidArgumentException('El usuario ya está asignado a esta tienda.');
        }

        if (!in_array($esResp, [0, 1], true)) {
            throw new InvalidArgumentException('Responsable no válido.');
        }

        try {
            $this->db->beginTransaction();

            if ($esResp === 1) {
                $this->repo->quitarResponsabilidad($tiendId);
            }

            $this->repo->asignar($usuaId, $tiendId, $esResp);

            $this->db->commit();

            return [
                'tienda' => $tienda,
                'usuario' => $usuario,
                'respo' => $esResp,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /* ========================================================
       NIVEL 5 — CAMBIAR RESPONSABLE
    ======================================================== */

    public function responsable(int $tiendId, int $usuaId): array
    {
        $tienda = $this->validarTienda($tiendId);

        if ($usuaId <= 0) {
            throw new InvalidArgumentException('Usuario no válido.');
        }

        $usuario = $this->repo->usuarioPorId($usuaId);
        if (!$usuario || (int) $usuario['activ'] !== 1) {
            throw new InvalidArgumentException('El usuario no existe o está inactivo.');
        }

        if (!$this->repo->relacion($usuaId, $tiendId)) {
            throw new InvalidArgumentException('El usuario no está asignado a esta tienda.');
        }

        try {
            $this->db->beginTransaction();

            $this->repo->quitarResponsabilidad($tiendId);

            if (!$this->repo->marcarResponsable($usuaId, $tiendId)) {
                throw new RuntimeException('No se pudo establecer el responsable.');
            }

            $this->db->commit();

            return [
                'tienda' => $tienda,
                'usuario' => $usuario,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /* ========================================================
       NIVEL 6 — QUITAR USUARIO
    ======================================================== */

    public function quitarUsuario(int $tiendId, int $usuaId): array
    {
        $tienda = $this->validarTienda($tiendId);
        $relacion = $this->repo->relacion($usuaId, $tiendId);

        if (!$relacion) {
            throw new InvalidArgumentException('El usuario no está asignado a esta tienda.');
        }

        $resumen = $this->repo->resumenUsuarios($tiendId);

        if ((int) $relacion['respo'] === 1 && $resumen['usuarios'] > 1) {
            throw new InvalidArgumentException(
                'El responsable no puede retirarse mientras la tienda tenga otros usuarios. Primero designe otro responsable.'
            );
        }

        try {
            $this->db->beginTransaction();

            if (!$this->repo->quitar($usuaId, $tiendId)) {
                throw new RuntimeException('No se pudo retirar el usuario.');
            }

            /*
               Si era el último usuario, la tienda deja de ser operativa.
            */
            $restantes = $this->repo->resumenUsuarios($tiendId);

            if ($restantes['usuarios'] === 0 && (int) $tienda['activ'] === 1) {
                $this->repo->cambiarEstado($tiendId, 0);
            }

            $this->db->commit();

            return $this->repo->porId($tiendId) ?? [];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /* ========================================================
       NIVEL 7 — ACTIVAR / INACTIVAR
    ======================================================== */

    public function cambiarEstado(int $id, int $activ): array
    {
        $tienda = $this->validarTienda($id);

        if (!in_array($activ, [0, 1], true)) {
            throw new InvalidArgumentException('Estado no válido.');
        }

        if ($activ === 1) {
            $resumen = $this->repo->resumenUsuarios($id);

            if ($resumen['usuarios'] < 1) {
                throw new InvalidArgumentException('La tienda necesita al menos un usuario para activarse.');
            }

            if ($resumen['responsables'] !== 1) {
                throw new InvalidArgumentException('La tienda debe tener exactamente un responsable para activarse.');
            }

            $almacenes = $this->alma->listar($id, '', 100);
            $principal = null;
            foreach ($almacenes as $almacen) {
                if ((int) ($almacen['prin'] ?? 0) === 1) {
                    $principal = $almacen;
                    break;
                }
            }

            if (!$principal) {
                throw new InvalidArgumentException('La tienda necesita un almacén principal para activarse.');
            }

            if ((int) ($principal['estad'] ?? 0) !== 1) {
                throw new InvalidArgumentException('El almacén principal debe estar activo para activar la tienda.');
            }
        }

        $this->repo->cambiarEstado($id, $activ);

        return $this->repo->porId($id) ?? $tienda;
    }

    /* ========================================================
       NIVEL 8 — ELIMINAR
    ======================================================== */

    public function eliminar(int $id): array
    {
        $tienda = $this->validarTienda($id);
        $resumen = $this->repo->resumenUsuarios($id);

        if ((int) $tienda['activ'] === 1) {
            throw new InvalidArgumentException('Primero debe inactivar la tienda.');
        }

        if ($resumen['usuarios'] > 0) {
            throw new InvalidArgumentException('No se puede eliminar una tienda que tiene usuarios asignados.');
        }

        try {
            $this->db->beginTransaction();

            if (!$this->repo->eliminar($id)) {
                throw new RuntimeException('No se pudo eliminar la tienda.');
            }

            $this->db->commit();
            return $tienda;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /* ========================================================
       NIVEL 9 — VALIDACIONES
    ======================================================== */

    private function validarTienda(int $id): array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Tienda no válida.');
        }

        $tienda = $this->repo->porId($id);

        if (!$tienda) {
            throw new InvalidArgumentException('La tienda no existe.');
        }

        return $tienda;
    }

    private function validarDatos(string $nombr, string $direc, string $telef): void
    {
        if ($nombr === '') {
            throw new InvalidArgumentException('El nombr de la tienda es obligatorio.');
        }

        if (mb_strlen($nombr) > 100) {
            throw new InvalidArgumentException('El nombr de la tienda supera los 100 caracteres.');
        }

        if (mb_strlen($direc) > 200) {
            throw new InvalidArgumentException('La dirección supera los 200 caracteres.');
        }

        if (mb_strlen($telef) > 30) {
            throw new InvalidArgumentException('El teléfono supera los 30 caracteres.');
        }
    }
}
