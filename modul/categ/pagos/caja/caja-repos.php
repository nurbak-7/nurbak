<?php
/* ============================================================
   Ruta: modul/categ/pagos/caja/caja-repos.php
   Módulo: Pagos / Caja

   RESPONSABILIDAD:
   Consultar exclusivamente la información propia del submódulo.
   Las relaciones con Ventas, Facturación y Logi son lógicas.
============================================================ */
declare(strict_types=1);

final class CajaRepo
{
    public function __construct(private PDO $db) {}

    public function listar(string $buscar = '', int $limite = 50): array
    {
        $limite = max(1, min($limite, 100));
        $buscar = trim($buscar);

        $sql = 'SELECT id,usuario_apertura_id,fecha_apertura,saldo_inicial,usuario_cierre_id,fecha_cierre,saldo_cierre,estad FROM caja';
        $params = [];

        if ($buscar !== '') {
            $sql .= ' WHERE estad LIKE :buscar';
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
