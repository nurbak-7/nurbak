<?php
/* ============================================================
   Ruta: modul/categ/compr/comex/comex-repos.php
   Módulo: Compras / Compra externa
   Función: Acceso a comex y detex.
============================================================ */
declare(strict_types=1);
final class Comex{public function __construct(private PDO $db,private PDO $cat){}public function prove():array{return $this->db->query("SELECT id,razon,ruc FROM prove WHERE estad=1 ORDER BY razon,id")->fetchAll(PDO::FETCH_ASSOC);}public function produ():array{return $this->cat->query("SELECT id,descr,codig,origi,pcomp FROM produ ORDER BY descr,id LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);}public function lista(string $busc='',int $limi=50):array
    {
        $limi=max(1,min($limi,100));
        $sql="SELECT c.id,c.prove,c.estad,c.fecha,c.obser,c.total,p.razon FROM comex c INNER JOIN prove p ON p.id=c.prove";
        $par=[];
        $busc=trim($busc);
        if($busc!==''){
            $sql.=" WHERE p.razon LIKE :razon OR c.estad LIKE :estad OR CAST(c.id AS CHAR) LIKE :ident";
            $pat="%{$busc}%";
            $par=['razon'=>$pat,'estad'=>$pat,'ident'=>$pat];
        }
        $sql.=" ORDER BY c.id DESC LIMIT {$limi}";
        $st=$this->db->prepare($sql);
        $st->execute($par);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
    public function crear(int $prov,mixed $det,string $obse):int{if($prov<=0||!is_array($det)||$det===[])throw new InvalidArgumentException('Proveedor y productos son obligatorios.');$q=$this->db->prepare('SELECT id FROM prove WHERE id=:id AND estad=1');$q->execute(['id'=>$prov]);if(!$q->fetch())throw new InvalidArgumentException('Proveedor inexistente o inactivo.');$lis=[];$tot=0.0;foreach($det as $d){$pro=(int)($d['produ']??0);$can=(float)($d['canti']??0);$pre=(float)($d['preun']??0);if($pro<=0||$can<=0||$pre<0)throw new InvalidArgumentException('Detalle de compra no válido.');$q=$this->cat->prepare('SELECT id FROM produ WHERE id=:id');$q->execute(['id'=>$pro]);if(!$q->fetch())throw new InvalidArgumentException('Producto inexistente.');$sub=round($can*$pre,2);$tot+=$sub;$lis[]=['produ'=>$pro,'canti'=>$can,'preun'=>$pre,'subto'=>$sub];}$this->db->beginTransaction();try{$q=$this->db->prepare("INSERT INTO comex(prove,estad,obser,total) VALUES(:p,'borrador',:o,:t)");$q->execute(['p'=>$prov,'o'=>$obse!==''?$obse:null,'t'=>$tot]);$id=(int)$this->db->lastInsertId();$q=$this->db->prepare('INSERT INTO detex(comex,produ,canti,preun,subto) VALUES(:x,:p,:c,:u,:s)');foreach($lis as $d)$q->execute(['x'=>$id,'p'=>$d['produ'],'c'=>$d['canti'],'u'=>$d['preun'],'s'=>$d['subto']]);$this->db->commit();return $id;}catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}}
}
