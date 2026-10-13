<?php
/* ============================================================
   Ruta: modul/categ/inven/ordco/ordco-servi.php
   Módulo: Inventario / Orden interna / Servicio
   Función: Validar almacenes y tiendas antes de crear traslados.
============================================================ */
declare(strict_types=1);
final class OrdcoServ
{
    public function __construct(private OrdcoRepo $repo,private PDO $db,private PDO $log) {}
    public function opciones(int $tiend=0): array { $tiendas=$this->repo->tiendas($this->log);$almac=$this->repo->almac($tiend);return ['tiendas'=>$tiendas,'almacenes'=>$almac,'productos'=>$this->repo->produ()]; }
    public function listar(int $tiend,string $busc,int $limi): array { if($tiend>0)$this->tienda($tiend);return $this->repo->lista($tiend,$busc,$limi); }
    public function crear(int $orig,int $dest,array $deto,string $obse): int { $a=$this->almac($orig);$b=$this->almac($dest);if((int)$a['id']===(int)$b['id'])throw new InvalidArgumentException('El origen y destino deben ser diferentes.');return $this->repo->crear($orig,$dest,$deto,$obse); }
    private function almac(int $id): array {if($id<=0)throw new InvalidArgumentException('Almacén no válido.');$st=$this->db->prepare('SELECT id,tiend,nombr,prin,estad FROM almac WHERE id=:id LIMIT 1');$st->execute(['id'=>$id]);$a=$st->fetch(PDO::FETCH_ASSOC);if(!$a)throw new InvalidArgumentException('El almacén no existe.');if((int)$a['estad']!==1)throw new InvalidArgumentException('El almacén está inactivo.');$this->tienda((int)$a['tiend']);return $a;}
    private function tienda(int $id): array {$st=$this->log->prepare('SELECT id,activ FROM tiend WHERE id=:id LIMIT 1');$st->execute(['id'=>$id]);$t=$st->fetch(PDO::FETCH_ASSOC);if(!$t)throw new InvalidArgumentException('La tienda no existe.');if((int)$t['activ']!==1)throw new InvalidArgumentException('La tienda está inactiva.');return $t;}
}
