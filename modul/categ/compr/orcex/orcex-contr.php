<?php
/* ============================================================
   Ruta: modul/categ/compr/orcex/orcex-contr.php
   Módulo: Compras / Orden Compra Ext
   Función: Controlador HTTP.
============================================================ */
declare(strict_types=1);

require_once __DIR__ . '/../../../../contr/error.php';
$base = dirname(__DIR__,4);
require_once $base . '/contr/categ/compr-db.php';
require_once $base . '/contr/categ/catal-db.php';
require_once $base . '/modul/categ/venta/clien/exter-servi.php';
require_once $base . '/modul/admin/usuar/usuar-repos.php';
require_once $base . '/modul/categ/catal/produ/produ-repos.php';
require_once $base . '/modul/categ/compr/prove/prove-repos.php';
require_once __DIR__ . '/orcex-repos.php';

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
    $pcod = 'ORCEV';
    if (in_array($pact, ['agreg', 'esta', 'ruc'], true)) {
        $pcod = 'ORCEG';
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


    CAuth::metod($pact, ['agreg','esta','ruc']);

$acci = trim((string)($_REQUEST['acci'] ?? 'lista'));

try {
    $compr = COMPRDB::abrir();
    $cata = CATALDB::abrir();
    $logi = LOGINDB::abrir();

    $repo = new Orcex(
        $compr,
        $cata,
        new UsuaRepo($logi),
        new ProdRepo($cata),
        new ProvRepo($compr)
    );

    if ($acci === 'opciones') {
        echo json_encode([
            'ok' => true,
            'numero' => $repo->numeracion()
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($acci === 'lista') {
        echo json_encode([
            'ok' => true,
            'orcex' => $repo->lista(
                trim((string)($_REQUEST['busc'] ?? '')),
                (int)($_REQUEST['limi'] ?? 50)
            )
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($acci === 'prod') {
        echo json_encode([
            'ok' => true,
            'produ' => $repo->buscarProd(trim((string)($_REQUEST['busc'] ?? '')), (int)($_REQUEST['limi'] ?? 20))
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($acci === 'ruc') {
        $ruc = preg_replace('/\D+/', '', (string)($_REQUEST['ruc'] ?? ''));
        if (strlen($ruc) !== 11) {
            throw new InvalidArgumentException('RUC no válido.');
        }

        $local = $repo->porRuc($ruc);
        if ($local) {
            echo json_encode([
                'ok' => true,
                'fuente' => 'local',
                'prove' => $local
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $ext = OperaClienteExterno::consultar($ruc);
        if (!$ext['ok'] || !$ext['encontrado'] || empty($ext['clien'])) {
            throw new InvalidArgumentException($ext['mensaje'] ?? 'Proveedor no encontrado.');
        }

        $clien = $ext['clien'];
        $prove = $repo->proveer(
            $ruc,
            (string)($clien['razon_social'] ?? ''),
            (string)($clien['direccion'] ?? '')
        );

        echo json_encode([
            'ok' => true,
            'fuente' => $prove['nuevo'] ? 'externa' : 'local',
            'nuevo' => $prove['nuevo'],
            'prove' => $prove['prove']
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($acci === 'buscar-emisor') {
        $codig = strtoupper(trim((string)($_REQUEST['codig'] ?? '')));
        if (!preg_match('/^\d{3}$|^A\d{3}$/', $codig)) {
            throw new InvalidArgumentException('Código de usuario no válido.');
        }
        $item = $repo->emisor($codig);
        echo json_encode([
            'ok' => true,
            'encontrado' => (bool)$item,
            'emisor' => $item
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($acci === 'agreg') {
        $r = $repo->crear(
            ($_REQUEST['prove'] ?? '') !== '' ? (int)$_REQUEST['prove'] : null,
            trim((string)($_REQUEST['razon'] ?? '')),
            trim((string)($_REQUEST['ruc'] ?? '')),
            trim((string)($_REQUEST['direc'] ?? '')),
            trim((string)($_REQUEST['emiso'] ?? '')),
            trim((string)($_REQUEST['fecha'] ?? '')),
            json_decode((string)($_REQUEST['detox'] ?? '[]'), true)
        );

        echo json_encode([
            'ok' => true,
            'id' => $r['id'],
            'nume' => $r['nume'],
            'prove' => $r['prove'],
            'nuevo' => $r['nuevo'],
            'mensaje' => 'Orden de compra registrada.'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($acci === 'esta') {
        $id = (int)($_REQUEST['id'] ?? 0);
        $estad = trim((string)($_REQUEST['estad'] ?? ''));
        $repo->estad($id, $estad);

        echo json_encode([
            'ok' => true,
            'id' => $id,
            'estad' => $estad,
            'mensaje' => 'Estado actualizado.'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Operación no válida.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code($e instanceof InvalidArgumentException ? 400 : 500);
    echo json_encode([
        'ok' => false,
        'mensaje' => errMsg($e)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
