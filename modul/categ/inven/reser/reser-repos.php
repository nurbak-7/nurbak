<?php
declare(strict_types=1);

/* ============================================================
   Módulo: Inventario / Reservas
   Responsabilidad exclusiva: INVEN.reser.
============================================================ */
final class ReseR
{
    private const VIGE = 60;
    public function __construct(private PDO $db) {}

    public function expirar(): int
    {
        $st=$this->db->prepare(
            "UPDATE reser SET estad='liberada'
             WHERE estad='reservada'
               AND actua < (NOW() - INTERVAL ".self::VIGE." SECOND)"
        );
        $st->execute();
        return $st->rowCount();
    }

    public function estado(int $almac, array $ids, int $exclOpera=0): array
    {
        $ids=array_values(array_unique(array_filter(array_map('intval',$ids),fn($v)=>$v>0)));
        if ($almac<=0 || $ids===[]) return [];
        $ph=[];$par=['almac'=>$almac];
        foreach($ids as $i=>$id){$k='p'.$i;$ph[]=':'.$k;$par[$k]=$id;}
        $lis=implode(',',$ph);

        $qs=$this->db->prepare("SELECT produ,COALESCE(canti,0) canti FROM stock WHERE almac=:almac AND produ IN ({$lis})");
        $qs->execute($par);$sto=[];
        foreach($qs->fetchAll(PDO::FETCH_ASSOC) as $r)$sto[(int)$r['produ']]=(float)$r['canti'];

        $sql="SELECT produ,COALESCE(SUM(canti),0) reser FROM reser
              WHERE almac=:almac AND estad='reservada'
                AND actua >= (NOW() - INTERVAL ".self::VIGE." SECOND)
                AND produ IN ({$lis})";
        if($exclOpera>0)$sql.=' AND opera<>:opera';
        $qr=$this->db->prepare($sql); if($exclOpera>0)$par['opera']=$exclOpera;
        $qr->execute($par);$res=[];
        foreach($qr->fetchAll(PDO::FETCH_ASSOC) as $r)$res[(int)$r['produ']]=(float)$r['reser'];

        $out=[];
        foreach($ids as $id){
            $fis=$sto[$id]??0.0;$rv=$res[$id]??0.0;
            $out[]=['produ'=>$id,'canti'=>$fis,'reser'=>$rv,'dispo'=>max(0.0,$fis-$rv)];
        }
        return $out;
    }

    public function sincronizar(int $opera,int $almac,array $detalles): float
    {
        if($opera<=0||$almac<=0)throw new InvalidArgumentException('Operación o almacén no válido.');
        $this->db->beginTransaction();
        try{
            $this->expirar();
            $q=$this->db->prepare("SELECT id,produ,canti,estad FROM reser WHERE opera=:opera AND almac=:almac FOR UPDATE");
            $q->execute(['opera'=>$opera,'almac'=>$almac]);$act=[];
            foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r)$act[(int)$r['produ']]=$r;
            $ids=[];foreach($detalles as $d)$ids[(int)$d['produ']]=(int)$d['produ'];
            $ids=array_values(array_filter($ids,fn($v)=>$v>0));
            $total=0.0;
            foreach($ids as $prod){
                $sol=0.0;foreach($detalles as $d)if((int)$d['produ']===$prod)$sol+=(float)$d['canti'];
                $qs=$this->db->prepare('SELECT id,canti FROM stock WHERE produ=:produ AND almac=:almac FOR UPDATE');
                $qs->execute(['produ'=>$prod,'almac'=>$almac]);$st=$qs->fetch(PDO::FETCH_ASSOC);
                $fis=(float)($st['canti']??0);$qo=$this->db->prepare(
                    "SELECT COALESCE(SUM(canti),0) FROM reser WHERE produ=:produ AND almac=:almac
                     AND estad='reservada' AND actua >= (NOW() - INTERVAL ".self::VIGE." SECOND) AND opera<>:opera"
                );
                $qo->execute(['produ'=>$prod,'almac'=>$almac,'opera'=>$opera]);
                $otros=(float)$qo->fetchColumn();
                $cant=max(0.0,min($sol,max(0.0,$fis-$otros)));
                if(isset($act[$prod])){
                    if($cant>0){
                        $up=$this->db->prepare("UPDATE reser SET canti=:canti,estad='reservada',actua=NOW() WHERE id=:id");
                        $up->execute(['canti'=>$cant,'id'=>(int)$act[$prod]['id']]);
                    }else{
                        $up=$this->db->prepare("UPDATE reser SET estad='liberada',actua=NOW() WHERE id=:id");
                        $up->execute(['id'=>(int)$act[$prod]['id']]);
                    }
                }elseif($cant>0){
                    $in=$this->db->prepare("INSERT INTO reser(opera,produ,almac,canti,estad) VALUES(:opera,:produ,:almac,:canti,'reservada')");
                    $in->execute(['opera'=>$opera,'produ'=>$prod,'almac'=>$almac,'canti'=>$cant]);
                }
                $total+=$cant;
            }
            foreach($act as $prod=>$r)if(!in_array($prod,$ids,true)){
                $this->db->prepare("UPDATE reser SET estad='liberada',actua=NOW() WHERE id=:id AND estad='reservada'")->execute(['id'=>(int)$r['id']]);
            }
            $this->db->commit();return $total;
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function liberar(int $opera): void
    {
        $this->db->prepare("UPDATE reser SET estad='liberada',actua=NOW() WHERE opera=:opera AND estad='reservada'")->execute(['opera'=>$opera]);
    }

    public function consumir(int $opera): void
    {
        $this->db->prepare("UPDATE reser SET estad='consumida',actua=NOW() WHERE opera=:opera AND estad='reservada'")->execute(['opera'=>$opera]);
    }

    public function activas(int $opera): float
    {
        $st=$this->db->prepare("SELECT COALESCE(SUM(canti),0) FROM reser WHERE opera=:opera AND estad='reservada' AND actua >= (NOW() - INTERVAL ".self::VIGE." SECOND)");
        $st->execute(['opera'=>$opera]);return (float)$st->fetchColumn();
    }
}
