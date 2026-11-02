<?php
/* ============================================================
   Ruta: modul/pdf/venta/nvent/dato.php
   Módulo: PDF / Ventas / Nota de Venta

   NIVEL 1 — ORIGEN DE DATOS
   ------------------------------------------------------------
   Consulta directamente VENTA.notav y VENTA.detnv.
   Complementa código y descripción desde CATAL.produ y el
   nombre del vendedor desde LOGIN.usuar.

   NIVEL 2 — NORMALIZACIÓN
   ------------------------------------------------------------
   Entrega exclusivamente datos al generador PDF común.
   No dibuja, no usa FPDF y no genera QR.
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/contr/categ/venta-db.php';
require_once dirname(__DIR__, 4) . '/contr/categ/catal-db.php';
require_once dirname(__DIR__, 4) . '/contr/login/login-db.php';

function datos(int $id): array
{
    if ($id <= 0) {
        throw new InvalidArgumentException('Nota de Venta no válida.');
    }

    $vent = VENTADB::abrir();
    $cata = CATALDB::abrir();
    $logi = LOGINDB::abrir();

    /* NIVEL 1.1 — CABECERA DE NOTA DE VENTA */
    $st = $vent->prepare(
        'SELECT id,vende,placa,docum,razon,direc,serie,numer,estad,fecha,
                subto,igv,total,formp,medi,obser
         FROM notav
         WHERE id = :id
         LIMIT 1'
    );
    $st->execute(['id' => $id]);
    $cab = $st->fetch(PDO::FETCH_ASSOC);

    if (!$cab) {
        throw new RuntimeException('No se encontró la Nota de Venta.');
    }

    /* NIVEL 1.2 — DETALLE DE NOTA DE VENTA */
    $sd = $vent->prepare(
        'SELECT produ,canti,preun,subto
         FROM detnv
         WHERE notav = :id
         ORDER BY id ASC'
    );
    $sd->execute(['id' => $id]);
    $detal = $sd->fetchAll(PDO::FETCH_ASSOC);

    /* NIVEL 1.3 — CÓDIGOS Y DESCRIPCIONES DEL CATÁLOGO */
    $ids = [];
    foreach ($detal as $row) {
        $produ = (int)($row['produ'] ?? 0);
        if ($produ > 0) {
            $ids[] = $produ;
        }
    }
    $ids = array_values(array_unique($ids));

    $prodMap = [];
    if ($ids !== []) {
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $sp = $cata->prepare(
            "SELECT id,codig,descr FROM produ WHERE id IN ($marcadores)"
        );
        $sp->execute($ids);

        foreach ($sp->fetchAll(PDO::FETCH_ASSOC) as $prod) {
            $prodMap[(int)$prod['id']] = [
                'codig' => (string)($prod['codig'] ?? ''),
                'descr' => (string)($prod['descr'] ?? ''),
            ];
        }
    }

    /* NIVEL 1.4 — VENDEDOR */
    $vendeNom = '';
    $vendeId = (int)($cab['vende'] ?? 0);

    if ($vendeId > 0) {
        $su = $logi->prepare(
            'SELECT nombr,apell FROM usuar WHERE id = :id LIMIT 1'
        );
        $su->execute(['id' => $vendeId]);
        $usr = $su->fetch(PDO::FETCH_ASSOC) ?: [];

        $nombres = preg_split(
            '/\s+/u',
            trim((string)($usr['nombr'] ?? '')),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];
        $apellidos = preg_split(
            '/\s+/u',
            trim((string)($usr['apell'] ?? '')),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        $vendeNom = trim(($nombres[0] ?? '') . ' ' . ($apellidos[0] ?? ''));
    }

    /* NIVEL 2.1 — CABECERA NORMALIZADA */
    $doc = [
        'tipo'      => 'NOTA DE VENTA',
        'serie'     => (string)($cab['serie'] ?? ''),
        'nume'      => (int)($cab['numer'] ?? 0),
        'estado'    => (string)($cab['estad'] ?? ''),
        'fecha'     => (string)($cab['fecha'] ?? ''),
        'docum'     => (string)($cab['docum'] ?? ''),
        'razon'     => (string)($cab['razon'] ?? ''),
        'direc'     => (string)($cab['direc'] ?? ''),
        'placa'     => (string)($cab['placa'] ?? ''),
        'vende_nom' => $vendeNom,
        'formp'     => (string)($cab['formp'] ?? ''),
        'medio'     => (string)($cab['medi'] ?? ''),
        'obser'     => (string)($cab['obser'] ?? ''),
        'subto'     => (float)($cab['subto'] ?? 0),
        'igv'       => (float)($cab['igv'] ?? 0),
        'total'     => (float)($cab['total'] ?? 0),
        'pie'       => 'Documento interno de Nota de Venta.',
    ];

    /* NIVEL 2.2 — DETALLE NORMALIZADO */
    $detNorm = [];
    foreach ($detal as $row) {
        $prod = $prodMap[(int)($row['produ'] ?? 0)] ?? [];

        $detNorm[] = [
            'codig' => (string)($prod['codig'] ?? ''),
            'descr' => (string)($prod['descr'] ?? ''),
            'canti' => (float)($row['canti'] ?? 0),
            'preun' => (float)($row['preun'] ?? 0),
            'total' => (float)($row['subto'] ?? 0),
        ];
    }

    return ['doc' => $doc, 'detal' => $detNorm];
}
