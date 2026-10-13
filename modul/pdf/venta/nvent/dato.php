<?php
/* ============================================================
   Ruta: modul/pdf/venta/nvent/dato.php
   Módulo: PDF / Ventas / Nota de Venta

   NIVEL 1 — ORIGEN DE DATOS
   NIVEL 2 — NORMALIZACIÓN
   Entrega datos al diseño PDF común. No dibuja ni genera PDF.
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/contr/categ/venta-db.php';
require_once dirname(__DIR__, 4) . '/contr/categ/catal-db.php';
require_once dirname(__DIR__, 4) . '/contr/login/login-db.php';
require_once dirname(__DIR__, 4) . '/modul/categ/venta/nvent/nvent-repos.php';
require_once dirname(__DIR__, 4) . '/modul/categ/catal/produ/produ-repos.php';
require_once dirname(__DIR__, 4) . '/modul/admin/usuar/usuar-repos.php';

function datos(int $id): array
{
    if ($id <= 0) throw new InvalidArgumentException('Nota de Venta no válida.');
    $vent=VENTADB::abrir();$cata=CATALDB::abrir();$logi=LOGINDB::abrir();
    $repo=new NvenRepo($vent);$nota=$repo->obtener($id);
    $prodRepo=new ProdRepo($cata);$usrRepo=new UsuaRepo($logi);
    $vende=$usrRepo->porId((int)($nota['vende']??0));
    $doc=[
        'tipo'=>'NOTA DE VENTA',
        'serie'=>(string)($nota['serie']??''),
        'nume'=>(int)($nota['numer']??0),
        'estado'=>(string)($nota['estad']??''),
        'fecha'=>(string)($nota['fecha']??''),
        'docum'=>(string)($nota['docum']??''),
        'razon'=>(string)($nota['razon']??''),
        'direc'=>(string)($nota['direc']??''),
        'placa'=>(string)($nota['placa']??''),
        'vigia'=>'',
        'vende_nom'=>trim((string)($vende['nombr']??'').' '.(string)($vende['apell']??'')),
        'formp'=>(string)($nota['formp']??''),
        'medio'=>(string)($nota['medi']??''),
        'subto'=>(float)($nota['subto']??0),
        'igv'=>(float)($nota['igv']??0),
        'total'=>(float)($nota['total']??0),
        'pie'=>'Documento interno de Nota de Venta.'
    ];
    $detal=[];
    foreach(($nota['detalles']??[]) as $row){
        $prod=$prodRepo->obtener((int)$row['produ']);
        $canti=(float)($row['canti']??0);$preun=(float)($row['preun']??0);
        $detal[]=[
            'codig'=>(string)($prod['codig']??''),
            'descr'=>(string)($prod['descr']??''),
            'canti'=>$canti,
            'preun'=>$preun,
            'total'=>(float)($row['subto']??round($canti*$preun,2))
        ];
    }
    return ['doc'=>$doc,'detal'=>$detal];
}
