<?php

/* ============================================================
   Ruta: modul/categ/venta/nvent/nvent-repos.php

   Módulo: Ventas / Notas de venta

   RESPONSABILIDAD:

   Consultar y emitir VENTA.notav.

   La Nota de Venta conserva el snapshot del comprador usado
   por la operación. clien es una referencia opcional al maestro.
============================================================ */

declare(strict_types=1);


/* ============================================================
   NIVEL 1 — REPOSITORIO
============================================================ */

final class NvenRepo
{

    /* ========================================================
       NIVEL 2 — CONEXIÓN
    ======================================================== */

    public function __construct(private PDO $db) {}


    /* ========================================================
       NIVEL 2 — CONSULTA
    ======================================================== */

    public function listar(
        string $buscar = '',
        int $limite = 50
    ): array {

        /* ====================================================
           NIVEL 3 — PARÁMETROS
        ==================================================== */

        $limite = max(1, min($limite, 100));
        $buscar = trim($buscar);


        /* ====================================================
           NIVEL 3 — SQL
        ==================================================== */

        $sql = '
            SELECT
                n.id,
                n.opera,
                n.clien,
                n.tipod,
                n.docum,
                n.razon,
                n.direc,
                n.serie,
                n.numer,
                n.estad,
                n.fecha,
                n.subto,
                n.total,
                n.obser
            FROM notav n
        ';


        /* ====================================================
           NIVEL 3 — FILTRO
        ==================================================== */

        $params = [];

        if ($buscar !== '') {

            $sql .= '
                WHERE
                    n.razon LIKE :razon
                    OR n.docum LIKE :docum
                    OR n.estad LIKE :estad
                    OR CAST(n.id AS CHAR) LIKE :id
            ';

            $v = '%' . $buscar . '%';

            $params = [
                'razon' => $v,
                'docum' => $v,
                'estad' => $v,
                'id'    => $v
            ];
        }


        /* ====================================================
           NIVEL 3 — ORDEN
        ==================================================== */

        $sql .= " ORDER BY n.id DESC LIMIT {$limite}";


        /* ====================================================
           NIVEL 3 — EJECUCIÓN
        ==================================================== */

        $st = $this->db->prepare($sql);

        foreach ($params as $n => $v) {
            $st->bindValue(
                ':' . $n,
                $v,
                PDO::PARAM_STR
            );
        }

        $st->execute();

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }


    public function obtener(int $id): array
    {
        if ($id <= 0) throw new InvalidArgumentException('Nota de Venta no válida.');
        $st=$this->db->prepare('SELECT id,opera,clien,vende,almac,placa,tipod,docum,razon,direc,serie,numer,estad,fecha,subto,igv,total,formp,medi,obser FROM notav WHERE id=:id LIMIT 1');
        $st->execute(['id'=>$id]);$nota=$st->fetch(PDO::FETCH_ASSOC);
        if(!$nota)throw new InvalidArgumentException('La Nota de Venta no existe.');
        $dt=$this->db->prepare('SELECT produ,canti,preun,subto FROM detnv WHERE notav=:id ORDER BY id ASC');
        $dt->execute(['id'=>$id]);$nota['detalles']=$dt->fetchAll(PDO::FETCH_ASSOC);
        return $nota;
    }

