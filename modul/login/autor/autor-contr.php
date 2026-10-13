<?php
/* ============================================================
   Ruta: modul/login/autor/autor-contr.php
   Módulo: LOGI / Autorización
   Función: Exponer la autorización central mediante una entrada
            HTTP para los componentes del sistema que necesiten
            consultar si el usuario actual puede acceder.

   CADENA:
   EVEN.JS / PETICIÓN
       ↓
   AUT-CONT
       ↓
   AUT-SERV
       ↓
   AUT-REPO
       ↓
   LOGI

   IMPORTANTE:
   - AUT no administra permisos.
   - PERM administra las asignaciones.
   - AUT solamente las verifica.
   - El backend de cada módulo podrá reutilizar AUT-SERV para
     realizar una segunda comprobación antes de ejecutar una
     operación protegida.

   🔹 Nivel 1: Cargar sesión y dependencias.
   🔹 Nivel 2: Identificar usuario autenticado.
   🔹 Nivel 3: Resolver autorización.
   🔹 Nivel Final: Entregar JSON.
============================================================ */

declare(strict_types=1);

/* Nivel 1 — Manejo normal de errores. */
require_once __DIR__ . '/../../../contr/error.php';

header('Content-Type: application/json; charset=utf-8');

/* ========================================================
   NIVEL 1 — CARGAR DEPENDENCIAS
======================================================== */

require_once dirname(__DIR__, 3) . '/contr/login/login-db.php';
require_once dirname(__DIR__, 3) . '/contr/login/contr-auth.php';
require_once __DIR__ . '/autor-repos.php';
require_once __DIR__ . '/autor-servi.php';

/* ========================================================
   NIVEL 2 — IDENTIFICAR USUARIO AUTENTICADO
======================================================== */

CAuth::iniciar();
$usuario = CAuth::usuario();
$usuaId = (int) ($usuario['id'] ?? 0);

/* ========================================================
   NIVEL 3 — RESOLVER AUTORIZACIÓN
======================================================== */

try {
    $permCodi = strtoupper(trim((string) (
        $_POST['codig']
        ?? $_GET['codig']
        ?? ''
    )));

    if ($usuaId <= 0) {
        http_response_code(401);
        echo json_encode([
            'ok' => false,
            'autorizado' => false,
            'mensaje' => 'Usuario no autenticado.'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($permCodi === '') {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'autorizado' => false,
            'mensaje' => 'Código de permiso no especificado.'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $repo = new AutRepo(LOGINDB::abrir());
    $serv = new AutServ($repo);
    $autorizado = $serv->autorizar($usuaId, $permCodi);

    /* ====================================================
       NIVEL FINAL — RESPUESTA
    ==================================================== */

    echo json_encode([
        'ok' => true,
        'autorizado' => $autorizado,
        'codig' => $permCodi
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'autorizado' => false,
        'mensaje' => errMsg($e)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
