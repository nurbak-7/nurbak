<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../../contr/error.php';
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__,4) . '/contr/login/login-db.php';
require_once dirname(__DIR__,4) . '/contr/login/contr-auth.php';
require_once dirname(__DIR__,4) . '/contr/login/contr-perm.php';
if (!CAuth::autenticado()) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'mensaje'=>'Sesión no válida.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
$pact=trim((string)($_REQUEST['accion']??$_REQUEST['acci']??'listar'));
$pcod=in_array($pact,['agregar','reint'],true)?'RECEG':'RECEV';
$contPerm=new ContPerm(LOGINDB::abrir());
if(!$contPerm->autorizar($pcod)){
    http_response_code(403);
    echo json_encode(['ok'=>false,'mensaje'=>'Operación no autorizada.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
CAuth::metod($pact,['agregar','reint']);
require_once dirname(__DIR__,4).'/contr/categ/compr-db.php';
require_once dirname(__DIR__,4).'/contr/categ/catal-db.php';
require_once dirname(__DIR__,4).'/contr/categ/inven-db.php';
require_once __DIR__.'/recep-repos.php';
$accion=trim((string)($_REQUEST['accion']??'listar'));$buscar=trim((string)($_REQUEST['buscar']??''));$limite=(int)($_REQUEST['limite']??50);
try{
    $repo=new ReceRepo(COMPRDB::abrir(),CATALDB::abrir(),INVENDB::abrir());
    switch($accion){
        case'listar':
            echo json_encode(['ok'=>true,'recepciones'=>$repo->listar($buscar,$limite)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);break;
        case'opciones':
            echo json_encode(['ok'=>true,'proveedores'=>$repo->proveedores(),'productos'=>$repo->productos(),'almacenes'=>$repo->almacenes(),'comex'=>$repo->comex()],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);break;
        case'agregar':
            echo json_encode(['ok'=>true]+$repo->crear($_REQUEST),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);break;
        case'reint':
            echo json_encode(['ok'=>true]+$repo->reint((int)($_REQUEST['id']??0)),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);break;
        default:
            http_response_code(400);
            echo json_encode(['ok'=>false,'mensaje'=>'Operación no válida.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
}catch(Throwable $e){
    http_response_code($e instanceof InvalidArgumentException?400:500);
    echo json_encode(['ok'=>false,'mensaje'=>errMsg($e)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
