<?php

/* ============================================================
   Ruta: modul/categ/venta/nvent/nvent-contr.php
   Módulo: Ventas / Notas de venta

   RESPONSABILIDAD:
   Consultar y registrar notas de venta comerciales.
============================================================ */

declare(strict_types=1);
/* ============================================================
   NIVEL 1 — CONTROLADOR
============================================================ */


/* ============================================================
   NIVEL 2 — CONFIGURACIÓN
============================================================ */

// Manejo normal de errores.
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
    /* AUTORIZACIÓN — Paso 4 | Permiso NOVEV requerido en el servidor. */
    $contPerm = new ContPerm(LOGINDB::abrir());
    if (!$contPerm->autorizar('NOVEV')) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Operación no autorizada.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }


require_once dirname(__DIR__, 4) . '/contr/categ/venta-db.php';
require_once dirname(__DIR__, 4) . '/contr/categ/catal-db.php';
require_once dirname(__DIR__, 4) . '/contr/categ/inven-db.php';
require_once dirname(__DIR__, 4) . '/modul/categ/catal/produ/produ-repos.php';
require_once dirname(__DIR__, 4) . '/modul/categ/inven/almac/almac-repos.php';
require_once dirname(__DIR__, 4) . '/modul/categ/inven/reser/reser-repos.php';
require_once __DIR__ . '/nvent-repos.php';
require_once dirname(__DIR__, 4) . '/modul/categ/venta/clien/clien-repos.php';
require_once dirname(__DIR__, 4) . '/modul/categ/venta/clien/exter-servi.php';
require_once dirname(__DIR__, 4) . '/modul/admin/usuar/usuar-repos.php';
require_once dirname(__DIR__, 4) . '/modul/categ/inven/stock/stock-repos.php';


/* ============================================================
   NIVEL 2 — ENTRADA
============================================================ */

$accion = trim(
    (string)($_REQUEST['accion'] ?? 'listar')
);

$buscar = trim(
    (string)($_REQUEST['buscar'] ?? '')
);

$limite = (int)(
    $_REQUEST['limite'] ?? 50
);


/* ============================================================
   NIVEL 2 — PROCESO
============================================================ */

