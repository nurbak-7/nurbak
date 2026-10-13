<?php
/* ============================================================
   Ruta: modul/categ/venta/opera/opera-repos.php
   Módulo: Ventas / Operaciones

   RESPONSABILIDAD:
   Persistir exclusivamente VENTA.opera y VENTA.detop.

   NO RESPONSABILIDADES:
   - CATAL
   - INVENTARIO / RESERVAS
   - LOGI
   - numeración de documentos
   - emisión de documentos
============================================================ */
declare(strict_types=1);

final class OperaRepo
{
    public function __construct(private PDO $db) {}

    public function listar(string $buscar = '', int $limite = 50): array
    {
        $limite=max(1,min($limite,100));$buscar=trim($buscar);
        $sql="SELECT o.id,o.clien,o.tipod,o.docum,o.razon,o.direc,o.vende,o.almac,o.placa,
                       o.estad,o.fecha,o.emisi,o.subto,o.igv,o.total,o.obser
                FROM opera o";
        $par=[];
        if($buscar!==''){
            $sql.=" WHERE o.placa LIKE :buscar
                       OR o.docum LIKE :buscar
                       OR o.razon LIKE :buscar
                       OR CAST(o.id AS CHAR) LIKE :buscar
                       OR o.estad LIKE :buscar";
            $par['buscar']="%{$buscar}%";
        }
        $sql.=" ORDER BY o.id DESC LIMIT {$limite}";
        $st=$this->db->prepare($sql);$st->execute($par);return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtener(int $operaId): array
    {
        if($operaId<=0)throw new InvalidArgumentException('Operación no válida.');
        $st=$this->db->prepare('SELECT id,clien,tipod,docum,razon,direc,vende,almac,placa,estad,fecha,emisi,subto,igv,total,obser FROM opera WHERE id=:id LIMIT 1');
        $st->execute(['id'=>$operaId]);$cab=$st->fetch(PDO::FETCH_ASSOC);
        if(!$cab)throw new InvalidArgumentException('La operación no existe.');
        $sd=$this->db->prepare('SELECT id,produ,canti,preun AS preci,subto FROM detop WHERE opera=:opera ORDER BY id');
        $sd->execute(['opera'=>$operaId]);$cab['detalles']=$sd->fetchAll(PDO::FETCH_ASSOC);return $cab;
    }

    public function guardarBorrador(
        int $operaId,
        ?int $clienId,
        ?string $tipod,
        ?string $docum,
        ?string $razon,
        ?string $direc,
        int $vendeId,
        int $almaId,
        string $placa,
        string $fecha,
        array $detalles,
        float $subto,
        float $igv,
        float $total,
        string $obser
    ): int {
        if($almaId<=0||$detalles===[])throw new InvalidArgumentException('Almacén y productos son obligatorios.');
        $this->db->beginTransaction();
        try{
            if($operaId>0){
                $qo=$this->db->prepare('SELECT id,estad FROM opera WHERE id=:id FOR UPDATE');$qo->execute(['id'=>$operaId]);$ope=$qo->fetch(PDO::FETCH_ASSOC);
                if(!$ope)throw new InvalidArgumentException('La operación no existe.');
                if($ope['estad']!=='borrador')throw new InvalidArgumentException('La operación ya fue procesada.');
                $up=$this->db->prepare(
                    'UPDATE opera SET clien=:clien,tipod=:tipod,docum=:docum,razon=:razon,direc=:direc,
                     vende=:vende,almac=:almac,placa=:placa,fecha=:fecha,subto=:subto,igv=:igv,total=:total,obser=:obser
                     WHERE id=:id AND estad=\'borrador\''
                );
                $up->execute([
                    'clien'=>$clienId,'tipod'=>$tipod,'docum'=>$docum,'razon'=>$razon,'direc'=>$direc,
                    'vende'=>$vendeId>0?$vendeId:null,'almac'=>$almaId,'placa'=>$placa!==''?$placa:null,
                    'fecha'=>$fecha,'subto'=>$subto,'igv'=>$igv,'total'=>$total,'obser'=>$obser!==''?$obser:null,'id'=>$operaId
                ]);
                $id=$operaId;$this->db->prepare('DELETE FROM detop WHERE opera=:id')->execute(['id'=>$id]);
            }else{
                $in=$this->db->prepare(
                    'INSERT INTO opera (clien,tipod,docum,razon,direc,vende,almac,placa,estad,fecha,subto,igv,total,obser)
                     VALUES (:clien,:tipod,:docum,:razon,:direc,:vende,:almac,:placa,\'borrador\',:fecha,:subto,:igv,:total,:obser)'
                );
                $in->execute([
                    'clien'=>$clienId,'tipod'=>$tipod,'docum'=>$docum,'razon'=>$razon,'direc'=>$direc,
                    'vende'=>$vendeId>0?$vendeId:null,'almac'=>$almaId,'placa'=>$placa!==''?$placa:null,
                    'fecha'=>$fecha,'subto'=>$subto,'igv'=>$igv,'total'=>$total,'obser'=>$obser!==''?$obser:null
                ]);
                $id=(int)$this->db->lastInsertId();
            }
            $ins=$this->db->prepare('INSERT INTO detop (opera,produ,canti,preun,subto) VALUES (:opera,:produ,:canti,:preun,:subto)');
            foreach($detalles as $d)$ins->execute(['opera'=>$id,'produ'=>(int)$d['produ'],'canti'=>(float)$d['canti'],'preun'=>(float)$d['preci'],'subto'=>(float)$d['subto']]);
            $this->db->commit();return $id;
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function vacia(int $operaId): void
    {
        $ope=$this->obtener($operaId);if($ope['estad']!=='borrador')throw new InvalidArgumentException('Solo se puede vaciar un borrador.');
        $this->db->beginTransaction();
        try{$this->db->prepare('DELETE FROM detop WHERE opera=:id')->execute(['id'=>$operaId]);$this->db->prepare("UPDATE opera SET subto=0,igv=0,total=0 WHERE id=:id AND estad='borrador'")->execute(['id'=>$operaId]);$this->db->commit();}
        catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function confirmarEstado(int $operaId,string $emisi): void
    {
        $st=$this->db->prepare("UPDATE opera SET emisi=:emisi,estad='confirmada' WHERE id=:id AND estad='borrador'");$st->execute(['emisi'=>$emisi,'id'=>$operaId]);
        if($st->rowCount()!==1)throw new RuntimeException('No se pudo confirmar la operación.');
    }

    public function cancelar(int $operaId): void
    {
        if($operaId<=0)throw new InvalidArgumentException('Operación no válida.');
        $st=$this->db->prepare("UPDATE opera SET estad='anulada' WHERE id=:id AND estad='borrador'");$st->execute(['id'=>$operaId]);
        if($st->rowCount()!==1)throw new InvalidArgumentException('Solo se puede cancelar una operación en borrador.');
    }
}
