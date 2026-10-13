<?php
/* ============================================================
   Ruta: modul/categ/venta/clien/clien-repos.php
   Módulo: Ventas / Clientes

   RESPONSABILIDAD:
   Consultar y modificar exclusivamente VENTA.clien.

   No contiene HTTP, HTML, JavaScript ni sesiones.
============================================================ */
declare(strict_types=1);

final class ClieRepo
{
    public function __construct(private PDO $db) {}

    public function listar(string $buscar = '', int $limite = 50): array
    {
        $limite = max(1, min($limite, 100));
        $buscar = trim($buscar);

        $sql = 'SELECT id,tipod,docum,razon,telef,email,direc,estad,fecre FROM clien';
        $params = [];

        if ($buscar !== '') {
            $sql .= ' WHERE razon LIKE :nombr OR docum LIKE :docum OR telef LIKE :telef OR email LIKE :email OR direc LIKE :direc';
            $v = '%' . $buscar . '%';
            $params = compact('v');
            $params = [
                'nombr' => $v,
                'docum' => $v,
                'telef' => $v,
                'email' => $v,
                'direc' => $v,
            ];
        }

        $sql .= " ORDER BY id DESC LIMIT {$limite}";
        

        $st = $this->db->prepare($sql);
        foreach ($params as $n => $v) {
            $st->bindValue(':' . $n, $v, PDO::PARAM_STR);
        }
        $st->execute();

        $r = $st->fetchAll(PDO::FETCH_ASSOC);
        
        return $r;
    }

    public function agregar(array $datos): int
    {
        $nombr = trim((string)($datos['razon'] ?? ''));
        if ($nombr === '') {
            throw new InvalidArgumentException('El nombr o razón social es obligatorio.');
        }

        $st = $this->db->prepare(
            'INSERT INTO clien (tipod,docum,razon,telef,email,direc)
             VALUES (:tipo,:docum,:nombr,:telef,:email,:direc)'
        );
        $st->execute([
            'tipo' => trim((string)($datos['tipod'] ?? 'DNI')) ?: 'DNI',
            'docum' => self::nulo($datos['docum'] ?? null),
            'nombr' => $nombr,
            'telef' => self::nulo($datos['telef'] ?? null),
            'email' => self::nulo($datos['email'] ?? null),
            'direc' => self::nulo($datos['direc'] ?? null),
        ]);

        $id = (int)$this->db->lastInsertId();
        
        return $id;
    }

    public function resolverOperacion(array $datos): int
    {
        $id = (int)($datos['id'] ?? 0);
        $docum = preg_replace('/\D+/', '', (string)($datos['docum'] ?? ''));
        $nombr = trim((string)($datos['razon'] ?? $datos['razon'] ?? ''));
        $direc = trim((string)($datos['direc'] ?? ''));
        $tipo = strtoupper(trim((string)($datos['tipod'] ?? '')));

        if ($id > 0) {
            $st = $this->db->prepare(
                'SELECT id FROM clien WHERE id=:id AND estad=1 LIMIT 1'
            );
            $st->execute(['id'=>$id]);

            if (!$st->fetchColumn()) {
                throw new InvalidArgumentException(
                    'El cliente no existe o está inactivo.'
                );
            }

            return $id;
        }

        if (!in_array(strlen($docum), [8, 11], true)) {
            throw new InvalidArgumentException(
                'El cliente debe tener un RUC/DNI identificado.'
            );
        }

        if ($nombr === '') {
            throw new InvalidArgumentException(
                'El nombr o razón social del cliente es obligatorio.'
            );
        }

        $st = $this->db->prepare(
            'SELECT id,estad FROM clien WHERE docum=:docum LIMIT 1'
        );
        $st->execute(['docum'=>$docum]);
        $existente = $st->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            if ((int)$existente['estad'] !== 1) {
                throw new InvalidArgumentException(
                    'El cliente existe pero está inactivo.'
                );
            }

            return (int)$existente['id'];
        }

        if ($tipo === '') {
            $tipo = strlen($docum) === 11 ? 'RUC' : 'DNI';
        }

        try {
            $st = $this->db->prepare(
                'INSERT INTO clien
                    (tipod,docum,razon,direc)
                 VALUES
                    (:tipo,:docum,:nombr,:direc)'
            );
            $st->execute([
                'tipo'=>$tipo,
                'docum'=>$docum,
                'nombr'=>$nombr,
                'direc'=>$direc !== '' ? $direc : null
            ]);

            return (int)$this->db->lastInsertId();

        } catch (PDOException $e) {
            /* Otro proceso pudo registrar el mismo docum primero. */
            if ((int)$e->errorInfo[1] !== 1062) {
                throw $e;
            }

            $st = $this->db->prepare(
                'SELECT id,estad FROM clien WHERE docum=:docum LIMIT 1'
            );
            $st->execute(['docum'=>$docum]);
            $existente = $st->fetch(PDO::FETCH_ASSOC);

            if (!$existente || (int)$existente['estad'] !== 1) {
                throw new InvalidArgumentException(
                    'El cliente existe pero no está disponible.'
                );
            }

            return (int)$existente['id'];
        }
    }

    public function estad(int $id, int $estad): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Cliente no válido.');
        }

        $estad = $estad === 1 ? 1 : 0;
        $st = $this->db->prepare('UPDATE clien SET estad=:estad WHERE id=:id');
        $st->execute(['estad' => $estad, 'id' => $id]);

        $ok = $st->rowCount() > 0;
        
        return $ok;
    }

    private static function nulo(mixed $v): ?string
    {
        $v = trim((string)($v ?? ''));
        return $v === '' ? null : $v;
    }

    public function porId(int $id): ?array
    {
        $st=$this->db->prepare('SELECT id,tipod,docum,razon,direc,estad FROM clien WHERE id=:id LIMIT 1');$st->execute(['id'=>$id]);$r=$st->fetch(PDO::FETCH_ASSOC);return $r?:null;
    }
    public function porDocum(string $docum): ?array
    {
        $st=$this->db->prepare('SELECT id,tipod,docum,razon,direc,estad FROM clien WHERE docum=:docum AND estad=1 LIMIT 1');$st->execute(['docum'=>trim($docum)]);$r=$st->fetch(PDO::FETCH_ASSOC);return $r?:null;
    }
}
