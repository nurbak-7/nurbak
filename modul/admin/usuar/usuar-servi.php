<?php
/* ============================================================
   Ruta: modul/admin/usuar/usuar-servi.php
   Función: Aplicar las reglas de negocio de USUA.

   🔹 Nivel 1: Consultar usuarios y roles.
   🔹 Nivel 2: Validar datos de creación.
   🔹 Nivel 3: Reservar código según el rol.
   🔹 Nivel 4: Crear usuario dentro de transacción.
   🔹 Nivel 5: Actualizar datos sin cambiar identidad ni rol.
   🔹 Nivel 6: Suspender o activar usuario.
   🔹 Nivel 7: Eliminar usuario físicamente.
   🔹 Nivel Final: Entregar el resultado de la operación.
============================================================ */

declare(strict_types=1);

final class UsuaServ
{
    private const LONGITUD_MIN_CLAVE = 4;
    private const LONGITUD_MAX_CLAVE = 15;

    public function __construct(
        private UsuaRepo $repo,
        private SecuServ $secu,
        private PDO $db
    ) {
    }

    public function listar(): array { return $this->repo->listar(); }
    public function buscar(string $texto): array { return $this->repo->buscar(trim($texto)); }
    public function porId(int $id): ?array { return $id > 0 ? $this->repo->porId($id) : null; }
    public function roles(): array { return $this->repo->roles(); }

    /* ========================================================
       NIVEL 1.1 — SOLICITAR DATOS POR DNI
       Primero consulta USUA local. Si no existe, consulta
       la fuente externa y devuelve nombres/apellidos.
       ======================================================== */
    public function solicitarDni(string $dni): array
    {
        $dni = preg_replace('/\D+/', '', $dni) ?? '';

        if (!preg_match('/^\d{8}$/', $dni)) {
            throw new InvalidArgumentException('El DNI debe tener 8 dígitos.');
        }

        $local = $this->repo->buscarPorDni($dni);

        if ($local) {
            return [
                'origen' => 'LOCAL',
                'encontrado' => true,
                'datos' => $local,
            ];
        }

        $externo = OperClienteExterno::consultar($dni);

        if (!($externo['ok'] ?? false) || !($externo['encontrado'] ?? false)) {
            throw new InvalidArgumentException(
                $externo['mensaje'] ?? 'No se encontraron datos para el DNI.'
            );
        }

        $cliente = $externo['cliente'] ?? [];
        $nombres = trim((string) ($cliente['nombres'] ?? ''));
        $apellidos = trim((string) ($cliente['apellidos'] ?? ''));

        if ($nombres === '' || $apellidos === '') {
            throw new InvalidArgumentException('La fuente externa no devolvió nombres y apellidos completos.');
        }

        return [
            'origen' => 'EXTERNO',
            'encontrado' => true,
            'datos' => [
                'nombr' => $nombres,
                'apell' => $apellidos,
                'dni' => $dni,
                'celul' => '',
                'usua_nomb' => $nombres,
                'usua_apel' => $apellidos,
                'usua_dni' => $dni,
                'usua_celu' => '',
            ],
        ];
    }

    /* ========================================================
       NIVEL 1 — VALIDAR CLAVE
       ======================================================== */
    private function validarClave(string $clave): bool
    {
        $longitud = strlen($clave);
        return $longitud >= self::LONGITUD_MIN_CLAVE && $longitud <= self::LONGITUD_MAX_CLAVE;
    }

    public function prepararClave(string $clave): ?string
    {
        if (!$this->validarClave($clave)) return null;
        return password_hash($clave, PASSWORD_DEFAULT);
    }

