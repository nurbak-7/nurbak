<?php
/* ============================================================
   Ruta: modul/categ/inven/almac/almac-servi.php
   Módulo: Inventario / Almacenes / Servicio

   RESPONSABILIDAD:
   Aplicar las reglas TIENDA → ALMACÉN.
   LOGIN valida la tienda; INVEN guarda el almacén.
============================================================ */

declare(strict_types=1);

final class AlmaServ
{
    public function __construct(private AlmaRepo $repo, private PDO $db, private PDO $log) {}

    public function listar(int $tiendId, string $buscar, int $limite): array
    {
        if ($tiendId > 0) $this->tienda($tiendId);
        return $this->repo->listar($tiendId, $buscar, $limite);
    }

    public function agregar(int $tiendId, string $nombr, string $descr, string $direc, int $prin = 0): array
    {
        $tienda = $this->tienda($tiendId);
        $nombr = trim($nombr);
        $descr = trim($descr);
        $direc = trim($direc);
        if ($nombr === '') throw new InvalidArgumentException('El nombr del almacén es obligatorio.');
        if (!in_array($prin,[0,1],true)) throw new InvalidArgumentException('Principal no válido.');

        $exist = $this->repo->listar($tiendId, '', 1);
        if ($exist === []) $prin = 1;

        $this->db->beginTransaction();
        try {
            if ($prin === 1) $this->repo->quitarPrincipal($tiendId);
            $id = $this->repo->crear($tiendId,$nombr,$descr,$direc,$prin);
            $this->db->commit();
            return ['almacen'=>$this->repo->porId($id),'tienda'=>$tienda];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function cambiarEstado(int $id, int $estad): array
    {
        $alma = $this->almacen($id);
        if (!in_array($estad,[0,1],true)) throw new InvalidArgumentException('Estado no válido.');
        $tienda = $this->tienda($alma['tiend']);

        if ($estad === 0 && (int)$alma['prin'] === 1 && (int)$tienda['activ'] === 1) {
            throw new InvalidArgumentException('No se puede inactivar el almacén principal de una tienda activa.');
        }
        $this->repo->cambiarEstado($id,$estad);
        return $this->repo->porId($id) ?? $alma;
    }

    public function principal(int $id): array
    {
        $alma = $this->almacen($id);
        $this->tienda($alma['tiend']);
        if ((int)$alma['estad'] !== 1) throw new InvalidArgumentException('El almacén debe estar activo para ser principal.');
        $this->db->beginTransaction();
        try {
            $this->repo->quitarPrincipal((int)$alma['tiend']);
            $this->repo->cambiarPrincipal($id,1);
            $this->db->commit();
            return $this->repo->porId($id) ?? $alma;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function tiendas(): array
    {
        return $this->log->query('SELECT id,codig,nombr,activ FROM tiend ORDER BY nombr,id')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function validarActivo(int $id): array
    {
        $alma = $this->almacen($id);
        $tienda = $this->tienda((int)$alma['tiend']);
        if ((int)$tienda['activ'] !== 1) throw new InvalidArgumentException('La tienda del almacén está inactiva.');
        if ((int)$alma['estad'] !== 1) throw new InvalidArgumentException('El almacén está inactivo.');
        return $alma + ['tienda'=>$tienda];
    }

    private function almacen(int $id): array
    {
        if ($id <= 0) throw new InvalidArgumentException('Almacén no válido.');
        $r = $this->repo->porId($id);
        if (!$r) throw new InvalidArgumentException('El almacén no existe.');
        return $r;
    }

    private function tienda(int $id): array
    {
        if ($id <= 0) throw new InvalidArgumentException('Tienda no válida.');
        $st = $this->log->prepare('SELECT id,codig,nombr,activ FROM tiend WHERE id=:id LIMIT 1');
        $st->execute(['id'=>$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if (!$r) throw new InvalidArgumentException('La tienda no existe.');
        return $r;
    }
}
