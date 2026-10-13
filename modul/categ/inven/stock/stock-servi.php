<?php
/* ============================================================
   Ruta: modul/categ/inven/stock/stock-servi.php
   Módulo: Inventario / Stock / Servicio
   Función: Validar TIENDA → ALMACÉN → STOCK.
============================================================ */
declare(strict_types=1);
final class StocServ
{
    public function __construct(private StocRepo $repo, private PDO $log) {}
    public function listar(int $tiend,int $almac,string $buscar,int $limite): array
    { $this->validarFiltros($tiend,$almac); return $this->repo->listar($tiend,$almac,$buscar,$limite); }
    public function opciones(int $tiend): array
    { if($tiend>0)$this->tienda($tiend); return ['productos'=>$this->repo->productos('',100),'almacenes'=>$this->repo->almacenes($tiend),'tiendas'=>$this->tiendas()]; }
    public function asignar(int $tiend,int $prod,int $almac,float $min,float $max): int
    { $this->validarAlmac($tiend,$almac); if($prod<=0)throw new InvalidArgumentException('Producto no válido.'); if($min<0||$max<0)throw new InvalidArgumentException('Los límites no pueden ser negativos.'); if($max>0&&$max<$min)throw new InvalidArgumentException('El stock máximo no puede ser menor al mínimo.'); return $this->repo->asignar($prod,$almac,$min,$max); }
    public function tiendas(): array { return $this->log->query('SELECT id,codig,nombr,activ FROM tiend ORDER BY nombr,id')->fetchAll(PDO::FETCH_ASSOC); }
    private function validarFiltros(int $tiend,int $almac): void { if($tiend>0)$this->tienda($tiend); if($almac>0)$this->validarAlmac($tiend,$almac); }
    private function validarAlmac(int $tiend,int $id): array { if($id<=0)throw new InvalidArgumentException('Almacén no válido.');$r=$this->repo->porAlmac($id);if(!$r)throw new InvalidArgumentException('El almacén no existe.');if($tiend>0&&(int)$r['tiend']!==$tiend)throw new InvalidArgumentException('El almacén no pertenece a la tienda seleccionada.');if((int)$r['estad']!==1)throw new InvalidArgumentException('El almacén está inactivo.');$this->tienda((int)$r['tiend']);return $r; }
    private function tienda(int $id): array { $st=$this->log->prepare('SELECT id,activ FROM tiend WHERE id=:id');$st->execute(['id'=>$id]);$r=$st->fetch(PDO::FETCH_ASSOC);if(!$r)throw new InvalidArgumentException('La tienda no existe.');if((int)$r['activ']!==1)throw new InvalidArgumentException('La tienda está inactiva.');return $r; }
}