    /* ========================================================
       NIVEL 2 — CREAR USUARIO
       ======================================================== */
    public function crear(array $entrada): array
    {
        $logi = trim((string) ($entrada['logi'] ?? ''));
        $nomb = trim((string) ($entrada['nombr'] ?? ''));
        $apel = trim((string) ($entrada['apell'] ?? ''));
        $dni = preg_replace('/\D+/', '', (string) ($entrada['dni'] ?? '')) ?? '';
        $celu = preg_replace('/\D+/', '', (string) ($entrada['celul'] ?? '')) ?? '';
        $corr = trim((string) ($entrada['corre'] ?? ''));
        $clave = (string) ($entrada['clave'] ?? '');
        $rolId = (int) ($entrada['rol'] ?? 0);

        if ($logi === '' || $nomb === '' || $apel === '' || $dni === '' || $rolId <= 0) {
            throw new InvalidArgumentException('Usuario, nombres, apellidos, DNI y rol son obligatorios.');
        }
        if (!preg_match('/^\d{8}$/', $dni)) {
            throw new InvalidArgumentException('El DNI debe tener 8 dígitos.');
        }
        if ($celu !== '' && !preg_match('/^\d{9,15}$/', $celu)) {
            throw new InvalidArgumentException('El celular debe contener entre 9 y 15 dígitos.');
        }
        if ($this->repo->buscarPorDni($dni)) {
            throw new InvalidArgumentException('El DNI ya está registrado en el sistema.');
        }

        $hash = $this->prepararClave($clave);
        if ($hash === null) {
            throw new InvalidArgumentException('La contraseña debe tener entre 4 y 15 caracteres.');
        }

        $rol = $this->repo->rolPorId($rolId);
        if (!$rol || (int) $rol['activ'] !== 1) {
            throw new InvalidArgumentException('El rol seleccionado no está disponible.');
        }

        $nombreRol = strtoupper(trim((string) $rol['nombr']));
        if (!in_array($nombreRol, ['ADMINISTRADOR', 'USUARIO'], true)) {
            throw new InvalidArgumentException('El rol seleccionado no tiene una secuencia configurada.');
        }

        try {
            $this->db->beginTransaction();

            $admiCodi = null;
            $operCodi = null;

            if ($nombreRol === 'ADMINISTRADOR') {
                $admiCodi = $this->secu->reservarAdministrador();
            } else {
                $operCodi = $this->secu->reservarOperador();
            }

            $id = $this->repo->crear([
                'empre' => null,
                'rol' => $rolId,
                'admic' => $admiCodi,
                'operc' => $operCodi,
                'logi' => $logi,
                'nombr' => $nomb,
                'apell' => $apel,
                'dni' => $dni,
                'celul' => $celu,
                'clave' => $hash,
                'corre' => $corr,
            ]);

            if ($operCodi !== null) {
                $this->repo->registrarOperador($operCodi, $id);
            }

            $this->db->commit();

            return [
                'id' => $id,
                'admic' => $admiCodi,
                'operc' => $operCodi,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /* ========================================================
       NIVEL 3 — ACTUALIZAR USUARIO
       ======================================================== */
    public function actualizar(int $id, array $entrada): array
    {
        $actual = $this->repo->porId($id);
        if (!$actual) throw new InvalidArgumentException('El usuario no existe.');

        $rolEnviado = (int) ($entrada['rol'] ?? 0);
        if ($rolEnviado <= 0) {
            throw new InvalidArgumentException('Debe seleccionar un rol válido.');
        }

        $rolNuevo = $this->repo->rolPorId($rolEnviado);
        if (!$rolNuevo || (int) $rolNuevo['activ'] !== 1) {
            throw new InvalidArgumentException('El rol seleccionado no está disponible.');
        }

        $nombreRolActual = strtoupper(trim((string) $actual['rol_nomb']));
        $nombreRolNuevo = strtoupper(trim((string) $rolNuevo['nombr']));

        if (!in_array($nombreRolNuevo, ['ADMINISTRADOR', 'USUARIO'], true)) {
            throw new InvalidArgumentException('El rol seleccionado no es válido.');
        }

        /* Si el único administrador activ intenta dejar de serlo,
           el sistema debe impedir la operación. */
        if (
            $nombreRolActual === 'ADMINISTRADOR'
            && $nombreRolNuevo !== 'ADMINISTRADOR'
            && (int) $actual['activ'] === 1
            && $this->repo->contarAdministradoresActivos() <= 1
        ) {
            throw new InvalidArgumentException(
                'No se puede quitar el rol de administrador porque es el único administrador activ del sistema.'
            );
        }

        $logi = trim((string) ($entrada['logi'] ?? ''));
        $nomb = trim((string) ($entrada['nombr'] ?? ''));
        $apel = trim((string) ($entrada['apell'] ?? ''));
        $dni = preg_replace('/\D+/', '', (string) ($entrada['dni'] ?? '')) ?? '';
        $celu = preg_replace('/\D+/', '', (string) ($entrada['celul'] ?? '')) ?? '';
        $corr = trim((string) ($entrada['corre'] ?? ''));
        $clave = (string) ($entrada['clave'] ?? '');

        if ($logi === '' || $nomb === '' || $apel === '' || $dni === '') {
            throw new InvalidArgumentException('Usuario, nombres, apellidos y DNI son obligatorios.');
        }
        if (!preg_match('/^\d{8}$/', $dni)) {
            throw new InvalidArgumentException('El DNI debe tener 8 dígitos.');
        }
        if ($celu !== '' && !preg_match('/^\d{9,15}$/', $celu)) {
            throw new InvalidArgumentException('El celular debe contener entre 9 y 15 dígitos.');
        }
        $dniActual = (string) ($actual['dni'] ?? '');
        if ($dni !== $dniActual) {
            $otro = $this->repo->buscarPorDni($dni);
            if ($otro && (int) $otro['id'] !== $id) {
                throw new InvalidArgumentException('El DNI ya está registrado en otro usuario.');
            }
        }

        $hash = '';
        if ($clave !== '') {
            $hash = $this->prepararClave($clave) ?? '';
            if ($hash === '') throw new InvalidArgumentException('La contraseña debe tener entre 4 y 15 caracteres.');
        }

        $cambioRol = $nombreRolActual !== $nombreRolNuevo;
        $admiCodi = $actual['admic'];
        $operCodi = $actual['operc'];
        $nuevoOperador = null;

        try {
            $this->db->beginTransaction();

            if ($cambioRol) {
                if ($nombreRolNuevo === 'ADMINISTRADOR') {
                    $admiCodi = $this->secu->reservarAdministrador();
                    $operCodi = null;
                } else {
                    $nuevoOperador = $this->secu->reservarOperador();
                    $admiCodi = null;
                    $operCodi = $nuevoOperador;
                }
            }

            $datos = [
                'rol' => $rolEnviado,
                'admic' => $admiCodi,
                'operc' => $operCodi,
                'logi' => $logi,
                'nombr' => $nomb,
                'apell' => $apel,
                'dni' => $dni,
                'celul' => $celu,
                'corre' => $corr,
                'clave' => $hash,
            ];

            if (!$this->repo->actualizar($id, $datos)) {
                throw new RuntimeException('No se pudo actualizar el usuario.');
            }

            if ($nuevoOperador !== null) {
                $this->repo->registrarOperador($nuevoOperador, $id);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }

        return $this->repo->porId($id) ?? $actual;
    }

    /* ========================================================
       NIVEL 4 — SUSPENDER / ACTIVAR
       ======================================================== */
    public function cambiarEstado(int $id, int $activ): array
    {
        if ($id <= 0 || !in_array($activ, [0, 1], true)) {
            throw new InvalidArgumentException('Datos de estad no válidos.');
        }

        $actual = $this->repo->porId($id);
        if (!$actual) {
            throw new InvalidArgumentException('El usuario no existe.');
        }

        if (
            $activ === 0
            && strtoupper(trim((string) $actual['rol_nomb'])) === 'ADMINISTRADOR'
            && (int) $actual['activ'] === 1
            && $this->repo->contarAdministradoresActivos() <= 1
        ) {
            throw new InvalidArgumentException(
                'No se puede suspender al único administrador activ del sistema.'
            );
        }

        $this->repo->cambiarEstado($id, $activ);

        return $this->repo->porId($id) ?? [];
    }

    /* ========================================================
       NIVEL 5 — ELIMINAR USUARIO
       ======================================================== */
    public function eliminar(int $id): array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Usuario no válido.');
        }

        $actual = $this->repo->porId($id);
        if (!$actual) {
            throw new InvalidArgumentException('El usuario no existe.');
        }

        if (
            strtoupper(trim((string) $actual['rol_nomb'])) === 'ADMINISTRADOR'
            && (int) $actual['activ'] === 1
            && $this->repo->contarAdministradoresActivos() <= 1
        ) {
            throw new InvalidArgumentException(
                'No se puede eliminar al único administrador activ del sistema.'
            );
        }

        $this->db->beginTransaction();

        try {
            $eliminado = $this->repo->eliminar($id);
            if (!$eliminado) {
                throw new RuntimeException('No se pudo eliminar el usuario.');
            }

            $this->db->commit();

            return $actual;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
}
