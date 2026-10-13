<?php
/* ============================================================
   Ruta: modul/admin/tiend/tiend-contr.php
   Módulo: Administración / Tiendas
   Función: Recibir solicitudes HTTP de TIEND y coordinar
   Controller → Service → Repository.

   CADENA:
   TIEND JS
       ↓
   TIEND CONT
       ↓
   TIEND SERV
       ↓
   TIEND REPO
       ↓
   LOGINDB
       ↓
   BD logi

   🔹 Nivel 1: Cargar conexión y dependencias.
   🔹 Nivel 2: Resolver acción.
   🔹 Nivel 3: Ejecutar consulta.
   🔹 Nivel 4: Ejecutar mutación.
   🔹 Nivel Final: Entregar JSON.
============================================================ */

declare(strict_types=1);

/* Nivel 1 — Manejo normal de errores. */
require_once __DIR__ . '/../../../contr/error.php';

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/contr/login/login-db.php';
require_once dirname(__DIR__, 3) . '/contr/login/contr-auth.php';
require_once dirname(__DIR__, 3) . '/contr/login/contr-perm.php';
require_once dirname(__DIR__, 3) . '/contr/categ/inven-db.php';
require_once dirname(__DIR__, 3) . '/modul/categ/inven/almac/almac-repos.php';
require_once dirname(__DIR__, 3) . '/modul/categ/inven/almac/almac-servi.php';
require_once __DIR__ . '/tiend-repos.php';
require_once __DIR__ . '/tiend-servi.php';

final class TiendCont
{
    public function __construct(private TiendServ $serv)
    {
    }

    public function listar(): array { return $this->serv->listar(); }
    public function buscar(string $texto): array { return $this->serv->buscar($texto); }
    public function porId(int $id): ?array { return $this->serv->porId($id); }
    public function siguienteCodigo(): string { return $this->serv->siguienteCodigo(); }
    public function crear(array $datos): array { return $this->serv->crear($datos); }
    public function actualizar(int $id, array $datos): array { return $this->serv->actualizar($id, $datos); }
    public function usuarios(int $tiendId): array { return $this->serv->usuarios($tiendId); }
    public function usuariosDisponibles(int $tiendId, string $texto = ''): array { return $this->serv->usuariosDisponibles($tiendId, $texto); }
    public function asignarUsuario(int $tiendId, int $usuaId, int $esResp): array { return $this->serv->asignarUsuario($tiendId, $usuaId, $esResp); }
    public function responsable(int $tiendId, int $usuaId): array { return $this->serv->responsable($tiendId, $usuaId); }
    public function quitarUsuario(int $tiendId, int $usuaId): array { return $this->serv->quitarUsuario($tiendId, $usuaId); }
    public function cambiarEstado(int $id, int $activ): array { return $this->serv->cambiarEstado($id, $activ); }
    public function eliminar(int $id): array { return $this->serv->eliminar($id); }
    public function almas(int $id): array { return $this->serv->almas($id); }
    public function alcre(int $id, array $datos): array { return $this->serv->alcre($id, $datos); }
    public function alest(int $id, int $estad): array { return $this->serv->alest($id, $estad); }
    public function alpri(int $id): array { return $this->serv->alpri($id); }
}

/* ============================================================
   NIVEL HTTP — ENTRADA
============================================================ */

