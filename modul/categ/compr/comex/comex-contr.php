<?php
/* ============================================================
   Ruta: modul/categ/compr/comex/comex-contr.php
   Módulo: Compras / Compra externa
   Función: Controlador HTTP.
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
    /* AUTORIZACIÓN — Paso 4 | V = consulta, G = gestión. */
    $pact = trim((string)($_REQUEST['accion'] ?? $_REQUEST['acci'] ?? 'listar'));
    $pcod = 'COMEV';
    if (in_array($pact, ['agreg'], true)) {
        $pcod = 'COMEG';
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


    CAuth::metod($pact, ['agreg']);

require_once dirname(__DIR__,4).'/contr/categ/compr-db.php';
require_once dirname(__DIR__,4).'/contr/categ/catal-db.php';
require_once __DIR__.'/comex-repos.php';
$acci=trim((string)($_REQUEST['acci']??'listar'));
try{$repo=new Comex(COMPRDB::abrir(),CATALDB::abrir());if($acci==='lista')echo json_encode(['ok'=>true,'comex'=>$repo->lista((string)($_REQUEST['busc']??''),(int)($_REQUEST['limi']??50))],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);elseif($acci==='opcio')echo json_encode(['ok'=>true,'prove'=>$repo->prove(),'produ'=>$repo->produ()],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);elseif($acci==='agreg'){$id=$repo->crear((int)($_REQUEST['prove']??0),json_decode((string)($_REQUEST['detex']??'[]'),true),trim((string)($_REQUEST['obse']??'')));echo json_encode(['ok'=>true,'id'=>$id,'mensaje'=>'Compra externa registrada.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}else{http_response_code(400);echo json_encode(['ok'=>false,'mensaje'=>'Operación no válida.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}}catch(Throwable $e){http_response_code($e instanceof InvalidArgumentException?400:500);echo json_encode(['ok'=>false,'mensaje' => errMsg($e)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
