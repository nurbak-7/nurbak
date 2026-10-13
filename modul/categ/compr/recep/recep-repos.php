<?php
/* ============================================================
   Ruta: modul/categ/compr/recep/recep-repos.php
   Módulo: Compras / Recepción

   RESPONSABILIDAD:
   Registrar la mercadería recibida y coordinarla con Inventario.

   BASES UTILIZADAS:
   - compr → recepción, proveedor y compra externa.
   - cate  → validar productos y actualizar pcomp.
   - inve  → validar almacén y actualizar stock/movimiento.

   INTEGRIDAD P5:
   Cada base mantiene su propia transacción.
   Una recepción queda PENDIENTE hasta que todas las bases
   confirman su parte. PENDIENTE es el estado recuperable.
============================================================ */
declare(strict_types=1);

final class ReceRepo
{
    public function __construct(private PDO $db, private PDO $cateDb, private PDO $inveDb) {}

    public function proveedores(): array
    {
        return $this->db->query('SELECT id, razon, ruc FROM prove WHERE estad=1 ORDER BY razon,id')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function productos(): array
    {
        return $this->cateDb->query('SELECT id,descr,codig,origi FROM produ ORDER BY descr,id')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function almacenes(): array
    {
        return $this->inveDb->query('SELECT id,nombr FROM almac WHERE estad=1 ORDER BY nombr,id')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function comex(): array
    {
        return $this->db->query("SELECT o.id,o.prove,p.razon,o.estad,o.total FROM comex o INNER JOIN prove p ON p.id=o.prove WHERE o.estad IN ('emitida','parcial','borrador') ORDER BY o.id DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar(string $buscar='',int $limite=50): array
    {
        $limite=max(1,min($limite,100));
        $buscar=trim($buscar);
        $almIds=[];
        $sql='SELECT r.id,r.comex,r.prove,r.almac,r.estad,r.fecha,r.obser,p.razon
              FROM recep r
              INNER JOIN prove p ON p.id=r.prove';
        $params=[];
        $cond=[];

        if($buscar!==''){
            $wa=$this->inveDb->prepare('SELECT id FROM almac WHERE nombr LIKE :busc ORDER BY nombr,id LIMIT 500');
            $wa->execute(['busc'=>'%'.$buscar.'%']);
            $almIds=array_map('intval',$wa->fetchAll(PDO::FETCH_COLUMN));

            $cond[]='(p.razon LIKE :proveedor OR r.estad LIKE :estad OR CAST(r.id AS CHAR) LIKE :id';
            $params['proveedor']='%'.$buscar.'%';
            $params['estad']='%'.$buscar.'%';
            $params['id']='%'.$buscar.'%';
            if($almIds){
                $ph=[];
                foreach($almIds as $i=>$aid){$k='alm'.$i;$ph[]=':'.$k;$params[$k]=$aid;}
                $cond[]='OR r.almac IN ('.implode(',',$ph).')';
            }
            $cond[] = ')';
        }

        if($cond)$sql.=' WHERE '.implode(' AND ',$cond);
        $sql.=" ORDER BY r.id DESC LIMIT {$limite}";
        $st=$this->db->prepare($sql);
        foreach($params as $n=>$v){$st->bindValue(':'.$n,$v,is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR);}
        $st->execute();
        $rows=$st->fetchAll(PDO::FETCH_ASSOC);

        if(!$rows)return [];

        $ids=array_values(array_unique(array_map(static fn($r)=>(int)$r['almac'],$rows)));
        $nom=[];
        if($ids){
            $ph=[];$pp=[];
            foreach($ids as $i=>$aid){$k='a'.$i;$ph[]=':'.$k;$pp[$k]=$aid;}
            $wa=$this->inveDb->prepare('SELECT id,nombr FROM almac WHERE id IN ('.implode(',',$ph).')');
            $wa->execute($pp);
            foreach($wa->fetchAll(PDO::FETCH_ASSOC) as $a){$nom[(int)$a['id']]=$a['nombr'];}
        }
        foreach($rows as &$r){$r['almacen']=$nom[(int)$r['almac']]??'';}
        unset($r);
        return $rows;
    }

    public function crear(array $datos): array
    {
        $proveedor=(int)($datos['prove']??0);
        $comex=(int)($datos['comex']??0);
        $alma=(int)($datos['almac']??0);
        $det=json_decode((string)($datos['detalles']??'[]'),true);
        if($proveedor<=0||$alma<=0||!is_array($det)||$det===[])throw new InvalidArgumentException('Proveedor, almacén y productos son obligatorios.');

        $st=$this->db->prepare('SELECT id FROM prove WHERE id=:id AND estad=1');
        $st->execute(['id'=>$proveedor]);
        if(!$st->fetch())throw new InvalidArgumentException('El proveedor no existe o está inactivo.');

        $wa=$this->inveDb->prepare('SELECT id FROM almac WHERE id=:id AND estad=1');
        $wa->execute(['id'=>$alma]);
        if(!$wa->fetch())throw new InvalidArgumentException('El almacén no existe o está inactivo.');

        if($comex>0){
            $qo=$this->db->prepare('SELECT id,prove,estad FROM comex WHERE id=:id');
            $qo->execute(['id'=>$comex]);
            $o=$qo->fetch();
            if(!$o)throw new InvalidArgumentException('La compra externa no existe.');
            if((int)$o['prove']!==$proveedor)throw new InvalidArgumentException('El proveedor no corresponde a la compra.');
            if(in_array((string)$o['estad'],['recibida','anulada'],true))throw new InvalidArgumentException('La compra externa ya no admite recepción.');
        }

        $limpios=[];
        foreach($det as $d){
            $prod=(int)($d['produ']??0);
            $canti=(float)($d['canti']??0);
            $costo=(float)($d['costu']??0);
            if($prod<=0||$canti<=0||$costo<0)throw new InvalidArgumentException('Detalle de recepción no válido.');
            $qp=$this->cateDb->prepare('SELECT id FROM produ WHERE id=:id');
            $qp->execute(['id'=>$prod]);
            if(!$qp->fetch())throw new InvalidArgumentException("El producto {$prod} no existe en Catálogo.");
            $limpios[]=['produ'=>$prod,'canti'=>$canti,'costo'=>$costo];
        }

        $this->db->beginTransaction();
        try{
            $st=$this->db->prepare('INSERT INTO recep (comex,prove,almac,estad,obser) VALUES (:comex,:proveedor,:almac,\'pendiente\',:obs)');
            $st->execute([
                'comex'=>$comex>0?$comex:null,
                'proveedor'=>$proveedor,
                'almac'=>$alma,
                'obs'=>trim((string)($datos['obser']??''))!==''?trim((string)$datos['obser']):null
            ]);
            $id=(int)$this->db->lastInsertId();
            $sd=$this->db->prepare('INSERT INTO detre (recep,produ,canti,costu) VALUES (:recep,:produ,:canti,:costo)');
            foreach($limpios as $d){
                $sd->execute(['recep'=>$id,'produ'=>$d['produ'],'canti'=>$d['canti'],'costo'=>$d['costo']]);
            }
            $this->db->commit();
        }catch(Throwable $e){
            if($this->db->inTransaction())$this->db->rollBack();
            throw $e;
        }

        return $this->apli($id);
    }

    public function reint(int $id): array
    {
        if($id<=0)throw new InvalidArgumentException('Recepción no válida.');
        return $this->apli($id);
    }

    private function apli(int $id): array
    {
        $inv=false;
        $cat=false;
        $this->db->beginTransaction();
        try{
            $st=$this->db->prepare('SELECT id,comex,prove,almac,estad FROM recep WHERE id=:id FOR UPDATE');
            $st->execute(['id'=>$id]);
            $rec=$st->fetch(PDO::FETCH_ASSOC);
            if(!$rec)throw new InvalidArgumentException('La recepción no existe.');
            if((string)$rec['estad']==='aplicada'){
                $this->db->commit();
                return ['id'=>$id,'estad'=>'aplicada','inv'=>false,'cate'=>false,'comx'=>((int)$rec['comex']>0),'mensaje'=>'La recepción ya estaba aplicada.'];
            }
            if((string)$rec['estad']==='anulada')throw new InvalidArgumentException('La recepción está anulada.');

            $det=$this->db->prepare('SELECT produ,canti,costu FROM detre WHERE recep=:id ORDER BY id');
            $det->execute(['id'=>$id]);
            $lista=$det->fetchAll(PDO::FETCH_ASSOC);
            if(!$lista)throw new InvalidArgumentException('La recepción no tiene detalles.');

            $wa=$this->inveDb->prepare('SELECT id FROM almac WHERE id=:id AND estad=1');
            $wa->execute(['id'=>(int)$rec['almac']]);
            if(!$wa->fetch())throw new InvalidArgumentException('El almacén ya no está disponible.');

            foreach($lista as $d){
                $qp=$this->cateDb->prepare('SELECT id FROM produ WHERE id=:id');
                $qp->execute(['id'=>(int)$d['produ']]);
                if(!$qp->fetch())throw new InvalidArgumentException('Un producto de la recepción ya no existe en Catálogo.');
            }

            $this->inveDb->beginTransaction();
            try{
                $motvo='Recepción de compra #'.$id;
                $ck=$this->inveDb->prepare("SELECT id FROM movim WHERE produ=:produ AND almac=:almac AND tipo='entrada' AND canti=:canti AND motvo=:motvo LIMIT 1");
                $up=$this->inveDb->prepare('INSERT INTO stock (produ,almac,canti,stmin,stmax) VALUES (:produ,:almac,:cantidad_insert,0,0) ON DUPLICATE KEY UPDATE canti=canti+:cantidad_update');
                $mv=$this->inveDb->prepare("INSERT INTO movim (produ,almac,tipo,canti,motvo) VALUES (:produ,:almac,'entrada',:canti,:motvo)");
                foreach($lista as $d){
                    $ck->execute(['produ'=>(int)$d['produ'],'almac'=>(int)$rec['almac'],'canti'=>$d['canti'],'motvo'=>$motvo]);
                    if($ck->fetch())continue;
                    $up->execute(['produ'=>(int)$d['produ'],'almac'=>(int)$rec['almac'],'cantidad_insert'=>$d['canti'],'cantidad_update'=>$d['canti']]);
                    $mv->execute(['produ'=>(int)$d['produ'],'almac'=>(int)$rec['almac'],'canti'=>$d['canti'],'motvo'=>$motvo]);
                }
                $this->inveDb->commit();
                $inv=true;
            }catch(Throwable $e){
                if($this->inveDb->inTransaction())$this->inveDb->rollBack();
                throw $e;
            }

            $this->cateDb->beginTransaction();
            try{
                $pc=$this->cateDb->prepare('UPDATE produ SET pcomp=:pcomp, fecha=CURRENT_TIMESTAMP WHERE id=:id');
                foreach($lista as $d){
                    $pc->execute(['pcomp'=>$d['costu'],'id'=>(int)$d['produ']]);
                }
                $this->cateDb->commit();
                $cat=true;
            }catch(Throwable $e){
                if($this->cateDb->inTransaction())$this->cateDb->rollBack();
                throw $e;
            }

            $up=$this->db->prepare("UPDATE recep SET estad='aplicada' WHERE id=:id AND estad='pendiente'");
            $up->execute(['id'=>$id]);
            if($up->rowCount()<1)throw new RuntimeException('No se pudo cerrar el estado de la recepción.');
            $comx=false;
            if((int)$rec['comex']>0){
                $cx=$this->db->prepare('SELECT estad FROM comex WHERE id=:id FOR UPDATE');
                $cx->execute(['id'=>(int)$rec['comex']]);
                $cs=(string)$cx->fetchColumn();
                if($cs===''||$cs==='anulada')throw new RuntimeException('La compra externa ya no puede cerrarse.');
                if($cs!=='recibida')$this->db->prepare("UPDATE comex SET estad='recibida' WHERE id=:id")->execute(['id'=>(int)$rec['comex']]);
                $comx=true;
            }
            $this->db->commit();
            return ['id'=>$id,'estad'=>'aplicada','inv'=>$inv,'cate'=>$cat,'comx'=>$comx,'mensaje'=>'Recepción registrada y aplicada a Inventario.'];
        }catch(Throwable $e){
            if($this->db->inTransaction())$this->db->rollBack();
            $this->anot($id,'PENDIENTE: '.$e->getMessage());
            return [
                'id'=>$id,
                'estad'=>'pendiente',
                'inv'=>$inv,
                'cate'=>$cat,
                'comx'=>false,
                'mensaje'=>"La recepción #{$id} quedó pendiente y puede reintentarse."
            ];
        }
    }

    private function anot(int $id,string $msj): void
    {
        try{
            $st=$this->db->prepare('SELECT COALESCE(obser,\'\') FROM recep WHERE id=:id');
            $st->execute(['id'=>$id]);
            $ant=(string)$st->fetchColumn();
            $msj=mb_substr($msj,0,280);
            $txt=trim($ant)===''?$msj:mb_substr($ant.' | '.$msj,0,300);
            $up=$this->db->prepare('UPDATE recep SET obser=:obser WHERE id=:id AND estad=\'pendiente\'');
            $up->execute(['obser'=>$txt,'id'=>$id]);
        }catch(Throwable){
            /* El estado pendiente es la recuperación; el detalle de error no debe romperla. */
        }
    }
}
