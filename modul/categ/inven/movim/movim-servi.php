<?php
/* ============================================================
   Ruta: modul/categ/inven/movim/movim-servi.php
   Módulo: Inventario / Movimientos / Servicio
   Función: Validar TIENDA → ALMACÉN antes del movimiento.
============================================================ */
declare(strict_types=1);
final class MoviServ
{
    public function __construct(private MoviRepo $repo, private PDO $log) {}
    public function listar(int $tiend,int $almac,string $buscar,int $limite): array { $this->validarFiltros($tiend,$almac);return $this->repo->listar($tiend,$almac,$buscar,$limite); }
    public function opciones(int $tiend): array { if($tiend>0)$this->tienda($tiend);return $this->repo->opciones($tiend); }
    public function registrar(int $tiend,int $prod,int $almac,string $tipo,float $canti,string $motvo): int { $this->validarAlmac($tiend,$almac);return $this->repo->registrar($prod,$almac,$tipo,$canti,$motvo); }
    private function validarFiltros(int $tiend,int $almac): void {if($tiend>0)$this->tienda($tiend);if($almac>0)$this->validarAlmac($tiend,$almac);}
    private function validarAlmac(int $tiend,int $id): array {if($id<=0)throw new InvalidArgumentException('Almacén no válido.');$r=$this->repo->porAlmac($id);if(!$r)throw new InvalidArgumentException('El almacén no existe o está inactivo.');if((int)$r['estad']!==1)throw new InvalidArgumentException('El almacén está inactivo.');if($tiend>0&&(int)$r['tiend']!==$tiend)throw new InvalidArgumentException('El almacén no pertenece a la tienda seleccionada.');$this->tienda((int)$r['tiend']);return $r;}
    private function tienda(int $id): array {$st=$this->log->prepare('SELECT id,activ FROM tiend WHERE id=:id');$st->execute(['id'=>$id]);$r=$st->fetch(PDO::FETCH_ASSOC);if(!$r)throw new InvalidArgumentException('La tienda no existe.');if((int)$r['activ']!==1)throw new InvalidArgumentException('La tienda está inactiva.');return $r;}
}
