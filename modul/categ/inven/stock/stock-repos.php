<?php
declare(strict_types=1);

final class StocRepo
{
    public function __construct(private PDO $db, private PDO $cat) {}

    public function listar(int $tiendId=0,int $almaId=0,string $buscar='',int $limite=50): array
    {
        $limite=max(1,min($limite,100));
        $buscar=trim($buscar);

        $asql='SELECT id,tiend,nombr,prin FROM almac WHERE estad=1';
        $apar=[];
        if($tiendId>0){$asql.=' AND tiend=:tiend';$apar['tiend']=$tiendId;}
        if($almaId>0){$asql.=' AND id=:almac';$apar['almac']=$almaId;}
        if($buscar!==''){
            $wa=$this->db->prepare('SELECT id FROM almac WHERE estad=1 AND nombr LIKE :busc ORDER BY tiend,prin DESC,nombr,id LIMIT 500');
            $wa->execute(['busc'=>'%'.$buscar.'%']);
            $coA=array_map('intval',$wa->fetchAll(PDO::FETCH_COLUMN));
        }else{
            $coA=[];
        }
        $asql.=' ORDER BY tiend,prin DESC,nombr,id';
        $st=$this->db->prepare($asql);$st->execute($apar);$almAll=$st->fetchAll(PDO::FETCH_ASSOC);

        if($buscar!==''){
            $ps=$this->cat->prepare('SELECT id,descr,codig,origi FROM produ WHERE CAST(id AS CHAR) LIKE :id OR descr LIKE :descr OR codig LIKE :codig OR origi LIKE :origi ORDER BY id DESC LIMIT 100');
            $v='%'.$buscar.'%';$ps->execute(['id'=>$v,'descr'=>$v,'codig'=>$v,'origi'=>$v]);
            $prodMatch=$ps->fetchAll(PDO::FETCH_ASSOC);
            if(!$prodMatch && !$coA)return [];
            $alm=$coA?array_values(array_filter($almAll,static fn($a)=>in_array((int)$a['id'],$coA,true))):$almAll;
            $prod=$prodMatch;
            if(!$prod)$prod=$this->cat->query('SELECT id,descr,codig,origi FROM produ ORDER BY id DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
            if(!$alm)$alm=$almAll;
        }else{
            $alm=$almAll;
            $prod=$this->cat->query('SELECT id,descr,codig,origi FROM produ ORDER BY id DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
        }

        if(!$alm||!$prod)return [];

        $aids=array_map(static fn($x)=>(int)$x['id'],$alm);
        $pids=array_map(static fn($x)=>(int)$x['id'],$prod);
        $phA=[];$pa=[];foreach($aids as $i=>$id){$k='a'.$i;$phA[]=':'.$k;$pa[$k]=$id;}
        $phP=[];$pp=[];foreach($pids as $i=>$id){$k='p'.$i;$phP[]=':'.$k;$pp[$k]=$id;}
        $qs=$this->db->prepare('SELECT produ,almac,canti,salss,stmin,stmax,ultim FROM stock WHERE almac IN ('.implode(',',$phA).') AND produ IN ('.implode(',',$phP).')');
        $qs->execute($pa+$pp);
        $map=[];foreach($qs->fetchAll(PDO::FETCH_ASSOC) as $x){$map[(int)$x['almac'].':'.(int)$x['produ']]=$x;}

        $res=[];
        foreach($alm as $a){
            foreach($prod as $p){
                $k=(int)$a['id'].':'.(int)$p['id'];$x=$map[$k]??[];
                $res[]=[
                    'produ'=>(int)$p['id'],'descr'=>$p['descr'],'codig'=>$p['codig'],'origi'=>$p['origi'],
                    'almac'=>(int)$a['id'],'tiend'=>(int)$a['tiend'],'almacen'=>$a['nombr'],'prin'=>(int)$a['prin'],
                    'canti'=>(float)($x['canti']??0),'salss'=>(float)($x['salss']??0),
                    'stmin'=>(float)($x['stmin']??0),'stmax'=>(float)($x['stmax']??0),'ultim'=>$x['ultim']??null
                ];
                if(count($res)>=$limite)return $res;
            }
        }
        return $res;
    }

    public function porAlmac(int $id): ?array
    {
        $st=$this->db->prepare('SELECT id,tiend,nombr,prin,estad FROM almac WHERE id=:id LIMIT 1');
        $st->execute(['id'=>$id]);
        $r=$st->fetch(PDO::FETCH_ASSOC);
        return $r?:null;
    }

    public function almacenes(int $tiendId=0): array
    {
        $sql='SELECT id,tiend,nombr,prin FROM almac WHERE estad=1';$par=[];
        if($tiendId>0){$sql.=' AND tiend=:tiend';$par['tiend']=$tiendId;}
        $sql.=' ORDER BY tiend,prin DESC,nombr,id';
        $st=$this->db->prepare($sql);$st->execute($par);return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function productos(string $buscar='',int $limite=50): array
    {
        $limite=max(1,min($limite,100));
        $buscar=trim($buscar);
        $sql='SELECT id,descr,codig,origi FROM produ';$par=[];
        if($buscar!==''){$sql.=' WHERE CAST(id AS CHAR) LIKE :id OR descr LIKE :descr OR codig LIKE :codig OR origi LIKE :origi';$v='%'.$buscar.'%';$par=['id'=>$v,'descr'=>$v,'codig'=>$v,'origi'=>$v];}
        $sql.=" ORDER BY descr,id LIMIT {$limite}";
        $st=$this->cat->prepare($sql);$st->execute($par);return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function asignar(int $prodId,int $almaId,float $minimo,float $maximo): int
    {
        if($prodId<=0)throw new InvalidArgumentException('Producto no válido.');
        $qp=$this->cat->prepare('SELECT id FROM produ WHERE id=:id');$qp->execute(['id'=>$prodId]);
        if(!$qp->fetch())throw new InvalidArgumentException('Producto inexistente en Catálogo.');
        $qa=$this->db->prepare('SELECT id,estad FROM almac WHERE id=:id');$qa->execute(['id'=>$almaId]);$alm=$qa->fetch(PDO::FETCH_ASSOC);
        if(!$alm||(int)$alm['estad']!==1)throw new InvalidArgumentException('Almacén inexistente o inactivo.');
        $st=$this->db->prepare('INSERT INTO stock (produ,almac,canti,salss,stmin,stmax) VALUES (:produ,:almac,0,0,:minimo,:maximo) ON DUPLICATE KEY UPDATE stmin=VALUES(stmin),stmax=VALUES(stmax)');
        $st->execute(['produ'=>$prodId,'almac'=>$almaId,'minimo'=>$minimo,'maximo'=>$maximo]);
        $st=$this->db->prepare('SELECT id FROM stock WHERE produ=:produ AND almac=:almac');$st->execute(['produ'=>$prodId,'almac'=>$almaId]);return (int)$st->fetchColumn();
    }

    public function salida(int $prod,int $almac,float $canti,string $motvo): int
    {
        if($prod<=0||$almac<=0||$canti<=0)throw new InvalidArgumentException('Datos de salida no válidos.');
        $st=$this->db->prepare('SELECT id,canti,salss FROM stock WHERE produ=:produ AND almac=:almac FOR UPDATE');
        $st->execute(['produ'=>$prod,'almac'=>$almac]);$r=$st->fetch(PDO::FETCH_ASSOC);
        if(!$r)throw new InvalidArgumentException('No existe stock para el producto.');
        $fis=(float)$r['canti'];if($fis<$canti)throw new InvalidArgumentException('Stock insuficiente.');
        $mi=$this->db->prepare("INSERT INTO movim(produ,almac,tipo,canti,motvo) VALUES(:produ,:almac,'salida',:canti,:motvo)");$mi->execute(['produ'=>$prod,'almac'=>$almac,'canti'=>$canti,'motvo'=>$motvo]);$mov=(int)$this->db->lastInsertId();
        $this->db->prepare('UPDATE stock SET canti=:canti WHERE id=:id')->execute(['canti'=>$fis-$canti,'id'=>(int)$r['id']]); return $mov;
    }

    public function salss(int $prod,int $almac,float $canti,string $motvo): int
    {
        if($prod<=0||$almac<=0||$canti<=0)throw new InvalidArgumentException('Datos de salss no válidos.');
        $st=$this->db->prepare('SELECT id,canti,salss FROM stock WHERE produ=:produ AND almac=:almac FOR UPDATE');
        $st->execute(['produ'=>$prod,'almac'=>$almac]);$r=$st->fetch(PDO::FETCH_ASSOC);
        if(!$r){$this->db->prepare('INSERT INTO stock(produ,almac,canti,salss) VALUES(:produ,:almac,0,0)')->execute(['produ'=>$prod,'almac'=>$almac]);$id=(int)$this->db->lastInsertId();$fis=0.0;$dem=0.0;}else{$id=(int)$r['id'];$fis=(float)$r['canti'];$dem=(float)$r['salss'];}
        if($fis>0)throw new InvalidArgumentException('La salida sin stock requiere stock físico cero.');
        $mi=$this->db->prepare("INSERT INTO movim(produ,almac,tipo,canti,motvo) VALUES(:produ,:almac,'salss',:canti,:motvo)");$mi->execute(['produ'=>$prod,'almac'=>$almac,'canti'=>$canti,'motvo'=>$motvo]);$mov=(int)$this->db->lastInsertId();
        $this->db->prepare('UPDATE stock SET salss=:salss WHERE id=:id')->execute(['salss'=>$dem+$canti,'id'=>$id]); return $mov;
    }


    public function vincular(array $ids, string $orige, int $docid): void
    {
        $ids=array_values(array_unique(array_filter(array_map('intval',$ids),static fn($v)=>$v>0)));
        if($ids===[]||$docid<=0)return;
        $ph=implode(',',array_fill(0,count($ids),'?'));
        $st=$this->db->prepare("UPDATE movim SET orige=?,docid=? WHERE id IN ({$ph})");
        $st->execute(array_merge([$orige,$docid],$ids));
    }

    public function compensarNota(int $docid): array
    {
        if($docid<=0)throw new InvalidArgumentException('Nota de Venta no válida.');
        $q=$this->db->prepare("SELECT m.id,m.produ,m.almac,m.tipo,m.canti FROM movim m WHERE m.orige='nvent' AND m.docid=:docid AND m.tipo IN ('salida','salss') AND NOT EXISTS (SELECT 1 FROM movim r WHERE r.refid=m.id) ORDER BY m.id ASC FOR UPDATE");
        $q->execute(['docid'=>$docid]);$rows=$q->fetchAll(PDO::FETCH_ASSOC);
        if($rows===[])throw new InvalidArgumentException('Esta nota no tiene movimientos trazables para editar. Para proteger el inventario, no se permite modificar sus productos o cantidades.');
        foreach($rows as $m){
            $st=$this->db->prepare('SELECT id,canti,salss FROM stock WHERE produ=:produ AND almac=:almac FOR UPDATE');
            $st->execute(['produ'=>(int)$m['produ'],'almac'=>(int)$m['almac']]);$r=$st->fetch(PDO::FETCH_ASSOC);
            if(!$r)throw new RuntimeException('No existe la fila de stock necesaria para compensar la nota.');
            $cantidad=(float)$m['canti'];$sid=(int)$r['id'];
            if($m['tipo']==='salida'){
                $this->db->prepare('UPDATE stock SET canti=canti+:canti WHERE id=:id')->execute(['canti'=>$cantidad,'id'=>$sid]);
                $tipo='entrada';
            }else{
                if((float)$r['salss']+0.0001<$cantidad)throw new RuntimeException('La demanda sin stock cambió desde la emisión; no es seguro compensarla automáticamente.');
                $this->db->prepare('UPDATE stock SET salss=salss-:canti WHERE id=:id')->execute(['canti'=>$cantidad,'id'=>$sid]);
                $tipo='ajuste';
            }
            $in=$this->db->prepare("INSERT INTO movim(produ,almac,tipo,canti,motvo,orige,docid,refid) VALUES(:produ,:almac,:tipo,:canti,:motvo,'nvent',:docid,:refid)");
            $in->execute(['produ'=>(int)$m['produ'],'almac'=>(int)$m['almac'],'tipo'=>$tipo,'canti'=>$cantidad,'motvo'=>'Compensación por edición de Nota de Venta','docid'=>$docid,'refid'=>(int)$m['id']]);
        }
        return $rows;
    }
}
