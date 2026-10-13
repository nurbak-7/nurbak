<?php
/* ============================================================
   Ruta: modul/categ/venta/devol/devol-contr.php
   Módulo: Ventas / Devoluciones

   RESPONSABILIDAD:
   Consultar devoluciones y registrar solicitudes de devolución.

   IMPORTANTE:
   Una devolución nueva queda PENDIENTE. No modifica Inventario
   hasta que exista una acción explícita de aprobación.
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
    /* AUTORIZACIÓN — Paso 4 | Permiso DEVOG requerido en el servidor. */
    $contPerm = new ContPerm(LOGINDB::abrir());
    if (!$contPerm->autorizar('DEVOG')) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Operación no autorizada.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }


require_once dirname(__DIR__,4) . '/contr/categ/venta-db.php';
require_once dirname(__DIR__,4) . '/contr/categ/inven-db.php';

require_once __DIR__ . '/devol-repos.php';

$accion=trim((string)($_REQUEST['accion']??'listar'));
$buscar=trim((string)($_REQUEST['buscar']??''));
$limite=(int)($_REQUEST['limite']??50);


try{
    $repo=new DevoRepo(VENTADB::abrir(),INVENDB::abrir());
    switch($accion){
        case 'listar':
            $r=$repo->listar($buscar,$limite);
            
            echo json_encode(['ok'=>true,'devoluciones'=>$r],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); break;
        case 'opciones':
            $c=$repo->clientes();$a=$repo->almacenes();
            
            echo json_encode(['ok'=>true,'clientes'=>$c,'almacenes'=>$a],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); break;
        default:
            http_response_code(400);
            echo json_encode(['ok'=>false,'mensaje'=>'Operación no válida.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
}catch(Throwable $e){
    http_response_code(500);
    
    echo json_encode(['ok'=>false,'mensaje' => errMsg($e)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