try {
    /* ========================================================
       NIVEL SEGURIDAD — SEGUNDA CAPA
       La interfaz oculta Administración para usuarios sin
       acceso total. El backend vuelve a comprobarlo aquí.
       El permiso TIENV no sustituye esta protección:
       Administración pertenece al acceso total del sistema.
    ======================================================== */

    CAuth::iniciar();

    if (!CAuth::autenticado()) {
        http_response_code(401);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Sesión no autenticada.'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $db = LOGINDB::abrir();
    $contPerm = new ContPerm($db);

    if (!$contPerm->tieneAccesoTotal()) {
        http_response_code(403);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Acceso administrativo no autorizado.'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }


    $repo = new TiendRepo($db);
    $inv = INVENDB::abrir();
    $alma = new AlmaServ(new AlmaRepo($inv), $inv, $db);
    $serv = new TiendServ($repo, $db, $alma);
    $cont = new TiendCont($serv);

    $accion = strtoupper(trim((string) (
        $_POST['accion'] ?? $_GET['accion'] ?? 'LISTAR'
    )));


    switch ($accion) {

        case 'LISTAR':
            $datos = $cont->listar();
            echo json_encode([
                'ok' => true,
                'tiendas' => $datos
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'BUSCAR':
            $texto = trim((string) ($_REQUEST['texto'] ?? ''));
            $datos = $cont->buscar($texto);
            echo json_encode([
                'ok' => true,
                'tiendas' => $datos
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'PORID':
            $id = (int) ($_REQUEST['id'] ?? 0);
            $dato = $cont->porId($id);

            if (!$dato) {
                throw new InvalidArgumentException('Tienda no encontrada.');
            }

            echo json_encode([
                'ok' => true,
                'tienda' => $dato
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'CODIGO':
            echo json_encode([
                'ok' => true,
                'codig' => $cont->siguienteCodigo()
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'USUARIOS':
            $tiendId = (int) ($_REQUEST['tiend'] ?? 0);
            echo json_encode([
                'ok' => true,
                'usuarios' => $cont->usuarios($tiendId)
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'USUARIOS_DISPONIBLES':
            $tiendId = (int) ($_REQUEST['tiend'] ?? 0);
            $texto = trim((string) ($_REQUEST['texto'] ?? ''));
            echo json_encode([
                'ok' => true,
                'usuarios' => $cont->usuariosDisponibles($tiendId, $texto)
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'CREAR':
            requerirPost();
            $dato = $cont->crear($_POST);
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Tienda creada correctamente.',
                'tienda' => $dato
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'ACTUALIZAR':
            requerirPost();
            $id = (int) ($_POST['id'] ?? 0);
            $dato = $cont->actualizar($id, $_POST);
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Tienda actualizada correctamente.',
                'tienda' => $dato
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'ASIGNAR':
            requerirPost();
            $tiendId = (int) ($_POST['tiend'] ?? 0);
            $usuaId = (int) ($_POST['usuar'] ?? 0);
            $esResp = (int) ($_POST['respo'] ?? 0);

            $dato = $cont->asignarUsuario($tiendId, $usuaId, $esResp);

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Usuario asignado correctamente.',
                'resultado' => $dato
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'RESPONSABLE':
            requerirPost();
            $tiendId = (int) ($_POST['tiend'] ?? 0);
            $usuaId = (int) ($_POST['usuar'] ?? 0);

            $dato = $cont->responsable($tiendId, $usuaId);

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Responsable actualizado correctamente.',
                'resultado' => $dato
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'QUITAR':
            requerirPost();
            $tiendId = (int) ($_POST['tiend'] ?? 0);
            $usuaId = (int) ($_POST['usuar'] ?? 0);

            $dato = $cont->quitarUsuario($tiendId, $usuaId);

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Usuario retirado correctamente.',
                'tienda' => $dato
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'ESTADO':
            requerirPost();
            $id = (int) ($_POST['id'] ?? 0);
            $activ = (int) ($_POST['activ'] ?? -1);

            $dato = $cont->cambiarEstado($id, $activ);

            echo json_encode([
                'ok' => true,
                'mensaje' => $activ === 1 ? 'Tienda activada correctamente.' : 'Tienda inactivada correctamente.',
                'tienda' => $dato
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'ELIMINAR':
            requerirPost();
            $id = (int) ($_POST['id'] ?? 0);
            $dato = $cont->eliminar($id);

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Tienda eliminada correctamente.',
                'tienda' => $dato
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'ALMAS':
            $id = (int) ($_REQUEST['tiend'] ?? 0);
            echo json_encode([
                'ok' => true,
                'almacenes' => $cont->almas($id)
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'ALCRE':
            requerirPost();
            $id = (int) ($_POST['tiend'] ?? 0);
            $dato = $cont->alcre($id, $_POST);
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Almacén creado correctamente.',
                'almacen' => $dato
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'ALEST':
            requerirPost();
            $id = (int) ($_POST['id'] ?? 0);
            $estad = (int) ($_POST['estad'] ?? -1);
            $dato = $cont->alest($id, $estad);
            echo json_encode([
                'ok' => true,
                'mensaje' => $estad === 1 ? 'Almacén activado correctamente.' : 'Almacén inactivado correctamente.',
                'almacen' => $dato
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'ALPRI':
            requerirPost();
            $id = (int) ($_POST['id'] ?? 0);
            $dato = $cont->alpri($id);
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Almacén principal actualizado correctamente.',
                'almacen' => $dato
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Operación no válida.'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'mensaje' => errMsg($e)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(409);
    echo json_encode([
        'ok' => false,
        'mensaje' => errMsg($e)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => errMsg($e)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function requerirPost(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        throw new InvalidArgumentException('Esta operación requiere solicitud POST.');
    }
}
