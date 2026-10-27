<?php
/* ============================================================
   Ruta: modul/categ/catal/produ/produ-repos.php
   Módulo: Catálogo / Productos
   Función: Acceso a produ.

   NIVEL 1 — Buscar y listar.
   NIVEL 2 — Crear y editar.
   NIVEL 3 — Obtener.
   NIVEL 4 — Eliminar.

   PRECIOS:
   - pcomp = precio de compra.
   - pvent = precio de venta.

   IMPORTANTE:
   `preci` ya no participa en CATAL.
============================================================ */
declare(strict_types=1);

final class ProdRepo
{
    public function __construct(private PDO $db)
    {
    }

    public function listar(string $buscar = '', int $limite = 50): array
    {
        $limite = max(1, min($limite, 100));
        $buscar = trim($buscar);
        $where = [];
        $params = [];

        $words = $buscar === ''
            ? []
            : preg_split('/\s+/u', $buscar, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $i => $word) {
            $grupo = [];

            foreach (['id', 'descr', 'codig', 'origi', 'medid', 'equiv', 'super'] as $campo) {
                $param = "b_{$i}_{$campo}";
                $grupo[] = $campo === 'id'
                    ? "CAST(p.{$campo} AS CHAR) LIKE :{$param}"
                    : "p.{$campo} LIKE :{$param}";
                $params[$param] = "%{$word}%";
            }

            $where[] = '(' . implode(' OR ', $grupo) . ')';
        }

        $sql = "SELECT
                    p.id,
                    p.descr,
                    p.codig,
                    p.origi,
                    p.medid,
                    p.super,
                    p.equiv,
                    p.pcomp,
                    p.pvent,
                    p.fecha
                FROM produ p";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY p.id DESC LIMIT {$limite}";

        $st = $this->db->prepare($sql);

        foreach ($params as $nombre => $valor) {
            $st->bindValue(':' . $nombre, $valor, PDO::PARAM_STR);
        }

        $st->execute();

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtener(int $id): array
    {
        $st = $this->db->prepare(
            'SELECT id,descr,codig,origi,medid,super,equiv,pcomp,pvent,fecha
             FROM produ
             WHERE id=:id'
        );

        $st->execute(['id' => $id]);
        $fila = $st->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            throw new RuntimeException('El producto no existe.');
        }

        return $fila;
    }

    public function guardar(array $datos): int
    {
        $id = (int) ($datos['id'] ?? 0);
        $descr = trim((string) ($datos['descr'] ?? ''));

        if ($descr === '') {
            throw new InvalidArgumentException(
                'La descripción es obligatoria.'
            );
        }

        $codig = trim((string) ($datos['codig'] ?? ''));
        $origi = trim((string) ($datos['origi'] ?? ''));
        $medid = trim((string) ($datos['medid'] ?? ''));
        $equiv = trim((string) ($datos['equiv'] ?? ''));
        $super = trim((string) ($datos['super'] ?? ''));
        $pcomp = $this->numero($datos['pcomp'] ?? 0, 'PCOMP');
        $pvent = $this->numero($datos['pvent'] ?? 0, 'PVENT');

        $this->db->beginTransaction();

        try {
            if ($id > 0) {
                $this->db->prepare(
                    'UPDATE produ
                     SET descr=:descr,
                         codig=:codig,
                         origi=:origi,
                         medid=:medid,
                         super=:super,
                         equiv=:equiv,
                         pcomp=:pcomp,
                         pvent=:pvent,
                         fecha=NOW()
                     WHERE id=:id'
                )->execute([
                    'descr' => $descr,
                    'codig' => $codig === '' ? null : $codig,
                    'origi' => $origi === '' ? null : $origi,
                    'medid' => $medid === '' ? null : $medid,
                    'super' => $super === '' ? null : $super,
                    'equiv' => $equiv === '' ? null : $equiv,
                    'pcomp' => $pcomp,
                    'pvent' => $pvent,
                    'id' => $id,
                ]);
            } else {
                $this->db->prepare(
                    'INSERT INTO produ
                        (descr,codig,origi,medid,super,equiv,pcomp,pvent,fecha)
                     VALUES
                        (:descr,:codig,:origi,:medid,:super,:equiv,:pcomp,:pvent,NOW())'
                )->execute([
                    'descr' => $descr,
                    'codig' => $codig === '' ? null : $codig,
                    'origi' => $origi === '' ? null : $origi,
                    'medid' => $medid === '' ? null : $medid,
                    'super' => $super === '' ? null : $super,
                    'equiv' => $equiv === '' ? null : $equiv,
                    'pcomp' => $pcomp,
                    'pvent' => $pvent,
                ]);

                $id = (int) $this->db->lastInsertId();
            }

            $this->db->commit();
            return $id;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function eliminar(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Producto no válido.'
            );
        }

        $this->db->beginTransaction();

        try {
            $st = $this->db->prepare(
                'DELETE FROM produ WHERE id=:id'
            );
            $st->execute(['id' => $id]);

            if ($st->rowCount() === 0) {
                throw new RuntimeException(
                    'El producto no existe.'
                );
            }

            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    private function numero(mixed $valor, string $campo): float
    {
        $valor = str_replace(',', '.', trim((string) $valor));

        if ($valor === '' || !is_numeric($valor)) {
            throw new InvalidArgumentException(
                "{$campo} no es válido."
            );
        }

        $numero = (float) $valor;

        if ($numero < 0) {
            throw new InvalidArgumentException(
                "{$campo} no puede ser negativo."
            );
        }

        return $numero;
    }

    public function buscarOpera(string $buscar = '', int $limite = 20): array
    {
        $limite=max(1,min($limite,50));$buscar=trim($buscar);$where=[];$par=[];
        $words=$buscar===''?[]:preg_split('/\s+/u',$buscar,-1,PREG_SPLIT_NO_EMPTY);
        foreach($words as $i=>$word){
            $g=[];
            foreach(['p.id','p.descr','p.codig','p.origi','p.medid','p.super','p.equiv','v.motor','v.model','v.anio','v.gdesd','v.ghast'] as $c){
                $k='b_'.$i.'_'.str_replace('.','_',$c);
                $g[]=in_array($c,['p.id','v.anio','v.gdesd','v.ghast'],true)?"CAST({$c} AS CHAR) LIKE :{$k}":"{$c} LIKE :{$k}";
                $par[$k]='%'.$word.'%';
            }
            $where[]='('.implode(' OR ',$g).')';
        }
        $where[]="((v.id IS NOT NULL AND UPPER(TRIM(p.descr))<>'VARIOS')
                    OR (UPPER(TRIM(p.descr))='VARIOS'
                    AND NOT EXISTS (SELECT 1 FROM equiv e2 WHERE e2.produ=p.id)))";
        $sql="SELECT p.id,p.descr,p.codig,p.origi,p.medid,p.super,p.equiv,p.pvent,
                      v.motor,v.model,
                      CONCAT(LPAD(MOD(v.anio,100),2,'0'),'-',v.gdesd,'-',LPAD(MOD(v.ghast,100),2,'0')) anio
               FROM produ p
               LEFT JOIN equiv e ON e.produ=p.id
               LEFT JOIN vehic v ON v.id=e.vehic";
        if($where)$sql.=' WHERE '.implode(' AND ',$where);
        $sql.=" ORDER BY p.id DESC,v.id ASC LIMIT {$limite}";
        $st=$this->db->prepare($sql);$st->execute($par);return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
