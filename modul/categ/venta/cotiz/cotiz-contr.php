<?php
/* ============================================================
   Ruta: modul/categ/venta/cotiz/cotiz-contr.php
   Módulo: Ventas / Cotización / Controlador

   RESPONSABILIDAD:
   Recibir HTTP y delegar la lógica en CotizServ/CotizRepo.

   RELACIONES:
   - VENTADB: cotiz / dtall / cliente / numeración.
   - CATALDB: productos.
   - LOGINDB: vendedor.
   - Cliente externo: mismo servicio usado por Operaciones.
============================================================ */
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
    CAuth::metod($pact, ['guardar','cancelar']);

    /* AUTORIZACIÓN — Paso 4 | Permiso COTIG requerido en el servidor. */
    $contPerm = new ContPerm(LOGINDB::abrir());
    if (!$contPerm->autorizar('COTIG')) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Operación no autorizada.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }


require_once dirname(__DIR__,4) . '/contr/categ/venta-db.php';
require_once dirname(__DIR__,4) . '/contr/categ/catal-db.php';
require_once dirname(__DIR__) . '/clien/clien-repos.php';
require_once dirname(__DIR__) . '/clien/exter-servi.php';
require_once __DIR__ . '/cotiz-repos.php';
require_once __DIR__ . '/cotiz-servi.php';

$accion = trim((string)($_REQUEST['accion'] ?? 'opciones'));

try {
    $ventDb = VENTADB::abrir();
    $cataDb = CATALDB::abrir();
    $logiDb = LOGINDB::abrir();

    $repo = new CotizRepo($ventDb, $cataDb, $logiDb);
    $serv = new CotizServ($repo);
    $clie = new ClieRepo($ventDb);

    switch ($accion) {
        case 'listar':
            $buscar = trim((string)($_REQUEST['buscar'] ?? ''));
            $limite = (int)($_REQUEST['limite'] ?? 50);
            echo json_encode([
                'ok' => true,
                'cotizaciones' => $repo->listar($buscar, $limite)
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'obtener':
            $id = (int)($_REQUEST['id'] ?? 0);
            echo json_encode([
                'ok' => true,
                'datos' => $repo->obtener($id)
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'opciones':
            echo json_encode([
                'ok' => true,
                'numero' => $repo->numeracion()
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'buscar-cliente-local':
            $docum = preg_replace('/\D+/', '', (string)($_REQUEST['docum'] ?? ''));
            if (!in_array(strlen($docum), [8,11], true)) {
                throw new InvalidArgumentException('RUC/DNI no válido.');
            }
            $item = $repo->buscarCliente($docum);
            echo json_encode([
                'ok' => true,
                'encontrado' => (bool)$item,
                'clien' => $item
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'buscar-cliente-externo':
            $docum = preg_replace('/\D+/', '', (string)($_REQUEST['docum'] ?? ''));
            if (!in_array(strlen($docum), [8,11], true)) {
                throw new InvalidArgumentException('RUC/DNI no válido.');
            }
            echo json_encode(
                OperaClienteExterno::consultar($docum),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            break;

        case 'buscar-vendedor':
            $codig = strtoupper(trim((string)($_REQUEST['codig'] ?? '')));
            if (!preg_match('/^\d{3}$|^A\d{3}$/', $codig)) {
                throw new InvalidArgumentException('Código de vendedor no válido.');
            }
            $item = $repo->buscarVendedor($codig);
            echo json_encode([
                'ok' => true,
                'encontrado' => (bool)$item,
                'vendedor' => $item
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'buscar-productos':
            $buscar = trim((string)($_REQUEST['buscar'] ?? ''));
            $limite = (int)($_REQUEST['limite'] ?? 20);
            echo json_encode([
                'ok' => true,
                'productos' => $repo->buscarProductos($buscar, $limite)
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'guardar':
            $det = json_decode((string)($_REQUEST['detalles'] ?? '[]'), true);
            if (!is_array($det)) {
                throw new InvalidArgumentException('Detalle de cotización no válido.');
            }

            $docum = preg_replace('/\D+/', '', (string)($_REQUEST['docum'] ?? ''));
            $clienId = (int)($_REQUEST['clien'] ?? 0);

            if ($docum === '') {
                $clienId = $repo->vario();
            } elseif ($clienId <= 0) {
                $clienId = $clie->resolverOperacion([
                    'id' => 0,
                    'tipod' => strlen($docum) === 11 ? 'RUC' : 'DNI',
                    'docum' => $docum,
                    'razon' => trim((string)($_REQUEST['razon'] ?? '')),
                    'direc' => trim((string)($_REQUEST['direc'] ?? ''))
                ]);
            }

            $r = $serv->guardar(
                (int)($_REQUEST['id'] ?? 0),
                $clienId,
                (int)($_REQUEST['vende'] ?? 0),
                trim((string)($_REQUEST['placa'] ?? '')),
                trim((string)($_REQUEST['fecha'] ?? '')),
                trim((string)($_REQUEST['vigia'] ?? '')),
                trim((string)($_REQUEST['obser'] ?? '')),
                $det
            );

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Cotización guardada correctamente.',
                'cotiz' => $r
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'cancelar':
            $id = (int)($_REQUEST['id'] ?? 0);
            if ($id > 0) {
                $serv->cancelar($id);
            }
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Cotización cancelada correctamente.'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Acción de Cotización no válida.'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
} catch (Throwable $e) {
    http_response_code($e instanceof InvalidArgumentException ? 400 : 500);
    echo json_encode([
        'ok' => false,
        'mensaje' => errMsg($e)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
