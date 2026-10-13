<?php
/* ============================================================
   Ruta: modul/categ/pagos/credi/credi-repos.php
   Módulo: Pagos / Créditos

   RESPONSABILIDAD:
   Consultar exclusivamente la información propia del submódulo.
   Las relaciones con Ventas, Facturación y Logi son lógicas.
============================================================ */
declare(strict_types=1);

final class CredRepo
{
    public function __construct(private PDO $db) {}

    public function listar(string $buscar = '', int $limite = 50): array
    {
        $limite = max(1, min($limite, 100));
        $buscar = trim($buscar);

        $sql = 'SELECT id,opera,documento_tipo,documento_id,clien,monto_inicial,saldo,fecha_inicio,fecha_vencimiento,estad FROM credi';
        $params = [];

        if ($buscar !== '') {
            $sql .= ' WHERE documento_tipo LIKE :buscar OR estad LIKE :buscar';
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
