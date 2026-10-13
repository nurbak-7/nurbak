<?php
/* ============================================================
   Ruta: modul/categ/inven/movim/movim-contr.php
   Módulo: Inventario / Movimientos
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
    $pcod = 'MOVIV';
    if (in_array($pact, ['registrar'], true)) {
        $pcod = 'MOVIG';
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

    CAuth::metod($pact, ['registrar']);

require_once dirname(__DIR__,4).'/contr/categ/inven-db.php';
require_once __DIR__.'/movim-repos.php';
require_once __DIR__.'/movim-servi.php';
$accion=trim((string)($_REQUEST['accion']??'listar'));
try{$serv=new MoviServ(new MoviRepo(INVENDB::abrir()),LOGINDB::abrir());$tiend=(int)($_REQUEST['tiend']??0);switch($accion){case 'listar':echo json_encode(['ok'=>true,'movimientos'=>$serv->listar($tiend,(int)($_REQUEST['almac']??0),(string)($_REQUEST['buscar']??''),(int)($_REQUEST['limite']??50))],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);break;case 'opciones':echo json_encode(['ok'=>true]+$serv->opciones($tiend),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);break;case 'registrar':$id=$serv->registrar($tiend,(int)($_REQUEST['produ']??0),(int)($_REQUEST['almac']??0),(string)($_REQUEST['tipo']??''),(float)($_REQUEST['canti']??0),(string)($_REQUEST['motvo']??''));echo json_encode(['ok'=>true,'id'=>$id],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);break;default:http_response_code(400);echo json_encode(['ok'=>false,'mensaje'=>'Operación no válida.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}}catch(Throwable $e){http_response_code($e instanceof InvalidArgumentException?400:500);echo json_encode(['ok'=>false,'mensaje' => errMsg($e)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
