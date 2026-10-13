<?php
declare(strict_types=1);

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

$pact = trim((string)($_REQUEST['accion'] ?? $_REQUEST['acci'] ?? 'listar'));
CAuth::metod($pact, ['guardar-borrador','agregar','latid','vacia','confirmar','cancelar']);

/* AUTORIZACIÓN — Paso 4 | Permiso VENTG requerido en el servidor. */
$contPerm = new ContPerm(LOGINDB::abrir());

if (!$contPerm->autorizar('VENTG')) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Operación no autorizada.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$base = dirname(__DIR__,4);

require_once $base.'/contr/categ/venta-db.php';
require_once $base.'/contr/categ/catal-db.php';
require_once $base.'/contr/categ/inven-db.php';
require_once $base.'/contr/categ/factu-db.php';
require_once __DIR__.'/opera-repos.php';
require_once __DIR__.'/opera-servi.php';
require_once $base.'/modul/categ/venta/clien/clien-repos.php';
require_once $base.'/modul/categ/catal/produ/produ-repos.php';
require_once $base.'/modul/categ/inven/almac/almac-repos.php';
require_once $base.'/modul/categ/inven/stock/stock-repos.php';
require_once $base.'/modul/categ/inven/reser/reser-repos.php';
require_once $base.'/modul/admin/usuar/usuar-repos.php';
require_once $base.'/modul/categ/factu/bolet/bolet-repos.php';
require_once $base.'/modul/categ/factu/factu/factu-repos.php';
require_once $base.'/modul/categ/venta/clien/exter-servi.php';

$accion = trim((string)($_REQUEST['accion'] ?? 'listar'));
$buscar = trim((string)($_REQUEST['buscar'] ?? ''));
$limite = (int)($_REQUEST['limite'] ?? 50);

try {
    $vent = VENTADB::abrir();
    $cata = CATALDB::abrir();
    $inve = INVENDB::abrir();
    $factu = FACTUDB::abrir();
    $logi = LOGINDB::abrir();

    $repo = new OperaRepo($vent);

    $serv = new OperaServ(
        $repo,
        new ClieRepo($vent),
        new ProdRepo($cata),
        new AlmaRepo($inve),
        new StocRepo($inve, $cata),
        new ReseR($inve),
        new UsuaRepo($logi),
        new BoleRepo($factu),
        new FactRepo($factu)
    );

    switch ($accion) {
        case 'listar':
            echo json_encode(
                ['ok'=>true,'operaciones'=>$serv->listar($buscar,$limite)],
                JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
            );
            break;

        case 'buscar-cliente-local':
            $doc = preg_replace('/\D+/','',(string)($_REQUEST['docum'] ?? ''));
            if (!in_array(strlen($doc),[8,11],true)) {
                throw new InvalidArgumentException('RUC/DNI no válido.');
            }
            $r = $serv->buscarCliente($doc);
            echo json_encode(
                ['ok'=>true,'encontrado'=>(bool)$r,'clien'=>$r],
                JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
            );
            break;

        case 'buscar-cliente-externo':
            $doc = preg_replace('/\D+/','',(string)($_REQUEST['docum'] ?? ''));
            if (!in_array(strlen($doc),[8,11],true)) {
                throw new InvalidArgumentException('RUC/DNI no válido.');
            }
            echo json_encode(
                OperaClienteExterno::consultar($doc),
                JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
            );
            break;

        case 'buscar-vendedor':
            $r = $serv->buscarVendedor((string)($_REQUEST['codig'] ?? ''));
            echo json_encode(
                ['ok'=>true,'encontrado'=>(bool)$r,'vendedor'=>$r],
                JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
            );
            break;

        case 'buscar-productos':
            $r = $serv->buscarProductos(
                $buscar,
                (int)($_REQUEST['almac'] ?? 0),
                $limite
            );
            echo json_encode(
                ['ok'=>true,'productos'=>$r],
                JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
            );
            break;

        case 'opciones':
            echo json_encode(
                ['ok'=>true]+$serv->opciones(),
                JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
            );
            break;

        case 'numeracion':
            $docum = preg_replace('/\\D+/','',(string)($_REQUEST['docum'] ?? ''));
            echo json_encode(
                ['ok'=>true,'numeracion'=>$serv->numeracion($docum)],
                JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
            );
            break;

        case 'guardar-borrador':
        case 'agregar':
            $det = json_decode((string)($_REQUEST['detalles'] ?? '[]'),true);

            if (!is_array($det)) {
                throw new InvalidArgumentException('Detalle de operación no válido.');
            }

            $clien = trim((string)($_REQUEST['clien'] ?? ''));

            $r = $serv->guardarBorrador([
                'opera'=>(int)($_REQUEST['opera'] ?? 0),
                'clien'=>$clien!==''?(int)$clien:null,
                'tipod'=>strtoupper(trim((string)($_REQUEST['tipod'] ?? ''))),
                'docum'=>preg_replace('/\D+/','',(string)($_REQUEST['docum'] ?? '')),
                'razon'=>trim((string)($_REQUEST['razon'] ?? '')),
                'direc'=>trim((string)($_REQUEST['direc'] ?? '')),
                'vende'=>(int)($_REQUEST['vende'] ?? 0),
                'almac'=>(int)($_REQUEST['almac'] ?? 0),
                'placa'=>(string)($_REQUEST['placa'] ?? ''),
                'fecha'=>(string)($_REQUEST['fecha'] ?? date('Y-m-d')),
                'detalles'=>$det,
                'obser'=>(string)($_REQUEST['obser'] ?? '')
            ]);

            echo json_encode(
                ['ok'=>true]+$r+['mensaje'=>'Borrador y reserva actualizados.'],
                JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
            );
            break;

        case 'latid':
            echo json_encode(
                ['ok'=>true]+$serv->latid((int)($_REQUEST['id'] ?? 0))+['mensaje'=>'Actividad de operación actualizada.'],
                JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
            );
            break;

        case 'vacia':
            $serv->vacia((int)($_REQUEST['id'] ?? 0));
            echo json_encode(
                ['ok'=>true,'mensaje'=>'Detalle de operación vaciado y reserva liberada.'],
                JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
            );
            break;

        case 'confirmar':
            $r = $serv->confirmar(
                (int)($_REQUEST['id'] ?? 0),
                (string)($_REQUEST['formp'] ?? '')
            );

            echo json_encode(
                ['ok'=>true,'docum'=>$r,'mensaje'=>'Operación confirmada.'],
                JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
            );
            break;

        case 'cancelar':
            $serv->cancelar((int)($_REQUEST['id'] ?? 0));
            echo json_encode(
                ['ok'=>true,'mensaje'=>'Operación cancelada y reserva liberada.'],
                JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
            );
            break;

        default:
            http_response_code(400);
            echo json_encode(
                ['ok'=>false,'mensaje'=>'Operación no válida.'],
                JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
            );
    }
} catch (Throwable $e) {
    http_response_code(
        $e instanceof InvalidArgumentException ? 400 : 500
    );

    echo json_encode(
        ['ok'=>false,'mensaje'=>errMsg($e)],
        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
    );
}