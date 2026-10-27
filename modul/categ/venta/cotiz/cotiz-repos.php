<?php
/* ============================================================
   Ruta: modul/categ/venta/cotiz/cotiz-repos.php
   Módulo: Ventas / Cotización / Repositorio

   RESPONSABILIDAD:
   Consultar y persistir exclusivamente Cotización.

   IMPORTANTE:
   - No reserva stock.
   - No descuenta stock.
   - No genera salss.
============================================================ */
declare(strict_types=1);

final class CotizRepo
{
    public function __construct(
        private PDO $db,
        private PDO $cataDb,
        private PDO $logiDb
    ) {}

    public function listar(string $buscar = '', int $limite = 50): array
    {
        $limite = max(1, min($limite, 100));
        $buscar = trim($buscar);

        $sql = "SELECT
                    c.id,c.clien,c.vende,c.serie,c.numco,c.fecha,c.vigia,c.estad,
                    c.subto,c.igv,c.total,
                    (c.estad='activa' AND c.vigia < CURDATE()) AS vencida,
                    cl.razon,cl.docum
                FROM cotiz c
                INNER JOIN clien cl ON cl.id=c.clien";

        $params = [];

        if ($buscar !== '') {
            $sql .= " WHERE cl.razon LIKE :clien
                       OR cl.docum LIKE :docum
                       OR c.estad LIKE :estad
                       OR CONCAT(c.serie,'-',LPAD(c.numco,4,'0')) LIKE :documco
                       OR CAST(c.id AS CHAR) LIKE :id";
            $v = "%{$buscar}%";
            $params = [
                'clien'=>$v,
                'docum'=>$v,
                'estad'=>$v,
                'documco'=>$v,
                'id'=>$v
            ];
        }

        $sql .= " ORDER BY c.id DESC LIMIT {$limite}";

        $st = $this->db->prepare($sql);
        $st->execute($params);
        $filas = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($filas as &$fila) {
            $fila['id'] = (int)$fila['id'];
            $fila['clien'] = (int)$fila['clien'];
            $fila['vende'] = (int)$fila['vende'];
            $fila['numco'] = (int)$fila['numco'];
            $fila['subto'] = (float)$fila['subto'];
            $fila['igv'] = (float)$fila['igv'];
            $fila['total'] = (float)$fila['total'];
            $fila['vencida'] = (bool)$fila['vencida'];
            $fila['situ'] = $fila['estad'] === 'cancelada'
                ? 'Cancelada'
                : ($fila['vencida'] ? 'Vencida' : 'Vigente');
            $fila['vende_cod'] = '';
            $fila['vende_nom'] = '';
        }
        unset($fila);

        $this->completarVendedores($filas);

        return $filas;
    }

