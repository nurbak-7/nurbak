<?php
/* ============================================================
   Ruta: modul/admin/usuar/usuar-contr.php
   Función: Recibir las solicitudes HTTP de USUA y coordinar
   las operaciones con USUA-SERV y SECU-SERV.

   🔹 Nivel 1: Recibir solicitudes de lectura.
   🔹 Nivel 2: Recibir creación y actualización.
   🔹 Nivel 3: Recibir suspensión y activación.
   🔹 Nivel 4: Recibir actualización de rol y datos.
   🔹 Nivel 5: Recibir eliminación física.
   🔹 Nivel 6: Solicitar códigos de previsualización.
   🔹 Nivel Final: Entregar respuestas JSON.
============================================================ */

declare(strict_types=1);

/* Nivel 1 — Manejo normal de errores. */
require_once __DIR__ . '/../../../contr/error.php';

final class UsuaCont
{
    public function __construct(
        private UsuaServ $serv,
        private SecuServ $secu
    ) {
    }

    public function listar(): array { return $this->serv->listar(); }
    public function buscar(string $texto): array { return $this->serv->buscar($texto); }
    public function porId(int $id): ?array { return $this->serv->porId($id); }
    public function roles(): array { return $this->serv->roles(); }
    public function solicitarDni(string $dni): array { return $this->serv->solicitarDni($dni); }
    public function crear(array $datos): array { return $this->serv->crear($datos); }
    public function actualizar(int $id, array $datos): array { return $this->serv->actualizar($id, $datos); }
    public function cambiarEstado(int $id, int $activ): array { return $this->serv->cambiarEstado($id, $activ); }
    public function eliminar(int $id): array { return $this->serv->eliminar($id); }
    public function siguienteAdministrador(): string { return $this->secu->siguienteAdministrador(); }
    public function siguienteOperador(): string { return $this->secu->siguienteOperador(); }
}

/* ============================================================
   NIVEL HTTP — ENTRADA DE USUA
============================================================ */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/contr/login/login-db.php';
require_once dirname(__DIR__, 3) . '/contr/login/contr-auth.php';
require_once dirname(__DIR__, 3) . '/contr/login/contr-perm.php';
require_once dirname(__DIR__) . '/secue/secue-repos.php';
require_once dirname(__DIR__) . '/secue/secue-servi.php';
require_once __DIR__ . '/usuar-repos.php';
require_once __DIR__ . '/usuar-servi.php';
require_once dirname(__DIR__, 2) . '/categ/venta/clien/exter-servi.php';

$accion = strtoupper(trim((string) ($_POST['accion'] ?? $_GET['accion'] ?? 'LISTAR')));

try {
    /* ========================================================
       NIVEL SEGURIDAD — SEGUNDA CAPA
       La interfaz oculta Administración para usuarios sin
       acceso total. El backend vuelve a comprobarlo aquí.
       El permiso USUAV no sustituye esta protección:
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

    CAuth::metod($accion, ['CREAR', 'ACTUALIZAR', 'ESTADO', 'ELIMINAR']);

    $repo = new UsuaRepo($db);
    $secuRepo = new SecuRepo($db);
    $secuServ = new SecuServ($secuRepo);
    $serv = new UsuaServ($repo, $secuServ, $db);
    $cont = new UsuaCont($serv, $secuServ);

    switch ($accion) {
        case 'LISTAR':
            $datos = $cont->listar();
            
            echo json_encode(['ok' => true, 'usuarios' => $datos], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'BUSCAR':
            $texto = trim((string) ($_REQUEST['texto'] ?? ''));
            $datos = $cont->buscar($texto);
            
            echo json_encode(['ok' => true, 'usuarios' => $datos], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'ROLES':
            $datos = $cont->roles();
            
            echo json_encode(['ok' => true, 'roles' => $datos], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'DATOS_DNI':
            $dni = preg_replace('/\D+/', '', (string) ($_REQUEST['dni'] ?? '')) ?? '';
            $resultado = $cont->solicitarDni($dni);
            
            echo json_encode([
                'ok' => true,
                'origen' => $resultado['origen'] ?? '',
                'encontrado' => true,
                'datos' => $resultado['datos'] ?? [],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'PORID':
            $id = (int) ($_REQUEST['id'] ?? 0);
            $dato = $cont->porId($id);
            if (!$dato) throw new InvalidArgumentException('Usuario no encontrado.');
            echo json_encode(['ok' => true, 'usuario' => $dato], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'CODIGO':
            $tipo = strtoupper(trim((string) ($_REQUEST['tipo'] ?? '')));
            $codig = match ($tipo) {
                'ADMINISTRADOR' => $cont->siguienteAdministrador(),
                'OPERADOR' => $cont->siguienteOperador(),
                default => throw new InvalidArgumentException('Tipo de código no válido.'),
            };
            
            echo json_encode(['ok' => true, 'tipo' => $tipo, 'codig' => $codig], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'CREAR':
            $resultado = $cont->crear($_POST);
            
            echo json_encode(['ok' => true, 'mensaje' => 'Usuario creado correctamente.', 'usuario' => $resultado], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'ACTUALIZAR':
            $id = (int) ($_POST['id'] ?? 0);
            $resultado = $cont->actualizar($id, $_POST);
            
            echo json_encode(['ok' => true, 'mensaje' => 'Usuario actualizado correctamente.', 'usuario' => $resultado], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'ESTADO':
            $id = (int) ($_POST['id'] ?? 0);
            $activ = (int) ($_POST['activ'] ?? -1);
            $resultado = $cont->cambiarEstado($id, $activ);
            
            echo json_encode(['ok' => true, 'mensaje' => $activ === 1 ? 'Usuario activado.' : 'Usuario suspendido.', 'usuario' => $resultado], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'ELIMINAR':
            $id = (int) ($_POST['id'] ?? 0);
            $resultado = $cont->eliminar($id);
            
            echo json_encode(['ok' => true, 'mensaje' => 'Usuario eliminado correctamente.', 'usuario' => $resultado], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'mensaje' => 'Operación no válida.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    
    echo json_encode(['ok' => false, 'mensaje' => errMsg($e)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (PDOException $e) {
    http_response_code(409);
    
    echo json_encode(['ok' => false, 'mensaje' => errMsg($e)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    
    echo json_encode(['ok' => false, 'mensaje' => errMsg($e)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
