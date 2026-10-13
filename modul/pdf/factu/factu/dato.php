<?php
/* ============================================================
   Ruta: modul/pdf/factu/factu/dato.php
   Módulo: PDF / Facturación / Factura

   NIVEL 1 — ORIGEN DE DATOS
   ------------------------------------------------------------
   Obtiene la factura y su detalle desde FACTU.
   Complementa los datos que el documento conserva en VENTA,
   LOGIN y CATAL.

   NIVEL 2 — NORMALIZACIÓN
   ------------------------------------------------------------
   Entrega exclusivamente datos al PDF común.

   ESTE ARCHIVO NO DIBUJA PDF.
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/contr/categ/factu-db.php';
require_once dirname(__DIR__, 4) . '/contr/categ/venta-db.php';
require_once dirname(__DIR__, 4) . '/contr/categ/catal-db.php';
require_once dirname(__DIR__, 4) . '/contr/login/login-db.php';

function datos(int $id): array
{
    if ($id <= 0) {
        throw new InvalidArgumentException('Factura no válida.');
    }

    $fact = FACTUDB::abrir();
    $vent = VENTADB::abrir();
    $cata = CATALDB::abrir();
    $logi = LOGINDB::abrir();

    $st = $fact->prepare(
        'SELECT id,opera,docum,razon,direc,serie,numer,fecha,formp,subto,igv,total,estad
         FROM factu WHERE id=:id LIMIT 1'
    );
    $st->execute(['id' => $id]);
    $cab = $st->fetch(PDO::FETCH_ASSOC);
    if (!$cab) {
        throw new RuntimeException('No se encontró la factura.');
    }

    $sd = $fact->prepare(
        'SELECT id,produ,descr,canti,preun,subto,igv,total
         FROM detfa WHERE factu=:id ORDER BY id'
    );
    $sd->execute(['id' => $id]);
    $detal = $sd->fetchAll(PDO::FETCH_ASSOC);

    $ids = [];
    foreach ($detal as $row) {
        if ((int)($row['produ'] ?? 0) > 0) {
            $ids[] = (int)$row['produ'];
        }
    }
    $ids = array_values(array_unique($ids));
    $map = [];
    if ($ids !== []) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $sp = $cata->prepare("SELECT id,codig FROM produ WHERE id IN ($ph)");
        $sp->execute($ids);
        foreach ($sp->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int)$row['id']] = (string)($row['codig'] ?? '');
        }
    }
    foreach ($detal as &$row) {
        $row['codig'] = $map[(int)($row['produ'] ?? 0)] ?? '';
    }
    unset($row);

    $cab['placa'] = '';
    $cab['vende_nom'] = '';
    if ((int)($cab['opera'] ?? 0) > 0) {
        $so = $vent->prepare('SELECT placa,vende FROM opera WHERE id=:id LIMIT 1');
        $so->execute(['id' => (int)$cab['opera']]);
        $ope = $so->fetch(PDO::FETCH_ASSOC) ?: [];
        $cab['placa'] = (string)($ope['placa'] ?? '');
        if ((int)($ope['vende'] ?? 0) > 0) {
            $su = $logi->prepare('SELECT nombr,apell FROM usuar WHERE id=:id LIMIT 1');
            $su->execute(['id' => (int)$ope['vende']]);
            $usr = $su->fetch(PDO::FETCH_ASSOC) ?: [];
            $cab['vende_nom'] = trim((string)($usr['nombr'] ?? '') . ' ' . (string)($usr['apell'] ?? ''));
        }
    }

    $doc = [
        'tipo' => 'FACTURA',
        'serie' => (string)($cab['serie'] ?? ''),
        'nume' => (int)($cab['numer'] ?? 0),
        'estado' => (string)($cab['estad'] ?? ''),
        'fecha' => (string)($cab['fecha'] ?? ''),
        'docum' => (string)($cab['docum'] ?? ''),
        'razon' => (string)($cab['razon'] ?? ''),
        'direc' => (string)($cab['direc'] ?? ''),
        'placa' => (string)($cab['placa'] ?? ''),
        'formp' => (string)($cab['formp'] ?? ''),
        'medio' => '',
        'vende_nom' => (string)($cab['vende_nom'] ?? ''),
        'subto' => (float)($cab['subto'] ?? 0),
        'igv' => (float)($cab['igv'] ?? 0),
        'total' => (float)($cab['total'] ?? 0),
        'pie' => 'DOCUMENTO FISCAL',
    ];

    $detNorm = [];
    foreach ($detal as $row) {
        $detNorm[] = [
            'codig' => (string)($row['codig'] ?? ''),
            'descr' => (string)($row['descr'] ?? ''),
            'canti' => (float)($row['canti'] ?? 0),
            'preun' => (float)($row['preun'] ?? 0),
            'total' => (float)($row['total'] ?? 0),
        ];
    }

    return ['doc' => $doc, 'detal' => $detNorm];
}
