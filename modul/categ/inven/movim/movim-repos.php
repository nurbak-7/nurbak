<?php
/* ============================================================
   Ruta: modul/categ/inven/movim/movim-repos.php
   Módulo: Inventario / Movimientos
   Función: Persistencia de movimientos y actualización de stock.

   REGLA:
   salss acumula demanda sin stock; nunca vuelve negativo canti.
============================================================ */
declare(strict_types=1);
final class MoviRepo
{
    public function __construct(private PDO $db) {}
    public function listar(int $tiend=0,int $almac=0,string $buscar='',int $limite=50): array
    { $limite=max(1,min($limite,100));$buscar=trim($buscar);$par=[];$sql="SELECT m.id,m.produ,p.descr,p.codig,p.origi,m.almac,a.tiend,a.nombr AS almacen,m.tipo,m.canti,m.motvo,m.fecha FROM movim m INNER JOIN almac a ON a.id=m.almac INNER JOIN catal.produ p ON p.id=m.produ";$c=[];if($tiend>0){$c[]='a.tiend=:tiend';$par['tiend']=$tiend;}if($almac>0){$c[]='a.id=:almac';$par['almac']=$almac;}if($buscar!==''){$c[]='(CAST(m.id AS CHAR) LIKE :id OR CAST(m.produ AS CHAR) LIKE :prod OR p.descr LIKE :descr OR p.codig LIKE :codig OR p.origi LIKE :origi OR a.nombr LIKE :almacn OR m.tipo LIKE :tipo OR m.motvo LIKE :motvo)';$v="%{$buscar}%";$par+=['id'=>$v,'prod'=>$v,'descr'=>$v,'codig'=>$v,'origi'=>$v,'almacn'=>$v,'tipo'=>$v,'motvo'=>$v];}if($c)$sql.=' WHERE '.implode(' AND ',$c);$sql.=" ORDER BY m.id DESC LIMIT {$limite}";$st=$this->db->prepare($sql);$st->execute($par);return $st->fetchAll(PDO::FETCH_ASSOC); }
    public function porAlmac(int $id): ?array { $st=$this->db->prepare('SELECT id,tiend,nombr,prin,estad FROM almac WHERE id=:id LIMIT 1');$st->execute(['id'=>$id]);$r=$st->fetch(PDO::FETCH_ASSOC);return $r?:null; }
    public function opciones(int $tiend=0): array { $p=$this->db->query('SELECT id,descr,codig,origi FROM catal.produ ORDER BY descr,id LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);$sql='SELECT id,tiend,nombr,prin FROM almac WHERE estad=1';$par=[];if($tiend>0){$sql.=' AND tiend=:tiend';$par['tiend']=$tiend;}$sql.=' ORDER BY tiend,prin DESC,nombr,id';$st=$this->db->prepare($sql);$st->execute($par);return ['productos'=>$p,'almacenes'=>$st->fetchAll(PDO::FETCH_ASSOC)]; }
    public function registrar(int $prod,int $almac,string $tipo,float $canti,string $motvo): int
    { if($prod<=0||$almac<=0)throw new InvalidArgumentException('Producto o almacén no válido.');if(!in_array($tipo,['entrada','salida','ajuste','salss'],true))throw new InvalidArgumentException('Tipo de movimiento no válido.');if($canti<=0)throw new InvalidArgumentException('La canti debe ser mayor que cero.');$this->db->beginTransaction();try{$st=$this->db->prepare('SELECT id,canti,salss FROM stock WHERE produ=:produ AND almac=:almac FOR UPDATE');$st->execute(['produ'=>$prod,'almac'=>$almac]);$stock=$st->fetch(PDO::FETCH_ASSOC);if(!$stock){$ir=$this->db->prepare('INSERT INTO stock(produ,almac,canti,salss) VALUES(:produ,:almac,0,0)');$ir->execute(['produ'=>$prod,'almac'=>$almac]);$stock=['id'=>(int)$this->db->lastInsertId(),'canti'=>0,'salss'=>0];}$fis=(float)$stock['canti'];$dem=(float)$stock['salss'];if($tipo==='entrada'){$nuevo=$fis+$canti;}elseif($tipo==='salida'){$nuevo=$fis-$canti;if($nuevo<0)throw new InvalidArgumentException('Stock insuficiente para realizar la salida.');}elseif($tipo==='salss'){if($fis>0)throw new InvalidArgumentException('La salida sin stock solo puede registrarse cuando el stock físico es 0.');$nuevo=0;$dem+=$canti;}else{$nuevo=$canti;}$ins=$this->db->prepare('INSERT INTO movim(produ,almac,tipo,canti,motvo) VALUES(:produ,:almac,:tipo,:canti,:motvo)');$ins->execute(['produ'=>$prod,'almac'=>$almac,'tipo'=>$tipo,'canti'=>$canti,'motvo'=>trim($motvo)!==''?trim($motvo):null]);$id=(int)$this->db->lastInsertId();$up=$this->db->prepare('UPDATE stock SET canti=:canti,salss=:salss WHERE id=:id');$up->execute(['canti'=>$nuevo,'salss'=>$dem,'id'=>(int)$stock['id']]);$this->db->commit();return $id;}catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}}
}
