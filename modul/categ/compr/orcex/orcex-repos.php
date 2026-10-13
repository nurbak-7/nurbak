<?php
/* ============================================================
   Ruta: modul/categ/compr/orcex/orcex-repos.php
   Módulo: Compras / Orden Compra Ext
   Función: Acceso a orcex y detox.
============================================================ */
declare(strict_types=1);

final class Orcex
{
    public function __construct(
        private PDO $db,
        private PDO $cat,
        private UsuaRepo $usua,
        private ProdRepo $prod,
        private ProvRepo $prov
    ) {}

    public function porRuc(string $ruc): ?array
    {
        $q = $this->db->prepare(
            'SELECT id,razon,ruc,direc,estad FROM prove WHERE ruc=:ruc ORDER BY id DESC LIMIT 1'
        );
        $q->execute(['ruc' => trim($ruc)]);
        $dato = $q->fetch(PDO::FETCH_ASSOC);
        return $dato ?: null;
    }

    public function proveer(string $ruc, string $razon, string $direc): array
    {
        $ruc = trim($ruc);
        $razon = trim($razon);
        $direc = trim($direc);

        if ($ruc === '' || $razon === '') {
            throw new InvalidArgumentException('Datos del proveedor externo incompletos.');
        }

        $local = $this->porRuc($ruc);
        if ($local) {
            if ((int)($local['estad'] ?? 0) !== 1) {
                throw new InvalidArgumentException('El proveedor existe pero está inactivo.');
            }
            return ['nuevo' => false, 'prove' => $local];
        }

        $id = $this->prov->agregar([
            'razon' => $razon,
            'ruc' => $ruc,
            'direc' => $direc
        ]);

        $dato = $this->porRuc($ruc);
        if (!$dato || (int)$dato['id'] !== $id) {
            throw new RuntimeException('No se pudo registrar el proveedor en PROVE.');
        }

        return ['nuevo' => true, 'prove' => $dato];
    }

    public function buscarProd(string $busc, int $limi = 20): array
    {
        return $this->prod->buscarOpera($busc, $limi);
    }

