<?php
/* ============================================================
   Ruta: modul/categ/factu/ncred/ncred-contr.php
   Función: Archivo del sistema.
============================================================ */

declare(strict_types=1);
/* Nivel 1 — Manejo normal de errores. */
require_once __DIR__ . '/../../../../contr/error.php'; header('Content-Type: application/json; charset=utf-8');
/* SESIÓN — autoridad de acceso antes de ejecutar el módulo. */
require_once dirname(__DIR__,4) . '/contr/login/login-db.php';
require_once dirname(__DIR__,4) . '/contr/login/contr-auth.php';
require_once dirname(__DIR__,4) . '/contr/login/contr-perm.php';
if (!CAuth::autenticado()) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Sesión no válida.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
    /* AUTORIZACIÓN — Paso 4 | Permiso NCREG requerido en el servidor. */
    $contPerm = new ContPerm(LOGINDB::abrir());
    if (!$contPerm->autorizar('NCREG')) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Operación no autorizada.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

require_once dirname(__DIR__,4).'/contr/categ/factu-db.php';
require_once __DIR__.'/ncred-repos.php';
$accion=trim((string)($_REQUEST['accion']??'listar')); $buscar=trim((string)($_REQUEST['buscar']??'')); $limite=(int)($_REQUEST['limite']??50);

try{ $repo=new NcreRepo(FACTUDB::abrir()); if($accion==='listar'){ $r=$repo->listar($buscar,$limite);  echo json_encode(['ok'=>true,'registros'=>$r],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); } else { http_response_code(400);  echo json_encode(['ok'=>false,'mensaje'=>'Operación no válida.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); } } catch(Throwable $e){ http_response_code(500);  echo json_encode(['ok'=>false,'mensaje' => errMsg($e)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); }
