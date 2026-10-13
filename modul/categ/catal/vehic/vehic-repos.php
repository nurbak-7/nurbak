<?php
/* ============================================================
   Ruta: modul/categ/catal/vehic/vehic-repos.php
   Módulo: Catálogo / Vehículos
   Función: Acceso a vehic.

   🔹 NIVEL 1: Buscar/listar.
   🔹 NIVEL 2: Obtener.
   🔹 NIVEL 3: Crear/editar.
   🔹 NIVEL 4: Eliminar.
============================================================ */
declare(strict_types=1);
final class VehiRepo {
    public function __construct(private PDO $db){}
    private function normalizarAnio(string $valor): array {
        $v=trim($valor);
        if(!preg_match('/^(\d{2}|\d{4})-(\d{4})-(\d{2}|\d{4})$/',$v,$m)) throw new InvalidArgumentException('El año debe tener formato 08-2000-12.');
        $model=(int)$m[1]; if($model<100)$model+=2000;
        $desde=(int)$m[2]; $hasta=(int)$m[3]; if($hasta<100)$hasta+=2000;
        if($desde>$hasta) throw new InvalidArgumentException('La generación inicial no puede ser mayor que la final.');
        return [$model,$desde,$hasta];
    }
    private function anioTexto(int $model,int $desde,int $hasta): string { return str_pad((string)($model%100),2,'0',STR_PAD_LEFT).'-'.$desde.'-'.str_pad((string)($hasta%100),2,'0',STR_PAD_LEFT); }
    public function listar(string $buscar='',int $limite=50): array {
        $limite=max(1,min($limite,100));$buscar=trim($buscar);$where=[];$params=[];$words=$buscar===''?[]:preg_split('/\s+/u',$buscar,-1,PREG_SPLIT_NO_EMPTY);
        foreach($words as $i=>$w){$g=[];foreach(['id','motor','model','anio','gdesd','ghast'] as $c){$p="b_{$i}_{$c}";$g[]=in_array($c,['id','anio','gdesd','ghast'],true)?"CAST({$c} AS CHAR) LIKE :{$p}":"{$c} LIKE :{$p}";$params[$p]="%{$w}%";} $where[]='('.implode(' OR ',$g).')';}
        $sql='SELECT id,motor,model,anio,gdesd,ghast FROM vehic'; if($where)$sql.=' WHERE '.implode(' AND ',$where);$sql.=" ORDER BY id DESC LIMIT {$limite}";
        $st=$this->db->prepare($sql);foreach($params as $n=>$v)$st->bindValue(':'.$n,$v,PDO::PARAM_STR);$st->execute();$rows=$st->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as &$r)$r['anio']=$this->anioTexto((int)$r['anio'],(int)$r['gdesd'],(int)$r['ghast']); return $rows;
    }
    public function obtener(int $id): array {$st=$this->db->prepare('SELECT id,motor,model,anio,gdesd,ghast FROM vehic WHERE id=:id');$st->execute(['id'=>$id]);$r=$st->fetch(PDO::FETCH_ASSOC);if(!$r)throw new RuntimeException('El vehículo no existe.');$r['anio']=$this->anioTexto((int)$r['anio'],(int)$r['gdesd'],(int)$r['ghast']);return $r;}
    public function guardar(array $d): int {$id=(int)($d['id']??0);$motor=trim((string)($d['motor']??''));$model=trim((string)($d['model']??''));$anio=trim((string)($d['anio']??''));if($motor===''||$model===''||$anio==='')throw new InvalidArgumentException('Motor, model y año son obligatorios.');[$am,$desde,$hasta]=$this->normalizarAnio($anio);if($id){$st=$this->db->prepare('UPDATE vehic SET motor=:motor,model=:model,anio=:am,gdesd=:desde,ghast=:hasta WHERE id=:id');$st->execute(['motor'=>$motor,'model'=>$model,'am'=>$am,'desde'=>$desde,'hasta'=>$hasta,'id'=>$id]);return $id;} $st=$this->db->prepare('INSERT INTO vehic(motor,model,anio,gdesd,ghast) VALUES(:motor,:model,:am,:desde,:hasta)');$st->execute(['motor'=>$motor,'model'=>$model,'am'=>$am,'desde'=>$desde,'hasta'=>$hasta]);return (int)$this->db->lastInsertId();}
    public function eliminar(int $id): bool {$st=$this->db->prepare('DELETE FROM vehic WHERE id=:id');$st->execute(['id'=>$id]);if(!$st->rowCount())throw new RuntimeException('El vehículo no existe.');return true;}
}
