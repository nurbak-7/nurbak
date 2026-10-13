<?php
/* ============================================================
   Ruta: modul/categ/venta/opera/opera-servi.php
   Módulo: Ventas / Operaciones / Servicio

   RESPONSABILIDAD:
   Coordinar las reglas de negocio de Operaciones.

   Los repositorios externos conservan la persistencia de sus
   propios módulos: CATAL, INVEN, LOGI, VENTA y FACTU.
============================================================ */
declare(strict_types=1);

final class OperaServ
{
    private const VIGE = 60;

    public function __construct(
        private OperaRepo $repo,
        private ClieRepo $clie,
        private ProdRepo $prod,
        private AlmaRepo $alma,
        private StocRepo $stoc,
        private ReseR $rese,
        private UsuaRepo $usua,
        private BoleRepo $bole,
        private FactRepo $fact
    ) {}

    private function normalizarDetalles(array $detalles): array
    {
        $agr=[];
        foreach($detalles as $d){
            $prod=(int)($d['produ']??0);$canti=(float)($d['canti']??0);$preci=(float)($d['preci']??$d['preun']??0);
            if($prod<=0||$canti<=0||$preci<0)throw new InvalidArgumentException('Producto, canti o preci no válidos.');
            if(!isset($agr[$prod]))$agr[$prod]=['produ'=>$prod,'canti'=>0.0,'preci'=>$preci];
            $agr[$prod]['canti']+=$canti;$agr[$prod]['preci']=$preci;
        }
        if($agr===[])throw new InvalidArgumentException('La operación debe contener productos.');
        return array_values($agr);
    }

    public function calcular(array $detalles): array
    {
        $detalles=$this->normalizarDetalles($detalles);$importe=0.0;
        foreach($detalles as &$d){$d['subto']=round($d['canti']*$d['preci'],2);$importe+=$d['subto'];}unset($d);
        $importe=round($importe,2);$base=round($importe/1.18,2);$igv=round($importe-$base,2);
        return ['detalles'=>$detalles,'subto'=>$base,'igv'=>$igv,'total'=>$importe];
    }

    public function opciones(): array
    {
        return [
            'clientes'=>$this->clie->listar('',100),
            'almacenes'=>$this->alma->listar(0,'',100)
        ];
    }

    public function numeracion(string $docum): array
    {
        $docum=preg_replace('/\\D+/','',$docum);

        if($docum===''){
            return $this->bole->numeracion();
        }

        if(strlen($docum)===8){
            return $this->bole->numeracion();
        }

        if(strlen($docum)===11){
            return $this->fact->numeracion();
        }

        throw new InvalidArgumentException('RUC/DNI no válido.');
    }

    public function listar(string $buscar = '', int $limite = 50): array
    {
        $rows=$this->repo->listar($buscar,$limite);
        foreach($rows as &$row){
            $row['cliente']=(string)($row['razon']??'');
        }
        unset($row);
        return $rows;
    }

    public function buscarCliente(string $docum): ?array { return $this->clie->porDocum($docum); }
    public function buscarVendedor(string $codig): ?array { return $this->usua->porOperc($codig); }

    public function buscarProductos(string $buscar,int $almaId,int $limite=20): array
    {
        $a=$this->alma->porId($almaId);if(!$a||(int)$a['estad']!==1)throw new InvalidArgumentException('El almacén no existe o está inactivo.');
        $rows=$this->prod->buscarOpera($buscar,$limite);if($rows===[])return [];
        $ids=array_values(array_unique(array_map(fn($r)=>(int)$r['id'],$rows)));
        $estado=$this->rese->estado($almaId,$ids);$map=[];foreach($estado as $r)$map[(int)$r['produ']]=$r;
        foreach($rows as &$r){$e=$map[(int)$r['id']]??['canti'=>0,'reser'=>0,'dispo'=>0];$r['canti']=$e['canti'];$r['reser']=$e['reser'];$r['dispo']=$e['dispo'];$r['preci']=(float)$r['pvent'];unset($r['pvent']);}unset($r);
        return $rows;
    }

