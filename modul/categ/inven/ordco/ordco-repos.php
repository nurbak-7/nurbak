<?php
/* ============================================================
   Ruta: modul/categ/inven/ordco/ordco-repos.php
   Módulo: Inventario / Orden de traslado interna
   Función: Persistencia de órdenes entre almacenes.
============================================================ */
declare(strict_types=1);
final class OrdcoRepo
{
    public function __construct(private PDO $db,private PDO $cat) {}
    public function tiendas(PDO $log): array { return $log->query("SELECT id,codig,nombr,activ FROM tiend WHERE activ=1 ORDER BY nombr,id")->fetchAll(PDO::FETCH_ASSOC); }
    public function almac(int $tiend=0): array { $sql='SELECT id,tiend,nombr,prin FROM almac WHERE estad=1';$p=[];if($tiend>0){$sql.=' AND tiend=:tiend';$p['tiend']=$tiend;}$sql.=' ORDER BY tiend,prin DESC,nombr,id';$st=$this->db->prepare($sql);$st->execute($p);return $st->fetchAll(PDO::FETCH_ASSOC); }
    public function produ(): array { return $this->cat->query('SELECT id,descr,codig,origi FROM produ ORDER BY descr,id LIMIT 200')->fetchAll(PDO::FETCH_ASSOC); }
    public function lista(int $tiend=0,string $busc='',int $limi=50): array { $limi=max(1,min($limi,100));$busc=trim($busc);$sql="SELECT o.id,o.orige,o.desti,o.estad,o.fecha,o.obser,a.nombr AS ori,b.nombr AS des,a.tiend AS tiend_orig,b.tiend AS tiend_dest FROM ordco o INNER JOIN almac a ON a.id=o.orige INNER JOIN almac b ON b.id=o.desti";$p=[];$c=[];if($tiend>0){$c[]='(a.tiend=:tiend OR b.tiend=:tiend)';$p['tiend']=$tiend;}if($busc!==''){$c[]='(a.nombr LIKE :busc OR b.nombr LIKE :busc OR o.estad LIKE :busc OR CAST(o.id AS CHAR) LIKE :busc)';$p['busc']="%{$busc}%";}if($c)$sql.=' WHERE '.implode(' AND ',$c);$sql.=" ORDER BY o.id DESC LIMIT {$limi}";$st=$this->db->prepare($sql);$st->execute($p);return $st->fetchAll(PDO::FETCH_ASSOC); }
    public function crear(int $orig,int $dest,array $deto,string $obse): int { if($orig<=0||$dest<=0||$orig===$dest)throw new InvalidArgumentException('Debe seleccionar almacenes de origen y destino diferentes.');if($deto===[])throw new InvalidArgumentException('La orden necesita al menos un producto.');$lis=[];$q=$this->cat->prepare('SELECT id FROM produ WHERE id=:id');foreach($deto as $d){$pro=(int)($d['produ']??0);$can=(float)($d['canti']??0);if($pro<=0||$can<=0)throw new InvalidArgumentException('Detalle de orden no válido.');$q->execute(['id'=>$pro]);if(!$q->fetch())throw new InvalidArgumentException('Producto inexistente.');$lis[]=['produ'=>$pro,'canti'=>$can];}$this->db->beginTransaction();try{$q=$this->db->prepare("INSERT INTO ordco(orige,desti,estad,obser) VALUES(:o,:d,'borrador',:x)");$q->execute(['o'=>$orig,'d'=>$dest,'x'=>$obse!==''?$obse:null]);$id=(int)$this->db->lastInsertId();$q=$this->db->prepare('INSERT INTO detor(ordco,produ,canti) VALUES(:o,:p,:c)');foreach($lis as $d)$q->execute(['o'=>$id,'p'=>$d['produ'],'c'=>$d['canti']]);$this->db->commit();return $id;}catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}}
}