    public function actualizar(int $id, ?int $clien, array $cliente, array $detalles, int $almac, int $vende, string $placa, string $fecha, string $formp, string $medi, string $obser): array
    {
        $actual=$this->obtener($id);
        if(($actual['estad']??'')!=='emitida')throw new InvalidArgumentException('Solo se puede editar una Nota de Venta emitida.');
        if((int)$actual['almac']!==$almac)throw new InvalidArgumentException('No se puede cambiar el almacén de una Nota de Venta emitida.');
        $docum=preg_replace('/\D+/', '', (string)($cliente['docum']??''));$razon=trim((string)($cliente['razon']??''));$direc=trim((string)($cliente['direc']??''));
        if(!in_array(strlen($docum),[8,11],true)||$razon==='')throw new InvalidArgumentException('Ingrese un DNI/RUC válido y el nombre del comprador.');
        if($detalles===[])throw new InvalidArgumentException('Agregue al menos un producto.');
        $placa=strtoupper(trim($placa));if($placa!==''&&!preg_match('/^[A-Z0-9]{3}-[A-Z0-9]{3}$/',$placa))throw new InvalidArgumentException('La placa debe tener el formato SSS-SSS.');
        $formp=strtoupper(trim($formp));if(!in_array($formp,['CONTADO','CREDITO'],true))throw new InvalidArgumentException('La forma de pago no es válida.');
        $f=DateTime::createFromFormat('!Y-m-d',$fecha);if(!$f||$f->format('Y-m-d')!==$fecha)throw new InvalidArgumentException('La fecha de Nota de Venta no es válida.');
        $total=0.0;$valid=[];
        foreach($detalles as $d){$produ=(int)($d['produ']??0);$canti=(float)($d['canti']??0);$preci=(float)($d['preci']??-1);if($produ<=0||!is_finite($canti)||$canti<=0||!is_finite($preci)||$preci<0)throw new InvalidArgumentException('Hay una línea con cantidad o precio no válido.');$sub=round($canti*$preci,2);$total=round($total+$sub,2);$valid[]=['produ'=>$produ,'canti'=>$canti,'preci'=>$preci,'subto'=>$sub];}
        $subto=round($total/1.18,2);$igv=round($total-$subto,2);
        $this->db->beginTransaction();
        try{
            $lock=$this->db->prepare("SELECT id FROM notav WHERE id=:id AND estad='emitida' FOR UPDATE");$lock->execute(['id'=>$id]);if(!$lock->fetch())throw new InvalidArgumentException('La nota cambió o ya no está emitida.');
            $up=$this->db->prepare('UPDATE notav SET clien=:clien,tipod=:tipod,docum=:docum,razon=:razon,direc=:direc,placa=:placa,vende=:vende,fecha=:fecha,subto=:subto,igv=:igv,total=:total,formp=:formp,medi=:medi,obser=:obser WHERE id=:id');
            $up->execute(['clien'=>$clien,'tipod'=>strlen($docum)===11?'RUC':'DNI','docum'=>$docum,'razon'=>$razon,'direc'=>$direc!==''?$direc:null,'placa'=>$placa!==''?$placa:null,'vende'=>$vende,'fecha'=>$fecha.' '.(new DateTime())->format('H:i:s'),'subto'=>$subto,'igv'=>$igv,'total'=>$total,'formp'=>$formp,'medi'=>trim($medi)!==''?trim($medi):null,'obser'=>trim($obser)!==''?trim($obser):null,'id'=>$id]);
            $this->db->prepare('DELETE FROM detnv WHERE notav=:id')->execute(['id'=>$id]);
            $di=$this->db->prepare('INSERT INTO detnv(notav,produ,canti,preun,subto) VALUES(:id,:produ,:canti,:preun,:subto)');
            foreach($valid as $d)$di->execute(['id'=>$id,'produ'=>$d['produ'],'canti'=>$d['canti'],'preun'=>$d['preci'],'subto'=>$d['subto']]);
            $this->db->commit();
            return ['documento_id'=>$id,'serie'=>(string)$actual['serie'],'numer'=>(int)$actual['numer'],'subto'=>$subto,'igv'=>$igv,'total'=>$total];
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    /* ========================================================
       NIVEL 2 — NUMERACIÓN
    ======================================================== */

    public function numeracion(): array
    {

        /* ====================================================
           NIVEL 3 — CONSULTA DE NUMERACIÓN
        ==================================================== */

        $st = $this->db->prepare(
            "SELECT
                serie,
                ultim,
                (ultim + 1) AS siguiente
             FROM numer
             WHERE tipo = 'notav'
             ORDER BY id ASC
             LIMIT 1"
        );

        $st->execute();

        $r = $st->fetch(PDO::FETCH_ASSOC);


        /* ====================================================
           NIVEL 3 — VALIDACIÓN
        ==================================================== */

        if (!$r) {
            throw new RuntimeException(
                'No existe numeración de Nota de Venta.'
            );
        }


        /* ====================================================
           NIVEL 3 — RESPUESTA
        ==================================================== */

        return [
            'serie'    => (string)$r['serie'],
            'siguiente'=> (int)$r['siguiente']
        ];
    }


    /* ========================================================
       NIVEL 2 — EMISIÓN
    ======================================================== */

    public function emitir(
        ?int $opera,
        ?int $clien,
        array $cliente,
        array $detalles,
        int $almac,
        ?int $vende,
        string $placa,
        string $fecha,
        string $formp,
        string $medi,
        string $obser
    ): array {
        $docum = preg_replace('/\D+/', '', (string)($cliente['docum'] ?? ''));
        $razon = trim((string)($cliente['razon'] ?? ''));
        $direc = trim((string)($cliente['direc'] ?? ''));

        if (!in_array(strlen($docum), [8, 11], true) || $razon === '') {
            throw new InvalidArgumentException('Ingrese un DNI de 8 dígitos o un RUC de 11 dígitos y el nombre del comprador.');
        }
        $tipod = strlen($docum) === 11 ? 'RUC' : 'DNI';
        if ($almac <= 0) throw new InvalidArgumentException('Seleccione un almacén activo.');
        if ($vende === null || $vende <= 0) throw new InvalidArgumentException('Identifique al vendedor.');
        if ($detalles === []) throw new InvalidArgumentException('Agregue al menos un producto.');
        $placa = strtoupper(trim($placa));
        if ($placa !== '' && !preg_match('/^[A-Z0-9]{3}-[A-Z0-9]{3}$/', $placa)) {
            throw new InvalidArgumentException('La placa debe tener el formato SSS-SSS.');
        }
        $formp = strtoupper(trim($formp));
        if (!in_array($formp, ['CONTADO', 'CREDITO'], true)) {
            throw new InvalidArgumentException('La forma de pago no es válida.');
        }
        $medi = trim($medi);
        $obser = trim($obser);
        $fechaObj = DateTime::createFromFormat('!Y-m-d', $fecha);
        if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fecha) {
            throw new InvalidArgumentException('La fecha de Nota de Venta no es válida.');
        }
        $fecha = $fechaObj->format('Y-m-d') . ' ' . (new DateTime())->format('H:i:s');

        $detValid = [];
        $total = 0.0;
        foreach ($detalles as $d) {
            $produ = (int)($d['produ'] ?? 0);
            $canti = (float)($d['canti'] ?? 0);
            $preci = (float)($d['preci'] ?? -1);
            if ($produ <= 0 || !is_finite($canti) || $canti <= 0 || !is_finite($preci) || $preci < 0) {
                throw new InvalidArgumentException('Hay una línea de producto con cantidad o precio no válido.');
            }
            $linea = round($canti * $preci, 2);
            $total = round($total + $linea, 2);
            $detValid[] = ['produ'=>$produ, 'canti'=>$canti, 'preci'=>$preci, 'subto'=>$linea];
        }
        $subto = round($total / 1.18, 2);
        $igv = round($total - $subto, 2);

        $this->db->beginTransaction();
        try {
            $sn = $this->db->prepare("SELECT id,serie,ultim FROM numer WHERE tipo='notav' ORDER BY id ASC LIMIT 1 FOR UPDATE");
            $sn->execute();
            $n = $sn->fetch(PDO::FETCH_ASSOC);
            if (!$n) throw new RuntimeException('No existe numeración de Nota de Venta.');
            $num = (int)$n['ultim'] + 1;
            $upn = $this->db->prepare('UPDATE numer SET ultim=:n WHERE id=:id');
            $upn->execute(['n'=>$num,'id'=>(int)$n['id']]);

            $in = $this->db->prepare(
                "INSERT INTO notav (
                    opera,clien,vende,almac,placa,tipod,docum,razon,direc,
                    serie,numer,estad,fecha,subto,igv,total,formp,medi,obser
                ) VALUES (
                    :opera,:clien,:vende,:almac,:placa,:tipod,:docum,:razon,:direc,
                    :serie,:numer,'emitida',:fecha,:subto,:igv,:total,:formp,:medi,:obser
                )"
            );
            $in->execute([
                'opera'=>$opera,
                'clien'=>$clien,
                'vende'=>$vende,
                'almac'=>$almac,
                'placa'=>$placa !== '' ? $placa : null,
                'tipod'=>$tipod,
                'docum'=>$docum,
                'razon'=>$razon,
                'direc'=>$direc !== '' ? $direc : null,
                'serie'=>$n['serie'],
                'numer'=>$num,
                'fecha'=>$fecha,
                'subto'=>$subto,
                'igv'=>$igv,
                'total'=>$total,
                'formp'=>$formp,
                'medi'=>$medi !== '' ? $medi : null,
                'obser'=>$obser !== '' ? $obser : null
            ]);

            $id = (int)$this->db->lastInsertId();
            $di = $this->db->prepare('INSERT INTO detnv (notav,produ,canti,preun,subto) VALUES (:doc,:produ,:canti,:preun,:subto)');
            foreach ($detValid as $d) {
                $di->execute(['doc'=>$id,'produ'=>$d['produ'],'canti'=>$d['canti'],'preun'=>$d['preci'],'subto'=>$d['subto']]);
            }
            $this->db->commit();
            return [
                'tipo'=>'NOTA',
                'serie'=>(string)$n['serie'],
                'numer'=>$num,
                'documento_id'=>$id,
                'subto'=>$subto,
                'igv'=>$igv,
                'total'=>$total
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

}