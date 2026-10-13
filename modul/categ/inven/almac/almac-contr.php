<?php
/* ============================================================
   Ruta: modul/categ/inven/almac/almac-contr.php
   Módulo: Inventario / Almacenes
   Función: HTTP; delega reglas al servicio.
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
    $pcod = 'ALMAV';
    if (in_array($pact, ['agregar', 'estad', 'principal'], true)) {
        $pcod = 'ALMAG';
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

    CAuth::metod($pact, ['agregar','estad','principal']);

require_once dirname(__DIR__,4).'/contr/categ/inven-db.php';
require_once __DIR__.'/almac-repos.php';
require_once __DIR__.'/almac-servi.php';
$accion=trim((string)($_REQUEST['accion']??'listar'));
try{$db=INVENDB::abrir();$serv=new AlmaServ(new AlmaRepo($db),$db,LOGINDB::abrir());switch($accion){case 'listar':echo json_encode(['ok'=>true,'almacenes'=>$serv->listar((int)($_REQUEST['tiend']??0),(string)($_REQUEST['buscar']??''),(int)($_REQUEST['limite']??50))],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);break;case 'tiendas':echo json_encode(['ok'=>true,'tiendas'=>$serv->tiendas()],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);break;case 'agregar':$r=$serv->agregar((int)($_REQUEST['tiend']??0),(string)($_REQUEST['nombr']??''),(string)($_REQUEST['descr']??''),(string)($_REQUEST['direc']??''),(int)($_REQUEST['prin']??0));echo json_encode(['ok'=>true]+$r,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);break;case 'estad':echo json_encode(['ok'=>true,'almacen'=>$serv->cambiarEstado((int)($_REQUEST['id']??0),(int)($_REQUEST['estad']??0))],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);break;case 'principal':echo json_encode(['ok'=>true,'almacen'=>$serv->principal((int)($_REQUEST['id']??0))],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);break;default:http_response_code(400);echo json_encode(['ok'=>false,'mensaje'=>'Operación no válida.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}}catch(Throwable $e){http_response_code($e instanceof InvalidArgumentException?400:500);echo json_encode(['ok'=>false,'mensaje' => errMsg($e)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
