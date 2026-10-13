<?php
/* ============================================================
   Ruta: modul/categ/factu/ndebi/ndebi-repos.php
   Función: Archivo del sistema.
============================================================ */

declare(strict_types=1);
final class NdebRepo {
    public function __construct(private PDO $db) {}
    public function listar(string $buscar = '', int $limite = 50): array {
        $limite=max(1,min($limite,100)); $buscar=trim($buscar);
        $sql='SELECT * FROM ndebi'; $params=[];
        if($buscar!==''){ $sql.=' WHERE CAST(id AS CHAR) LIKE :b1 OR estad LIKE :b2 OR serie LIKE :b3'; $v='%'.$buscar.'%'; $params=['b1'=>$v,'b2'=>$v,'b3'=>$v]; }
        $sql.=" ORDER BY id DESC LIMIT {$limite}";
        
        $st=$this->db->prepare($sql); foreach($params as $n=>$v) $st->bindValue(':'.$n,$v,PDO::PARAM_STR); $st->execute();
        $r=$st->fetchAll(PDO::FETCH_ASSOC);  return $r;
    }
}
