<?php
/* ============================================================
   Ruta: modul/admin/secue/secue-servi.php
   Función: Aplicar las reglas de numeración de SECU.

   🔹 Nivel 1: Consultar el primer código libre.
   🔹 Nivel 2: Formatear códigos de administrador y operador.
   🔹 Nivel 3: Consumir una secuencia durante una transacción.
   🔹 Nivel Final: Entregar el código reservado.
============================================================ */

declare(strict_types=1);

final class SecuServ
{
    public function __construct(private SecuRepo $repo)
    {
    }

    /* ========================================================
       NIVEL 1 — PREVISUALIZAR ADMINISTRADOR
       ======================================================== */
    public function siguienteAdministrador(): string
    {
        return 'A' . str_pad(
            (string) $this->repo->primerLibre('ADMINISTRADOR'),
            3,
            '0',
            STR_PAD_LEFT
        );
    }

    /* ========================================================
       NIVEL 2 — PREVISUALIZAR OPERADOR
       ======================================================== */
    public function siguienteOperador(): string
    {
        return str_pad(
            (string) $this->repo->primerLibre('OPERADOR'),
            3,
            '0',
            STR_PAD_LEFT
        );
    }

    /* ========================================================
       NIVEL 3 — CONSUMIR ADMINISTRADOR
       ======================================================== */
    public function reservarAdministrador(): string
    {
        return 'A' . str_pad(
            (string) $this->repo->reservar('ADMINISTRADOR'),
            3,
            '0',
            STR_PAD_LEFT
        );
    }

    /* ========================================================
       NIVEL 4 — CONSUMIR OPERADOR
       ======================================================== */
    public function reservarOperador(): string
    {
        return str_pad(
            (string) $this->repo->reservar('OPERADOR'),
            3,
            '0',
            STR_PAD_LEFT
        );
    }
}