    public function lista(string $busc = '', int $limi = 50): array
    {
        $limi = max(1, min($limi, 100));
        $busc = trim($busc);
        $sql = "SELECT o.id,o.nume,o.prove,o.razon,o.ruc,o.emiso,o.estad,o.fecha,o.total
                FROM orcex o";
        $par = [];
        if ($busc !== '') {
            $sql .= " WHERE o.razon LIKE :busc
                       OR o.ruc LIKE :busc
                       OR o.emiso LIKE :busc
                       OR o.estad LIKE :busc
                       OR CAST(o.nume AS CHAR) LIKE :busc";
            $par['busc'] = '%' . $busc . '%';
        }
        $sql .= " ORDER BY o.id DESC LIMIT {$limi}";
        $q = $this->db->prepare($sql);
        $q->execute($par);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public function emisor(string $codig): ?array
    {
        return $this->usua->porOperc($codig);
    }

    public function crear(?int $prov,string $razon,string $ruc,string $direc,string $emiso,string $fecha,mixed $det): array
    {
        if ($emiso === '') {
            throw new InvalidArgumentException('El emisor es obligatorio.');
        }

        $prov = $prov !== null && $prov > 0 ? $prov : null;

        $ruc = preg_replace('/\D+/', '', $ruc);
        if ($ruc !== '' && strlen($ruc) !== 11) {
            throw new InvalidArgumentException('RUC no válido.');
        }

        if (!$this->emisor($emiso)) {
            throw new InvalidArgumentException('El emisor no existe o está inactivo.');
        }

        $fec = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$fec || $fec->format('Y-m-d') !== $fecha) {
            throw new InvalidArgumentException('Fecha de orden no válida.');
        }

        if (!is_array($det)) {
            $det = [];
        }

        if ($prov !== null) {
            $q = $this->db->prepare('SELECT id FROM prove WHERE id=:id AND estad=1');
            $q->execute(['id'=>$prov]);
            if (!$q->fetch()) throw new InvalidArgumentException('Proveedor inexistente o inactivo.');
        }

        $lis = [];
        $tot = 0.0;
        foreach ($det as $d) {
            if (!is_array($d)) throw new InvalidArgumentException('Detalle de orden no válido.');
            $pro = (int)($d['produ'] ?? 0);
            $desc = trim((string)($d['descr'] ?? ''));
            $can = (float)($d['canti'] ?? 0);
            $pre = (float)($d['preun'] ?? 0);
            if ($can <= 0 || $pre < 0 || $desc === '') {
                throw new InvalidArgumentException('Detalle de orden no válido.');
            }
            if ($pro > 0) {
                $qp = $this->cat->prepare('SELECT id,descr FROM produ WHERE id=:id');
                $qp->execute(['id'=>$pro]);
                if (!$qp->fetch(PDO::FETCH_ASSOC)) throw new InvalidArgumentException('Producto inexistente.');
            } else {
                $pro = null;
            }
            $sub = round($can * $pre, 2);
            $tot = round($tot + $sub, 2);
            $lis[] = ['produ'=>$pro,'descr'=>$desc,'canti'=>$can,'preun'=>$pre,'subto'=>$sub];
        }

        $this->db->beginTransaction();
        try {
            /* Igual que Cotización: bloquear la fila de numeración y avanzar el correlativo. */
            $n = $this->db->prepare(
                "SELECT id,ultim
                 FROM numer
                 WHERE tipo='orcex'
                 FOR UPDATE"
            );
            $n->execute();
            $num = $n->fetch(PDO::FETCH_ASSOC);

            if (!$num) {
                throw new InvalidArgumentException('La numeración ORCEX no está configurada.');
            }

            $nume = (int)$num['ultim'] + 1;

            $this->db->prepare(
                'UPDATE numer SET ultim=:ultim WHERE id=:id'
            )->execute([
                'ultim'=>$nume,
                'id'=>(int)$num['id']
            ]);

            $q = $this->db->prepare(
                "INSERT INTO orcex
                    (nume,prove,razon,ruc,direc,emiso,estad,fecha,total)
                 VALUES
                    (:nume,:prove,:razon,:ruc,:direc,:emiso,'borrador',:fecha,:total)"
            );
            $q->execute([
                'nume'=>$nume,
                'prove'=>$prov,
                'razon'=>trim($razon) !== '' ? trim($razon) : null,
                'ruc'=>$ruc !== '' ? $ruc : null,
                'direc'=>trim($direc) !== '' ? trim($direc) : null,
                'emiso'=>$emiso,
                'fecha'=>$fecha . ' 00:00:00',
                'total'=>$tot
            ]);
            $id = (int)$this->db->lastInsertId();

            $q = $this->db->prepare(
                'INSERT INTO detox (orcex,produ,descr,canti,preun,subto) VALUES (:orcex,:produ,:descr,:canti,:preun,:subto)'
            );
            foreach ($lis as $d) {
                $q->execute([
                    'orcex'=>$id,
                    'produ'=>$d['produ'],
                    'descr'=>$d['descr'],
                    'canti'=>$d['canti'],
                    'preun'=>$d['preun'],
                    'subto'=>$d['subto']
                ]);
            }

            $this->db->commit();

            return [
                'id' => $id,
                'nume' => $nume,
                'prove' => $prov,
                'nuevo' => false
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function numeracion(): array
    {
        $q = $this->db->prepare(
            "SELECT ultim,(ultim+1) AS siguiente
             FROM numer
             WHERE tipo='orcex'
             LIMIT 1"
        );
        $q->execute();
        $row = $q->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new InvalidArgumentException('La numeración ORCEX no está configurada.');
        }

        return ['siguiente' => (int)$row['siguiente']];
    }

    public function estad(int $id,string $estad): bool
    {
        $validos=['borrador','emitida','atendida','anulada'];
        if ($id<=0 || !in_array($estad,$validos,true)) throw new InvalidArgumentException('Estado de orden no válido.');
        $q=$this->db->prepare('UPDATE orcex SET estad=:estad WHERE id=:id');
        $q->execute(['estad'=>$estad,'id'=>$id]);
        return $q->rowCount()>0;
    }
}
