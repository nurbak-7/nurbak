<?php
/* ============================================================
   Ruta: modul/categ/inven/almac/almac-repos.php
   Módulo: Inventario / Almacenes
   Función: Persistencia exclusiva de INVEN.almac.

   RELACIÓN:
   TIEND 1:N ALMAC 1:N STOCK
============================================================ */

declare(strict_types=1);

final class AlmaRepo
{
    public function __construct(private PDO $db) {}

    public function listar(int $tiendId = 0, string $buscar = '', int $limite = 50): array
    {
        $limite = max(1, min($limite, 100));
        $buscar = trim($buscar);
        $sql = 'SELECT id, tiend, nombr, descr, direc, estad, prin, fecre
                FROM almac';
        $cond = [];
        $par = [];

        if ($tiendId > 0) {
            $cond[] = 'tiend = :tiend';
            $par['tiend'] = $tiendId;
        }
        if ($buscar !== '') {
            $cond[] = '(nombr LIKE :nombr OR descr LIKE :descr OR direc LIKE :direc)';
            $valor = "%{$buscar}%";
            $par['nombr'] = $valor;
            $par['descr'] = $valor;
            $par['direc'] = $valor;
        }
        if ($cond) $sql .= ' WHERE ' . implode(' AND ', $cond);
        $sql .= " ORDER BY tiend ASC, prin DESC, id DESC LIMIT {$limite}";

        $st = $this->db->prepare($sql);
        $st->execute($par);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $st = $this->db->prepare('SELECT id,tiend,nombr,descr,direc,estad,prin,fecre FROM almac WHERE id=:id LIMIT 1');
        $st->execute(['id'=>$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function crear(int $tiendId, string $nombr, string $descr, string $direc, int $prin): int
    {
        $st = $this->db->prepare(
            'INSERT INTO almac (tiend,nombr,descr,direc,estad,prin)
             VALUES (:tiend,:nombr,:descr,:direc,1,:prin)'
        );
        $st->execute([
            'tiend'=>$tiendId,
            'nombr'=>$nombr,
            'descr'=>$descr !== '' ? $descr : null,
            'direc'=>$direc !== '' ? $direc : null,
            'prin'=>$prin,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function quitarPrincipal(int $tiendId): void
    {
        $st = $this->db->prepare('UPDATE almac SET prin=0 WHERE tiend=:tiend AND prin=1');
        $st->execute(['tiend'=>$tiendId]);
    }

    public function cambiarEstado(int $id, int $estad): bool
    {
        $st = $this->db->prepare('UPDATE almac SET estad=:estad WHERE id=:id');
        $st->execute(['estad'=>$estad,'id'=>$id]);
        return $st->rowCount() > 0;
    }

    public function cambiarPrincipal(int $id, int $prin): bool
    {
        $st = $this->db->prepare('UPDATE almac SET prin=:prin WHERE id=:id');
        $st->execute(['prin'=>$prin,'id'=>$id]);
        return $st->rowCount() > 0;
    }
}
