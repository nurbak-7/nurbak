<?php
/* ============================================================
   Ruta: modul/admin/permi/permi-contr.php
   Función: Controlador HTTP del módulo PERM.

   NIVEL 1: Operaciones propias de permisos.
   NIVEL 2: Usuarios con sus permisos.
   NIVEL 3: Asignación de permisos a un usuario.
   NIVEL FINAL: Entregar respuestas JSON.
============================================================ */

declare(strict_types=1);

/* Nivel 1 — Manejo normal de errores. */
require_once __DIR__ . '/../../../contr/error.php';

require_once dirname(__DIR__, 3) . '/contr/login/login-db.php';
require_once dirname(__DIR__, 3) . '/contr/login/contr-auth.php';
require_once dirname(__DIR__, 3) . '/contr/login/contr-perm.php';
require_once __DIR__ . '/permi-repos.php';
require_once __DIR__ . '/permi-servi.php';

final class PermCont
{
    public function __construct(private PermServ $serv) {}

    /* ========================================================
       NIVEL 1 — PERMISOS
    ======================================================== */
    public function listar(): array { return $this->serv->listar(); }
    public function buscar(string $texto): array { return $this->serv->buscar($texto); }
    public function porId(int $id): ?array { return $this->serv->porId($id); }
    public function crear(string $codig, string $nombr, ?string $desc): int { return $this->serv->crear($codig, $nombr, $desc); }
    public function actualizar(int $id, string $nombr, ?string $desc): bool { return $this->serv->actualizar($id, $nombr, $desc); }
    public function cambiarEstado(int $id, bool $activ): bool { return $this->serv->cambiarEstado($id, $activ); }
    public function permisosUsuario(int $usuaId): array { return $this->serv->permisosUsuario($usuaId); }
    public function tienePermiso(int $usuaId, string $codig): bool { return $this->serv->tienePermiso($usuaId, $codig); }

    /* ========================================================
       NIVEL 2 — USUARIOS CON PERMISOS
    ======================================================== */
    public function usuariosConPermisos(string $texto = ''): array
    {
        return $this->serv->usuariosConPermisos($texto);
    }

    /* ========================================================
       NIVEL 3 — GUARDAR ASIGNACIÓN
    ======================================================== */
    public function guardarAsignacion(int $usuaId, array $permIds): void
    {
        $this->serv->guardarAsignacion($usuaId, $permIds);
    }

    /* ========================================================
       NIVEL 4 — ELIMINAR ASIGNACIONES
       El controlador solo delega. No ejecuta SQL.
    ======================================================== */
    public function eliminarAsignacion(int $usuaId): array
    {
        return $this->serv->eliminarAsignacion($usuaId);
    }
}

/* ============================================================
   NIVEL HTTP — ENTRADA DE PERM
============================================================ */

header('Content-Type: application/json; charset=utf-8');

try {
    /* ========================================================
       NIVEL SEGURIDAD — SEGUNDA CAPA
       La interfaz oculta Administración para usuarios sin
       acceso total. El backend vuelve a comprobarlo aquí.
       El permiso PERMV no sustituye esta protección:
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


    $repo = new PermRepo($db);
    $serv = new PermServ($repo);
    $cont = new PermCont($serv);

    /* ========================================================
       NIVEL HTTP 1 — RESOLVER LA ACCIÓN
       POST tiene prioridad para mutaciones.
    ======================================================== */
    $accion = strtoupper(trim((string) (
        $_POST['accion'] ?? $_GET['accion'] ?? 'LISTAR'
    )));

    CAuth::metod($accion, ['ELIMINAR', 'GUARDAR']);

    switch ($accion) {
        case 'LISTAR':
            $texto = trim((string) ($_REQUEST['texto'] ?? ''));
            $usuarios = $cont->usuariosConPermisos($texto);
            
            echo json_encode(['ok' => true, 'usuarios' => $usuarios], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'PERMISOS':
            $permisos = $cont->listar();
            echo json_encode(['ok' => true, 'permisos' => $permisos], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'USUARIO_PERMISOS':
            $usuaId = (int) ($_REQUEST['usuar'] ?? 0);
            echo json_encode(['ok' => true, 'permisos' => $cont->permisosUsuario($usuaId)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        /* ====================================================
           NIVEL HTTP 4 — ELIMINAR ASIGNACIONES
           Entrada exclusiva por POST.
        ==================================================== */
        case 'ELIMINAR':
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                
                throw new InvalidArgumentException('ELIMINAR requiere solicitud POST.');
            }

            $usuaId = (int) ($_POST['usuar'] ?? 0);
            if ($usuaId <= 0) {
                throw new InvalidArgumentException('Usuario inválido.');
            }

            
            $resultado = $cont->eliminarAsignacion($usuaId);

            

            echo json_encode(
                ['ok' => true, 'mensaje' => 'Asignaciones de permisos eliminadas correctamente.'] + $resultado,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            break;

        case 'GUARDAR':
            $usuaId = (int) ($_POST['usuar'] ?? 0);
            if ($usuaId <= 0) throw new InvalidArgumentException('Usuario inválido.');

            $ids = $_POST['perm_ids'] ?? [];
            if (!is_array($ids)) $ids = [$ids];
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));

            $cont->guardarAsignacion($usuaId, $ids);
            
            echo json_encode(['ok' => true, 'mensaje' => 'Permisos guardados correctamente.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
