<?php
/* ============================================================
   Ruta: modul/login/aut/autor-servi.php
   Módulo: LOGI / Autorización
   Función: Aplicar las reglas centrales de autorización.

   AUT no administra permisos.
   AUT utiliza los permisos administrados por ADMI / PERM.

   🔹 Nivel 1: Validar usuario.
   🔹 Nivel 2: Resolver acceso total.
   🔹 Nivel 3: Resolver permiso individual.
   🔹 Nivel Final: Entregar una decisión de autorización.
============================================================ */

declare(strict_types=1);

final class AutServ
{
    public function __construct(
        private AutRepo $repo
    ) {
    }

    /* ========================================================
       NIVEL 1 — VALIDAR USUARIO
    ======================================================== */

    public function usuarioValido(int $usuaId): bool
    {
        return $usuaId > 0;
    }

    /* ========================================================
       NIVEL 2 — RESOLVER ACCESO TOTAL
    ======================================================== */

    public function tieneAccesoTotal(int $usuaId): bool
    {
        if (!$this->usuarioValido($usuaId)) {
            return false;
        }

        return $this->repo->tieneAccesoTotal($usuaId);
    }

    /* ========================================================
       NIVEL 3 — RESOLVER AUTORIZACIÓN
    ======================================================== */

    public function tienePermiso(int $usuaId, string $permCodi): bool
    {
        if (!$this->usuarioValido($usuaId)) {
            return false;
        }

        $permCodi = strtoupper(trim($permCodi));

        if ($permCodi === '') {
            return false;
        }

        /* El acceso total del rol supera cualquier permiso individual. */
        if ($this->repo->tieneAccesoTotal($usuaId)) {
            return true;
        }

        return $this->repo->tienePermiso($usuaId, $permCodi);
    }

    /* ========================================================
       NIVEL FINAL — AUTORIZAR
    ======================================================== */

    public function autorizar(int $usuaId, string $permCodi): bool
    {
        return $this->tienePermiso($usuaId, $permCodi);
    }
}
