<?php
/* ============================================================
   Ruta: modul/categ/factu/factu/factu-repos.php
   Función: Archivo del sistema.
============================================================ */

declare(strict_types=1);
final class FactRepo {
    public function __construct(private PDO $db) {}
    public function listar(string $buscar = '', int $limite = 50): array {
        $limite=max(1,min($limite,100)); $buscar=trim($buscar);
        $sql='SELECT * FROM factu'; $params=[];
        if($buscar!==''){ $sql.=' WHERE CAST(id AS CHAR) LIKE :b1 OR estad LIKE :b2 OR serie LIKE :b3'; $v='%'.$buscar.'%'; $params=['b1'=>$v,'b2'=>$v,'b3'=>$v]; }
        $sql.=" ORDER BY id DESC LIMIT {$limite}";
        
        $st=$this->db->prepare($sql); foreach($params as $n=>$v) $st->bindValue(':'.$n,$v,PDO::PARAM_STR); $st->execute();
        $r=$st->fetchAll(PDO::FETCH_ASSOC);  return $r;
    }

    public function numeracion(): array
    {
        $st=$this->db->prepare("SELECT serie,ultim,(ultim+1) AS siguiente FROM numer WHERE tipo=:tipo LIMIT 1");
        $st->execute(['tipo'=>'factura']);
        $r=$st->fetch(PDO::FETCH_ASSOC);
        if(!$r)throw new RuntimeException('No existe numeración de FACTURA.');
        return ['serie'=>(string)$r['serie'],'siguiente'=>(int)$r['siguiente']];
    }

    public function emitir(int $opera,?int $clien,array $cliente,array $detalles,float $subto,float $igv,float $total,string $formp,string $obser): array
    {
        $tipod=strtoupper(trim((string)($cliente['tipod']??'')));$docum=preg_replace('/\D+/','',(string)($cliente['docum']??''));$razon=trim((string)($cliente['razon']??''));$direc=trim((string)($cliente['direc']??''));
        if(!in_array(strlen($docum),[8,11],true)||$razon==='')throw new InvalidArgumentException('Datos del comprador no válidos para el documento.');
        if($tipod==='')$tipod=strlen($docum)===11?'RUC':'DNI';
        $this->db->beginTransaction();
        try{
            $sn=$this->db->prepare("SELECT id,serie,ultim FROM numer WHERE tipo=:tipo FOR UPDATE");$sn->execute(['tipo'=>'factura']);$n=$sn->fetch(PDO::FETCH_ASSOC);
            if(!$n)throw new RuntimeException('No existe numeración de FACTURA.');
            $num=(int)$n['ultim']+1;$this->db->prepare('UPDATE numer SET ultim=:n WHERE id=:id')->execute(['n'=>$num,'id'=>(int)$n['id']]);
            $fecha=(new DateTime())->format('Y-m-d H:i:s');
            $in=$this->db->prepare("INSERT INTO factu(opera,clien,tipod,docum,razon,direc,serie,numer,fecha,formp,subto,igv,total,estad,obser) VALUES(:opera,:clien,:tipod,:docum,:razon,:direc,:serie,:numer,:fecha,:formp,:subto,:igv,:total,'emitida',:obser)");
            $in->execute(['opera'=>$opera,'clien'=>$clien,'tipod'=>$tipod,'docum'=>$docum,'razon'=>$razon,'direc'=>$direc!==''?$direc:null,'serie'=>$n['serie'],'numer'=>$num,'fecha'=>$fecha,'formp'=>$formp!==''?$formp:null,'subto'=>$subto,'igv'=>$igv,'total'=>$total,'obser'=>$obser!==''?$obser:null]);
            $id=(int)$this->db->lastInsertId();$di=$this->db->prepare("INSERT INTO detfa(factu,produ,descr,canti,preun,subto,igv,total) VALUES(:doc,:produ,:descr,:canti,:preun,:subto,:igv,:total)");
            foreach($detalles as $d){
                $lt=(float)$d['subto'];$lb=round($lt/1.18,2);$li=round($lt-$lb,2);
                $di->execute(['doc'=>$id,'produ'=>(int)$d['produ'],'descr'=>(string)$d['descr'],'canti'=>(float)$d['canti'],'preun'=>(float)$d['preci'],'subto'=>$lb,'igv'=>$li,'total'=>$lt]);
            }
            $this->db->commit();return ['tipo'=>'FACTURA','serie'=>$n['serie'],'numer'=>$num,'documento_id'=>$id];
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
}