    public function guardarBorrador(array $datos): array
    {
        $calc=$this->calcular($datos['detalles']??[]);
        $clien=$datos['clien']??null;
        $clien=$clien===null||$clien===''?null:(int)$clien;
        $vende=(int)($datos['vende']??0);$almac=(int)($datos['almac']??0);
        $tipod=strtoupper(trim((string)($datos['tipod']??'')));
        $docum=preg_replace('/\D+/','',(string)($datos['docum']??''));
        $razon=trim((string)($datos['razon']??''));
        $direc=trim((string)($datos['direc']??''));

        if($clien!==null){
            $cliente=$this->clie->porId($clien);
            if(!$cliente||(int)$cliente['estad']!==1)throw new InvalidArgumentException('Cliente no existe o está inactivo.');
            if($tipod==='')$tipod=strtoupper((string)$cliente['tipod']);
            if($docum==='')$docum=preg_replace('/\D+/','',(string)$cliente['docum']);
            if($razon==='')$razon=trim((string)$cliente['razon']);
            if($direc==='')$direc=trim((string)($cliente['direc']??''));
        }

        if($vende>0&&!$this->usua->porId($vende))throw new InvalidArgumentException('Vendedor no existe.');
        $a=$this->alma->porId($almac);if(!$a||(int)$a['estad']!==1)throw new InvalidArgumentException('El almacén no existe o está inactivo.');
        foreach($calc['detalles'] as $d)$this->prod->obtener((int)$d['produ']);

        $id=$this->repo->guardarBorrador(
            (int)($datos['opera']??0),
            $clien,
            $tipod!==''?$tipod:null,
            $docum!==''?$docum:null,
            $razon!==''?$razon:null,
            $direc!==''?$direc:null,
            $vende,
            $almac,
            strtoupper(trim((string)($datos['placa']??''))),
            trim((string)($datos['fecha']??date('Y-m-d'))),
            $calc['detalles'],$calc['subto'],$calc['igv'],$calc['total'],trim((string)($datos['obser']??''))
        );
        $reservas=$this->rese->sincronizar($id,$almac,$calc['detalles']);
        return ['opera'=>$id,'calculo'=>['subto'=>$calc['subto'],'igv'=>$calc['igv'],'total'=>$calc['total']],'estados'=>$this->rese->estado($almac,array_map(fn($d)=>(int)$d['produ'],$calc['detalles'])),'reservas'=>$reservas];
    }

    public function latid(int $id): array
    {
        $o=$this->repo->obtener($id);if($o['estad']!=='borrador')return ['opera'=>$id,'activo'=>false,'reservas'=>0];
        $this->rese->sincronizar($id,(int)$o['almac'],$o['detalles']);return ['opera'=>$id,'activo'=>true,'reservas'=>$this->rese->activas($id)];
    }

    public function dispo(int $almac,array $ids): array { $this->rese->expirar();return $this->rese->estado($almac,$ids); }
    public function expi(): int { return $this->rese->expirar(); }

    public function vacia(int $id): void { $o=$this->repo->obtener($id);$this->repo->vacia($id);$this->rese->liberar($id); }

