<?php
/* ============================================================
   Ruta: modul/categ/compr/prove/prove-contr.php
   Módulo: Compras / Proveedores

   RESPONSABILIDAD:
   Recibir la petición HTTP, validar la acción y delegar el
   acceso SQL a ProvRepo.
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
    $pcod = 'PROVV';
    if (in_array($pact, ['agregar', 'estad'], true)) {
        $pcod = 'PROVG';
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


    CAuth::metod($pact, ['agregar','estad']);

require_once dirname(__DIR__,4).'/contr/categ/compr-db.php';

require_once __DIR__.'/prove-repos.php';

$accion=trim((string)($_REQUEST['accion']??'listar'));
$buscar=trim((string)($_REQUEST['buscar']??''));
$limite=(int)($_REQUEST['limite']??50);


try {
    $repo=new ProvRepo(COMPRDB::abrir());
    switch($accion){
        case 'listar':
            $r=$repo->listar($buscar,$limite);
            
            echo json_encode(['ok'=>true,'proveedores'=>$r],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); break;
        case 'agregar':
            $id=$repo->agregar($_REQUEST);
            
            echo json_encode(['ok'=>true,'id'=>$id,'mensaje'=>'Proveedor registrado.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); break;
        case 'estad':
            $ok=$repo->estad((int)($_REQUEST['id']??0),(int)($_REQUEST['estad']??0));
            
            echo json_encode(['ok'=>true,'ultim'=>$ok],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); break;
        default:
            http_response_code(400); 
            echo json_encode(['ok'=>false,'mensaje'=>'Operación no válida.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
} catch(Throwable $e){
    http_response_code(500);
    
    echo json_encode(['ok'=>false,'mensaje'=>'No se pudo procesar Proveedores.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
