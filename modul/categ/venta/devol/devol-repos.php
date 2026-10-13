<?php
declare(strict_types=1);

final class DevoRepo
{
    public function __construct(private PDO $db,private PDO $inveDb) {}

    public function clientes(): array
    {
        return $this->db->query("SELECT id,razon,docum FROM clien WHERE estad=1 ORDER BY razon,id")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function almacenes(): array
    {
        return $this->inveDb->query("SELECT id,nombr FROM almac WHERE estad=1 ORDER BY nombr,id")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar(string $buscar='',int $limite=50): array
    {
        $limite=max(1,min($limite,100));
        $buscar=trim($buscar);
        $sql='SELECT d.id,d.opera,d.clien,d.almac,d.estad,d.fecha,d.motvo,c.razon
              FROM devol d INNER JOIN clien c ON c.id=d.clien';
        $params=[];
        $cond=[];

        if($buscar!==''){
            $wa=$this->inveDb->prepare('SELECT id FROM almac WHERE nombr LIKE :busc ORDER BY nombr,id LIMIT 500');
            $wa->execute(['busc'=>'%'.$buscar.'%']);
            $aids=array_map('intval',$wa->fetchAll(PDO::FETCH_COLUMN));
            $cond[]='(c.razon LIKE :cliente OR d.estad LIKE :estad OR CAST(d.id AS CHAR) LIKE :id';
            $params['cliente']='%'.$buscar.'%';$params['estad']='%'.$buscar.'%';$params['id']='%'.$buscar.'%';
            if($aids){
                $ph=[];foreach($aids as $i=>$aid){$k='alm'.$i;$ph[]=':'.$k;$params[$k]=$aid;}
                $cond[]='OR d.almac IN ('.implode(',',$ph).')';
            }
            $cond[]=')';
        }
        if($cond)$sql.=' WHERE '.implode(' AND ',$cond);
        $sql.=" ORDER BY d.id DESC LIMIT {$limite}";
        $st=$this->db->prepare($sql);
        foreach($params as $n=>$v)$st->bindValue(':'.$n,$v,is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR);
        $st->execute();
        $rows=$st->fetchAll(PDO::FETCH_ASSOC);
        if(!$rows)return [];

        $ids=array_values(array_unique(array_map(static fn($r)=>(int)$r['almac'],$rows)));
        $nom=[];
        if($ids){
            $ph=[];$pp=[];foreach($ids as $i=>$id){$k='a'.$i;$ph[]=':'.$k;$pp[$k]=$id;}
            $wa=$this->inveDb->prepare('SELECT id,nombr FROM almac WHERE id IN ('.implode(',',$ph).')');$wa->execute($pp);
            foreach($wa->fetchAll(PDO::FETCH_ASSOC) as $a)$nom[(int)$a['id']]=$a['nombr'];
        }
        foreach($rows as &$r)$r['almacen']=$nom[(int)$r['almac']]??'';
        unset($r);
        return $rows;
    }
}