    public function confirmar(int $id,string $formp=''): array
    {
        $o=$this->repo->obtener($id);if($o['estad']!=='borrador')throw new InvalidArgumentException('La operación ya fue procesada.');if($o['detalles']===[])throw new InvalidArgumentException('La operación no tiene productos.');
        /*
         * La sincronización reserva todo lo físicamente disponible.
         *
         * IMPORTANTE:
         * Si el stock físico es 0, la operación NO se bloquea.
         * La cantidad faltante se registra como salss al finalizar.
         *
         * Por eso aquí no se debe exigir que canti <= stock físico.
         * Esa validación fue la que impedía confirmar ventas sin stock.
         */
        $this->rese->sincronizar($id,(int)$o['almac'],$o['detalles']);

        foreach($o['detalles'] as &$d){$p=$this->prod->obtener((int)$d['produ']);$d['descr']=(string)$p['descr'];}unset($d);

        $clien=$o['clien']!==null&&$o['clien']!==''?(int)$o['clien']:null;
        $tipod=strtoupper(trim((string)($o['tipod']??'')));
        $docum=preg_replace('/\D+/','',(string)($o['docum']??''));
        $razon=trim((string)($o['razon']??''));
        $direc=trim((string)($o['direc']??''));
        if(!in_array(strlen($docum),[8,11],true))throw new InvalidArgumentException('La operación debe tener un RUC/DNI válido antes de emitir el documento.');

        /*
         * El documento final no se acepta desde el navegador.
         * Se determina exclusivamente por la cantidad de dígitos.
         */
        $tipo = strlen($docum)===11 ? 'FACTURA' : 'BOLETA';

        if($tipod==='')$tipod=strlen($docum)===11?'RUC':'DNI';
        if($razon==='')throw new InvalidArgumentException('La operación debe tener el nombre o razón social del comprador antes de emitir el documento.');
        $cliente=['tipod'=>$tipod,'docum'=>$docum,'razon'=>$razon,'direc'=>$direc];

        $plan=[];

        foreach($o['detalles'] as $d){
            $sal=(float)$d['canti'];
            $st=$this->rese->estado(
                (int)$o['almac'],
                [(int)$d['produ']],
                $id
            )[0]??null;

            if(!$st){
                throw new InvalidArgumentException(
                    'No se pudo validar el stock del producto.'
                );
            }

            /*
             * estado() excluye la reserva de esta operación.
             * dispo representa lo que puede salir físicamente sin
             * tocar reservas de otras operaciones.
             */
            $fis=(float)$st['canti'];
            $dispo=(float)$st['dispo'];
            $salida=min($sal,$dispo);
            $falt=max(0.0,$sal-$salida);

            if($falt>0&&$fis>0){
                throw new InvalidArgumentException(
                    'La cantidad solicitada no está disponible; el stock está reservado por otra operación.'
                );
            }

            $plan[]=[
                'produ'=>(int)$d['produ'],
                'salida'=>$salida,
                'falt'=>$falt
            ];
        }

        foreach($plan as $p){
            if($p['salida']>0){
                $this->stoc->salida(
                    $p['produ'],
                    (int)$o['almac'],
                    $p['salida'],
                    "Venta - operación #{$id}"
                );
            }

            if($p['falt']>0){
                $this->stoc->salss(
                    $p['produ'],
                    (int)$o['almac'],
                    $p['falt'],
                    "Venta sin stock - operación #{$id}"
                );
            }
        }

        $doc = $tipo === 'BOLETA'
            ? $this->bole->emitir(
                $id,
                $clien,
                $cliente,
                $o['detalles'],
                (float)$o['subto'],
                (float)$o['igv'],
                (float)$o['total'],
                trim($formp),
                (string)$o['obser']
            )
            : $this->fact->emitir(
                $id,
                $clien,
                $cliente,
                $o['detalles'],
                (float)$o['subto'],
                (float)$o['igv'],
                (float)$o['total'],
                trim($formp),
                (string)$o['obser']
            );
        $this->rese->consumir($id);$this->repo->confirmarEstado($id,(new DateTime())->format('Y-m-d H:i:s'));
        return ['opera'=>$id,'tipo'=>$doc['tipo'],'serie'=>$doc['serie'],'numer'=>$doc['numer'],'documento_id'=>$doc['documento_id']];
    }

    public function cancelar(int $id): void { $this->repo->obtener($id);$this->rese->liberar($id);$this->repo->cancelar($id); }
}