    public function obtener(int $id): array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Cotización no válida.');
        }

        $st = $this->db->prepare(
            "SELECT
                c.id,c.clien,c.vende,c.placa,c.serie,c.numco,c.fecha,c.vigia,
                c.estad,c.obser,c.subto,c.igv,c.total,
                cl.docum,cl.razon,cl.direc
             FROM cotiz c
             INNER JOIN clien cl ON cl.id=c.clien
             WHERE c.id=:id
             LIMIT 1"
        );
        $st->execute(['id'=>$id]);
        $cot = $st->fetch(PDO::FETCH_ASSOC);

        if (!$cot) {
            throw new InvalidArgumentException('La cotización no existe.');
        }

        $dt = $this->db->prepare(
            'SELECT cotiz,produ,canti,preun,total
             FROM dtall
             WHERE cotiz=:cotiz
             ORDER BY id ASC'
        );
        $dt->execute(['cotiz'=>$id]);
        $detal = $dt->fetchAll(PDO::FETCH_ASSOC);

        if ($detal !== []) {
            $ids = array_values(array_unique(array_map(
                static fn(array $row): int => (int)$row['produ'],
                $detal
            )));

            $ph = [];
            $par = [];
            foreach ($ids as $i => $prod) {
                $key = 'p' . $i;
                $ph[] = ':' . $key;
                $par[$key] = $prod;
            }

            $qp = $this->cataDb->prepare(
                'SELECT id,codig,descr
                 FROM produ
                 WHERE id IN (' . implode(',', $ph) . ')'
            );
            $qp->execute($par);
            $produs = [];
            foreach ($qp->fetchAll(PDO::FETCH_ASSOC) as $prod) {
                $produs[(int)$prod['id']] = $prod;
            }

            foreach ($detal as &$row) {
                $prod = $produs[(int)$row['produ']] ?? [];
                $row['produ'] = (int)$row['produ'];
                $row['canti'] = (float)$row['canti'];
                $row['preun'] = (float)$row['preun'];
                $row['total'] = (float)$row['total'];
                $row['codig'] = $prod['codig'] ?? '';
                $row['descr'] = $prod['descr'] ?? '';
            }
            unset($row);
        }

        $cot['id'] = (int)$cot['id'];
        $cot['clien'] = (int)$cot['clien'];
        $cot['vende'] = (int)$cot['vende'];
        $cot['numco'] = (int)$cot['numco'];
        $cot['subto'] = (float)$cot['subto'];
        $cot['igv'] = (float)$cot['igv'];
        $cot['total'] = (float)$cot['total'];
        $vende = $this->vendedor((int)$cot['vende']);
        $cot['vende_cod'] = $vende['codig'] ?? '';
        $cot['vende_nom'] = $vende['display'] ?? '';

        return [
            'cotiz'=>$cot,
            'detalles'=>$detal
        ];
    }

    private function completarVendedores(array &$filas): void
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn(array $fila): int => (int)$fila['vende'],
            $filas
        ))));
        if ($ids === []) return;

        $ph = [];
        $par = [];
        foreach ($ids as $i => $id) {
            $key = 'u' . $i;
            $ph[] = ':' . $key;
            $par[$key] = $id;
        }

        $st = $this->logiDb->prepare(
            'SELECT id,admic,operc,nombr,apell
             FROM usuar
             WHERE id IN (' . implode(',', $ph) . ')'
        );
        $st->execute($par);
        $usuarios = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $nom = preg_split('/\s+/u', trim((string)$row['nombr']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $ape = preg_split('/\s+/u', trim((string)$row['apell']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $usuarios[(int)$row['id']] = [
                'codig' => $row['admic'] ?: $row['operc'],
                'display' => trim(($nom[0] ?? '') . ' ' . ($ape[0] ?? ''))
            ];
        }

        foreach ($filas as &$fila) {
            $vende = $usuarios[(int)$fila['vende']] ?? [];
            $fila['vende_cod'] = $vende['codig'] ?? '';
            $fila['vende_nom'] = $vende['display'] ?? '';
        }
        unset($fila);
    }

    private function vendedor(int $id): ?array
    {
        if ($id <= 0) return null;
        $st = $this->logiDb->prepare(
            'SELECT id,admic,operc,nombr,apell
             FROM usuar
             WHERE id=:id
             LIMIT 1'
        );
        $st->execute(['id'=>$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $nom = preg_split('/\s+/u', trim((string)$row['nombr']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $ape = preg_split('/\s+/u', trim((string)$row['apell']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return [
            'id'=>(int)$row['id'],
            'codig'=>$row['admic'] ?: $row['operc'],
            'display'=>trim(($nom[0] ?? '') . ' ' . ($ape[0] ?? ''))
        ];
    }

    public function numeracion(): array
    {
        $st = $this->db->prepare(
            "SELECT serie,ultim,(ultim+1) AS siguiente
             FROM numer
             WHERE tipo='cotiz' AND serie='C001'
             LIMIT 1"
        );
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new InvalidArgumentException('La numeración C001 no está configurada.');
        }

        return [
            'serie' => $row['serie'],
            'siguiente' => (int)$row['siguiente']
        ];
    }

    public function buscarCliente(string $docum): ?array
    {
        $st = $this->db->prepare(
            'SELECT id,tipod,docum,razon,direc
             FROM clien
             WHERE docum=:docum AND estad=1
             LIMIT 1'
        );
        $st->execute(['docum'=>$docum]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function buscarVendedor(string $codig): ?array
    {
        $st = $this->logiDb->prepare(
            "SELECT
                u.id,u.admic,u.operc,u.nombr,u.apell,
                r.nombr AS rol_nomb
             FROM usuar u
             INNER JOIN rol r ON r.id=u.rol
             WHERE u.activ=1
               AND r.activ=1
               AND r.nombr IN ('USUARIO','ADMINISTRADOR')
               AND (u.admic=:admi OR u.operc=:oper)
             LIMIT 1"
        );
        $st->execute(['admi'=>$codig,'oper'=>$codig]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $nom = preg_split('/\s+/u', trim((string)$row['nombr']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $ape = preg_split('/\s+/u', trim((string)$row['apell']), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return [
            'id' => (int)$row['id'],
            'codig' => $row['admic'] ?: $row['operc'],
            'nombr' => $nom[0] ?? '',
            'apell' => $ape[0] ?? '',
            'display' => trim(($nom[0] ?? '') . ' ' . ($ape[0] ?? ''))
        ];
    }

    public function buscarProductos(string $buscar = '', int $limite = 20): array
    {
        $limite = max(1, min($limite, 50));
        $buscar = trim($buscar);

        $sql = "
            SELECT
                p.id,
                p.codig,
                p.descr,
                p.origi,
                p.medid,
                p.super,
                v.motor,
                v.model,
                CONCAT(
                    LPAD(MOD(v.anio,100),2,'0'),
                    '-',
                    v.gdesd,
                    '-',
                    LPAD(MOD(v.ghast,100),2,'0')
                ) AS anio,
                COALESCE(p.pvent,0) AS preci
            FROM produ p
            LEFT JOIN equiv e ON e.produ=p.id
            LEFT JOIN vehic v ON v.id=e.vehic
        ";

        $par = [];
        $cond = ["((v.id IS NOT NULL AND UPPER(TRIM(p.descr))<>'VARIOS')
            OR (UPPER(TRIM(p.descr))='VARIOS'
            AND NOT EXISTS (SELECT 1 FROM equiv e2 WHERE e2.produ=p.id)))"];

        if ($buscar !== '') {
            $tokn = preg_split('/\s+/u', mb_strtolower($buscar), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($tokn as $i => $tok) {
                $patr = "%{$tok}%";
                $des = "b{$i}des";
                $cod = "b{$i}cod";
                $ori = "b{$i}ori";
                $med = "b{$i}med";
                $equ = "b{$i}equ";
                $sup = "b{$i}sup";
                $mot = "b{$i}mot";
                $mod = "b{$i}mod";
                $ani = "b{$i}ani";
                $gde = "b{$i}gde";
                $gha = "b{$i}gha";

                $cond[] = "(
                    LOWER(COALESCE(p.descr,'')) LIKE :$des
                    OR LOWER(COALESCE(p.codig,'')) LIKE :$cod
                    OR LOWER(COALESCE(p.origi,'')) LIKE :$ori
                    OR LOWER(COALESCE(p.medid,'')) LIKE :$med
                    OR LOWER(COALESCE(p.equiv,'')) LIKE :$equ
                    OR LOWER(COALESCE(CAST(p.super AS CHAR),'')) LIKE :$sup
                    OR LOWER(COALESCE(v.motor,'')) LIKE :$mot
                    OR LOWER(COALESCE(v.model,'')) LIKE :$mod
                    OR LOWER(COALESCE(CAST(v.anio AS CHAR),'')) LIKE :$ani
                    OR LOWER(COALESCE(CAST(v.gdesd AS CHAR),'')) LIKE :$gde
                    OR LOWER(COALESCE(CAST(v.ghast AS CHAR),'')) LIKE :$gha
                )";

                $par[$des]=$patr; $par[$cod]=$patr; $par[$ori]=$patr; $par[$med]=$patr;
                $par[$equ]=$patr; $par[$sup]=$patr; $par[$mot]=$patr; $par[$mod]=$patr;
                $par[$ani]=$patr; $par[$gde]=$patr; $par[$gha]=$patr;
            }
        }

        if ($cond) {
            $sql .= ' WHERE ' . implode(' AND ', $cond);
        }

        $sql .= " ORDER BY p.id DESC,v.id ASC LIMIT {$limite}";

        $st = $this->cataDb->prepare($sql);
        $st->execute($par);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar(
        int $id,
        int $clien,
        int $vende,
        string $placa,
        string $fecha,
        string $vigia,
        string $obser,
        array $detal,
        float $subto,
        float $igv,
        float $total
    ): array {
        $this->validarCliente($clien);
        $this->validarVendedor($vende);

        $this->db->beginTransaction();
        try {
            if ($id > 0) {
                $q = $this->db->prepare(
                    'SELECT id,serie,numco,estad
                     FROM cotiz
                     WHERE id=:id
                     FOR UPDATE'
                );
                $q->execute(['id'=>$id]);
                $cot = $q->fetch(PDO::FETCH_ASSOC);

                if (!$cot) {
                    throw new InvalidArgumentException('La cotización no existe.');
                }
                if ($cot['estad'] === 'cancelada') {
                    throw new InvalidArgumentException('La cotización está cancelada.');
                }

                $up = $this->db->prepare(
                    'UPDATE cotiz
                     SET clien=:clien,vende=:vende,placa=:placa,fecha=:fecha,vigia=:vigia,
                         obser=:obser,subto=:subto,igv=:igv,total=:total
                     WHERE id=:id'
                );
                $up->execute([
                    'clien'=>$clien,
                    'vende'=>$vende,
                    'placa'=>$placa !== '' ? $placa : null,
                    'fecha'=>$fecha,
                    'vigia'=>$vigia,
                    'obser'=>$obser !== '' ? $obser : null,
                    'subto'=>$subto,
                    'igv'=>$igv,
                    'total'=>$total,
                    'id'=>$id
                ]);

                $serie = $cot['serie'];
                $numco = (int)$cot['numco'];
            } else {
                $n = $this->db->prepare(
                    "SELECT id,serie,ultim
                     FROM numer
                     WHERE tipo='cotiz' AND serie='C001'
                     FOR UPDATE"
                );
                $n->execute();
                $num = $n->fetch(PDO::FETCH_ASSOC);
                if (!$num) {
                    throw new InvalidArgumentException('La numeración C001 no está configurada.');
                }

                $serie = (string)$num['serie'];
                $numco = (int)$num['ultim'] + 1;

                $this->db->prepare(
                    'UPDATE numer SET ultim=:ultim WHERE id=:id'
                )->execute([
                    'ultim'=>$numco,
                    'id'=>(int)$num['id']
                ]);

                $ins = $this->db->prepare(
                    "INSERT INTO cotiz
                        (clien,vende,placa,serie,numco,fecha,vigia,estad,obser,subto,igv,total)
                     VALUES
                        (:clien,:vende,:placa,:serie,:numco,:fecha,:vigia,'activa',:obser,:subto,:igv,:total)"
                );
                $ins->execute([
                    'clien'=>$clien,
                    'vende'=>$vende,
                    'placa'=>$placa !== '' ? $placa : null,
                    'serie'=>$serie,
                    'numco'=>$numco,
                    'fecha'=>$fecha,
                    'vigia'=>$vigia,
                    'obser'=>$obser !== '' ? $obser : null,
                    'subto'=>$subto,
                    'igv'=>$igv,
                    'total'=>$total
                ]);
                $id = (int)$this->db->lastInsertId();
            }

            $this->db->prepare('DELETE FROM dtall WHERE cotiz=:cotiz')->execute(['cotiz'=>$id]);

            $ins = $this->db->prepare(
                'INSERT INTO dtall (cotiz,produ,canti,preun,total)
                 VALUES (:cotiz,:produ,:canti,:preun,:total)'
            );

            foreach ($detal as $row) {
                $ins->execute([
                    'cotiz'=>$id,
                    'produ'=>$row['produ'],
                    'canti'=>$row['canti'],
                    'preun'=>$row['preun'],
                    'total'=>$row['total']
                ]);
            }

            $this->db->commit();

            return [
                'id'=>$id,
                'serie'=>$serie,
                'numco'=>$numco,
                'subto'=>$subto,
                'igv'=>$igv,
                'total'=>$total
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function cancelar(int $id): void
    {
        if ($id <= 0) return;

        $st = $this->db->prepare(
            "UPDATE cotiz
             SET estad='cancelada'
             WHERE id=:id AND estad='activa'"
        );
        $st->execute(['id'=>$id]);

        if ($st->rowCount() === 0) {
            throw new InvalidArgumentException('La cotización no puede ser cancelada.');
        }
    }

    private function validarCliente(int $id): void
    {
        $st = $this->db->prepare(
            'SELECT id FROM clien WHERE id=:id AND estad=1 LIMIT 1'
        );
        $st->execute(['id'=>$id]);
        if (!$st->fetchColumn()) {
            throw new InvalidArgumentException('El cliente no existe o está inactivo.');
        }
    }

    private function validarVendedor(int $id): void
    {
        $st = $this->logiDb->prepare(
            "SELECT u.id
             FROM usuar u
             INNER JOIN rol r ON r.id=u.rol
             WHERE u.id=:id AND u.activ=1 AND r.activ=1
               AND r.nombr IN ('USUARIO','ADMINISTRADOR')
             LIMIT 1"
        );
        $st->execute(['id'=>$id]);
        if (!$st->fetchColumn()) {
            throw new InvalidArgumentException('El vendedor no existe o está inactivo.');
        }
    }
}