try {

    /* ========================================================
       NIVEL 3 — REPOSITORIO
    ======================================================== */

    /* VENTA es la única conexión necesaria para listar y numerar
       Nota de Venta. CATÁLOGO, INVENTARIO y LOGIN se abren únicamente
       en las acciones que son responsables de utilizarlos. */
    $ventDb = VENTADB::abrir();
    $repo = new NvenRepo($ventDb);


    /* ========================================================
       NIVEL 3 — ACCIÓN
    ======================================================== */

    switch ($accion) {

        case 'opciones':
            $inveDb = INVENDB::abrir();
            $almaRepo = new AlmaRepo($inveDb);
            $almacenes = $almaRepo->listar(0, '', 100);
            $almacenes = array_values(array_filter($almacenes, static fn(array $a): bool => (int)($a['estad'] ?? 0) === 1));
            echo json_encode(['ok'=>true,'almacenes'=>$almacenes], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            break;

        case 'numeracion':
            echo json_encode(['ok'=>true,'numeracion'=>$repo->numeracion()], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            break;

        case 'buscar-cliente-local':
            $clieRepo = new ClieRepo($ventDb);
            $docum = preg_replace('/\D+/', '', (string)($_REQUEST['docum'] ?? ''));
            if (!in_array(strlen($docum), [8,11], true)) throw new InvalidArgumentException('Ingrese un DNI de 8 dígitos o un RUC de 11 dígitos.');
            $clien = $clieRepo->porDocum($docum);
            echo json_encode(['ok'=>true,'encontrado'=>(bool)$clien,'clien'=>$clien], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            break;

        case 'buscar-cliente-externo':
            $docum = preg_replace('/\D+/', '', (string)($_REQUEST['docum'] ?? ''));
            if (!in_array(strlen($docum), [8,11], true)) throw new InvalidArgumentException('Ingrese un DNI de 8 dígitos o un RUC de 11 dígitos.');
            echo json_encode(OperaClienteExterno::consultar($docum), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            break;

        case 'buscar-vendedor':
            $usuaRepo = new UsuaRepo(LOGINDB::abrir());
            $codig = strtoupper(preg_replace('/\s+/', '', (string)($_REQUEST['codig'] ?? '')));
            if (!preg_match('/^\d{3}$|^A\d{3}$/', $codig)) throw new InvalidArgumentException('Código de vendedor no válido.');
            $vendedor = $usuaRepo->porOperc($codig);
            echo json_encode(['ok'=>true,'encontrado'=>(bool)$vendedor,'vendedor'=>$vendedor], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            break;

        case 'buscar-productos':
            $cataDb = CATALDB::abrir();
            $prodRepo = new ProdRepo($cataDb);
            $inveDb = INVENDB::abrir();
            $almaRepo = new AlmaRepo($inveDb);
            $reseRepo = new ReseR($inveDb);
            $almacId = (int)($_REQUEST['almac'] ?? 0);
            $alma = $almaRepo->porId($almacId);
            if (!$alma || (int)$alma['estad'] !== 1) {
                throw new InvalidArgumentException('Seleccione un almacén activo.');
            }
            $productos = $prodRepo->buscarOpera($buscar, max(1, min($limite, 50)));
            $ids = array_values(array_unique(array_map(static fn(array $p): int => (int)$p['id'], $productos)));
            $reseRepo->expirar();
            $estados = $reseRepo->estado($almacId, $ids);
            $mapa = [];
            foreach ($estados as $estado) $mapa[(int)$estado['produ']] = $estado;
            foreach ($productos as &$producto) {
                $estado = $mapa[(int)$producto['id']] ?? ['canti'=>0,'reser'=>0,'dispo'=>0];
                $producto['canti'] = (float)$estado['canti'];
                $producto['reser'] = (float)$estado['reser'];
                $producto['dispo'] = (float)$estado['dispo'];
                $producto['preci'] = (float)($producto['pvent'] ?? 0);
                unset($producto['pvent']);
            }
            unset($producto);
            echo json_encode(['ok'=>true,'productos'=>$productos], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            break;

        case 'obtener':
            $idNota=(int)($_REQUEST['id']??0);
            $nota=$repo->obtener($idNota);
            $cataDb=CATALDB::abrir();$prodRepo=new ProdRepo($cataDb);
            foreach($nota['detalles'] as &$det){$prod=$prodRepo->obtener((int)$det['produ']);$det['codig']=(string)($prod['codig']??'');$det['descr']=(string)($prod['descr']??'');}
            unset($det);
            $usuaRepo=new UsuaRepo(LOGINDB::abrir());$vend=$usuaRepo->porId((int)($nota['vende']??0));
            $nota['vende_cod']=(string)($vend['oper_codi']??$vend['operc']??'');
            $nota['vende_nom']=trim((string)($vend['nombr']??'').' '.(string)($vend['apell']??''));
            echo json_encode(['ok'=>true,'nota'=>$nota],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            break;

        case 'emitir':
        case 'actualizar':
            $esEdicion = $accion === 'actualizar';
            $notaId = $esEdicion ? (int)($_REQUEST['id'] ?? 0) : 0;
            $notaActual = $esEdicion ? $repo->obtener($notaId) : null;
            if ($esEdicion && (($notaActual['estad'] ?? '') !== 'emitida')) throw new InvalidArgumentException('Solo se puede editar una Nota de Venta emitida.');
            $clieRepo = new ClieRepo($ventDb);
            $cataDb = CATALDB::abrir();
            $prodRepo = new ProdRepo($cataDb);
            $inveDb = INVENDB::abrir();
            $almaRepo = new AlmaRepo($inveDb);
            $reseRepo = new ReseR($inveDb);
            $stocRepo = new StocRepo($inveDb, $cataDb);
            $usuaRepo = new UsuaRepo(LOGINDB::abrir());
            $docum = preg_replace('/\D+/', '', (string)($_REQUEST['docum'] ?? ''));
            if (!in_array(strlen($docum), [8,11], true)) throw new InvalidArgumentException('Ingrese un DNI de 8 dígitos o un RUC de 11 dígitos.');
            $tipod = strlen($docum) === 11 ? 'RUC' : 'DNI';
            $razon = trim((string)($_REQUEST['razon'] ?? ''));
            $direc = trim((string)($_REQUEST['direc'] ?? ''));
            if ($razon === '') throw new InvalidArgumentException('El nombre o razón social del comprador es obligatorio.');

            $detRaw = json_decode((string)($_REQUEST['detalles'] ?? '[]'), true);
            if (!is_array($detRaw) || $detRaw === []) throw new InvalidArgumentException('Agregue al menos un producto.');
            $detalles = [];
            $cantPorProd = [];
            foreach ($detRaw as $fila) {
                if (!is_array($fila)) throw new InvalidArgumentException('Detalle de producto no válido.');
                $produ = (int)($fila['produ'] ?? 0);
                $canti = (float)($fila['canti'] ?? 0);
                $preci = (float)($fila['preci'] ?? -1);
                if ($produ <= 0 || !is_finite($canti) || $canti <= 0 || !is_finite($preci) || $preci < 0) throw new InvalidArgumentException('Cantidad o precio de producto no válido.');
                $prodRepo->obtener($produ);
                $detalles[] = ['produ'=>$produ,'canti'=>$canti,'preci'=>$preci];
                $cantPorProd[$produ] = ($cantPorProd[$produ] ?? 0.0) + $canti;
            }

            $almacId = (int)($_REQUEST['almac'] ?? 0);
            if ($esEdicion && (int)($notaActual['almac'] ?? 0) !== $almacId) throw new InvalidArgumentException('No se puede cambiar el almacén de una Nota de Venta emitida.');
            $alma = $almaRepo->porId($almacId);
            if (!$alma || (int)($alma['estad'] ?? 0) !== 1) throw new InvalidArgumentException('Seleccione un almacén activo.');

            $vendeCod = strtoupper(preg_replace('/\s+/', '', (string)($_REQUEST['codig'] ?? '')));
            if (!preg_match('/^\d{3}$|^A\d{3}$/', $vendeCod)) throw new InvalidArgumentException('Identifique al vendedor mediante su código.');
            $vende = $usuaRepo->porOperc($vendeCod);
            if (!$vende) throw new InvalidArgumentException('El vendedor no existe o está inactivo.');
            $vendeId = (int)$vende['id'];
            $vendePost = (int)($_REQUEST['vende'] ?? 0);
            if ($vendePost > 0 && $vendePost !== $vendeId) throw new InvalidArgumentException('El vendedor cambió; vuelva a identificarlo.');

            $placa = strtoupper(trim((string)($_REQUEST['placa'] ?? '')));
            if ($placa !== '' && !preg_match('/^[A-Z0-9]{3}-[A-Z0-9]{3}$/', $placa)) throw new InvalidArgumentException('La placa debe tener el formato SSS-SSS.');
            $formp = strtoupper(trim((string)($_REQUEST['formp'] ?? 'CONTADO')));
            if (!in_array($formp, ['CONTADO','CREDITO'], true)) throw new InvalidArgumentException('La forma de pago no es válida.');
            $medi = trim((string)($_REQUEST['medi'] ?? ''));
            $fecha = trim((string)($_REQUEST['fecha'] ?? date('Y-m-d')));
            $obser = trim((string)($_REQUEST['obser'] ?? ''));

            /* Validación final de stock/disponibilidad, dentro de la
               transacción de INVENTARIO. La pantalla MINI solo informa. */
            $inveDb->beginTransaction();
            try {
                $ids = array_map('intval', array_keys($cantPorProd));
                $marcadores = implode(',', array_fill(0, count($ids), '?'));
                if ($ids !== []) {
                    $bloqueo = $inveDb->prepare("SELECT id FROM stock WHERE almac=? AND produ IN ({$marcadores}) FOR UPDATE");
                    $bloqueo->execute(array_merge([$almacId], $ids));
                }
                if ($esEdicion) {
                    $movAnt = $stocRepo->compensarNota($notaId);
                    $movCant = [];
                    foreach ($movAnt as $mv) {
                        if ((int)$mv['almac'] !== (int)$notaActual['almac']) throw new RuntimeException('La trazabilidad de inventario no coincide con el almacén de la nota.');
                        $pid=(int)$mv['produ']; $movCant[$pid]=($movCant[$pid]??0.0)+(float)$mv['canti'];
                    }
                    $detCant = [];
                    foreach (($notaActual['detalles']??[]) as $detOld) { $pid=(int)$detOld['produ']; $detCant[$pid]=($detCant[$pid]??0.0)+(float)$detOld['canti']; }
                    ksort($movCant); ksort($detCant);
                    if (array_keys($movCant)!==array_keys($detCant)) throw new RuntimeException('Los movimientos trazables no coinciden con los productos de la nota; no se modificó el documento.');
                    foreach ($detCant as $pid=>$cantOld) if (abs($cantOld-($movCant[$pid]??0.0))>0.0001) throw new RuntimeException('Las cantidades de los movimientos no coinciden con el detalle de la nota; no se modificó el documento.');
                }
                $reseRepo->expirar();
                $estados = $reseRepo->estado($almacId, $ids);
                $estadoMap = [];
                foreach ($estados as $estado) $estadoMap[(int)$estado['produ']] = $estado;
                foreach ($cantPorProd as $produ => $cantidad) {
                    $estado = $estadoMap[(int)$produ] ?? ['canti'=>0,'reser'=>0,'dispo'=>0];
                    $fisico = (float)$estado['canti'];
                    $dispo = (float)$estado['dispo'];
                    if ($fisico > 0 && $cantidad > $dispo + 0.0001) {
                        throw new InvalidArgumentException('La cantidad solicitada no está disponible en el almacén; el stock está reservado o comprometido por otra operación.');
                    }
                }

                $clienId = (int)($_REQUEST['clien'] ?? 0);
                if ($clienId > 0) {
                    $clienActual = $clieRepo->porId($clienId);
                    $docActual = preg_replace('/\D+/', '', (string)($clienActual['docum'] ?? ''));
                    if (!$clienActual || (int)($clienActual['estad'] ?? 0) !== 1 || $docActual !== $docum) $clienId = 0;
                }
                $clienId = $clieRepo->resolverOperacion([
                    'id'=>$clienId,
                    'tipod'=>$tipod,
                    'docum'=>$docum,
                    'razon'=>$razon,
                    'direc'=>$direc
                ]);

                /* Nota de Venta no crea ni confirma una operación.
                   Se validan y registran sus movimientos propios directamente
                   en INVEN, y después se persiste el documento interno. */
                $movIds = [];
                foreach ($cantPorProd as $produ => $cantidad) {
                    $estado = $estadoMap[(int)$produ] ?? ['canti'=>0,'dispo'=>0];
                    if ((float)$estado['canti'] > 0) {
                        $movIds[] = $stocRepo->salida((int)$produ, $almacId, (float)$cantidad, $esEdicion ? 'Salida por edición de Nota de Venta' : 'Salida por Nota de Venta interna');
                    } else {
                        $movIds[] = $stocRepo->salss((int)$produ, $almacId, (float)$cantidad, $esEdicion ? 'Salida sin stock por edición de Nota de Venta' : 'Salida sin stock por Nota de Venta interna');
                    }
                }

                if ($esEdicion) {
                    $resultado = $repo->actualizar($notaId, $clienId, ['tipod'=>$tipod,'docum'=>$docum,'razon'=>$razon,'direc'=>$direc], $detalles, $almacId, $vendeId, $placa, $fecha, $formp, $medi, $obser);
                } else {
                    $resultado = $repo->emitir(null, $clienId, ['tipod'=>$tipod,'docum'=>$docum,'razon'=>$razon,'direc'=>$direc], $detalles, $almacId, $vendeId, $placa, $fecha, $formp, $medi, $obser);
                }
                $stocRepo->vincular($movIds, 'nvent', (int)($resultado['documento_id'] ?? $notaId));
                $inveDb->commit();
            } catch (Throwable $e) {
                if ($inveDb->inTransaction()) $inveDb->rollBack();
                throw $e;
            }

            echo json_encode(['ok'=>true,'mensaje'=>$esEdicion?'Nota de Venta actualizada correctamente.':'Nota de Venta emitida correctamente.','nota'=>$resultado], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            break;

        /* ====================================================
           NIVEL 4 — LISTAR
        ==================================================== */

        case 'listar':

            $r = $repo->listar(
                $buscar,
                $limite
            );

            echo json_encode(
                [
                    'ok'    => true,
                    'notas' => $r
                ],
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            );

            break;


        /* ====================================================
           NIVEL 4 — ACCIÓN NO VÁLIDA
        ==================================================== */

        default:

            http_response_code(400);

            echo json_encode(
                [
                    'ok'      => false,
                    'mensaje' => 'Operación no válida.'
                ],
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            );

            break;
    }


/* ============================================================
   NIVEL 2 — ERROR
============================================================ */

} catch (Throwable $e) {

    http_response_code($e instanceof InvalidArgumentException ? 400 : 500);

    echo json_encode(
        [
            'ok'      => false,
            'mensaje' => errMsg($e)
        ],
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );
}