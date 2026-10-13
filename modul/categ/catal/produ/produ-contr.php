<?php
/* ============================================================
   Ruta: modul/categ/catal/produ/produ-contr.php
   Módulo: Catálogo / Productos
   Función: Entrada HTTP de Productos.

   🔹 NIVEL 1: Leer petición.
   🔹 NIVEL 2: Dirigir al repositorio.
   🔹 NIVEL FINAL: Responder JSON.
============================================================ */
declare(strict_types=1);

/* Nivel 1 — Manejo normal de errores. */
require_once __DIR__ . '/../../../../contr/error.php';
header('Content-Type: application/json; charset=utf-8');
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
    /* AUTORIZACIÓN — Paso 4 | V = consulta, G = gestión. */
    $pact = trim((string)($_REQUEST['accion'] ?? $_REQUEST['acci'] ?? 'listar'));
    $pcod = 'PRODV';
    if (in_array($pact, ['guardar', 'eliminar'], true)) {
        $pcod = 'PRODG';
    }
    $contPerm = new ContPerm(LOGINDB::abrir());
    if (!$contPerm->autorizar($pcod)) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Operación no autorizada.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }


    CAuth::metod($pact, ['guardar','eliminar']);

require_once dirname(__DIR__,4).'/contr/categ/catal-db.php';
require_once __DIR__.'/produ-repos.php';
$accion=trim((string)($_REQUEST['accion']??'listar')); $buscar=trim((string)($_REQUEST['buscar']??'')); $limite=(int)($_REQUEST['limite']??50);
try{
    $repo=new ProdRepo(CATALDB::abrir());
    switch($accion){
        case 'listar': echo json_encode(['ok'=>true,'productos'=>$repo->listar($buscar,$limite)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); break;
        case 'obtener': echo json_encode(['ok'=>true,'producto'=>$repo->obtener((int)($_REQUEST['id']??0))],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); break;
        case 'guardar':
            $id=$repo->guardar($_REQUEST); echo json_encode(['ok'=>true,'id'=>$id,'mensaje'=>'Producto guardado.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); break;
        case 'eliminar':
            $repo->eliminar((int)($_REQUEST['id']??0)); echo json_encode(['ok'=>true,'mensaje'=>'Producto eliminado.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); break;
        default: http_response_code(400); echo json_encode(['ok'=>false,'mensaje'=>'Operación no válida.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
}catch(PDOException $e){http_response_code($e->getCode()==='23000'?409:500);echo json_encode(['ok'=>false,'mensaje'=>$e->getCode()==='23000'?'No se pudo modificar el producto por una relación existente.':'No se pudo completar la operación.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}catch(Throwable $e){http_response_code(400);echo json_encode(['ok'=>false,'mensaje' => errMsg($e)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
