<?php
/* ============================================================
   Ruta: modul/categ/repor/audit/audit-repos.php
   Módulo: Reportes / Auditoría

   RESPONSABILIDAD:
   Consultar el historial almacenado en repo.audit.
============================================================ */
declare(strict_types=1);

final class AudiRepo
{
    public function __construct(private PDO $db) {}

    public function listar(string $buscar = '', int $limite = 50): array
    {
        $limite = max(1, min($limite, 100));
        $buscar = trim($buscar);

        $sql = 'SELECT id,usuar,modulo,accion,referencia,detalle,fecha FROM audit';
        $params = [];

        if ($buscar !== '') {
            $sql .= ' WHERE modulo LIKE :buscar OR accion LIKE :buscar OR referencia LIKE :buscar OR detalle LIKE :buscar';
            $params['buscar'] = '%' . $buscar . '%';
        }

        $sql .= " ORDER BY id DESC LIMIT {$limite}";

        

        $st = $this->db->prepare($sql);
        foreach ($params as $n => $v) {
            $st->bindValue(':' . $n, $v, PDO::PARAM_STR);
        }
        $st->execute();

        $r = $st->fetchAll(PDO::FETCH_ASSOC);
        
        return $r;
    }
}
