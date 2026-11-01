<?php
/* ============================================================
   Ruta: modul/pdf/venta/cotiz/dato.php
   Módulo: PDF / Ventas / Cotización

   NIVEL 1 — ORIGEN DE DATOS
   ------------------------------------------------------------
   Consulta directamente las tablas de VENTA, CATAL y LOGIN.

   NIVEL 2 — NORMALIZACIÓN
   ------------------------------------------------------------
   Entrega la cabecera y el detalle preparados para el PDF.

   ESTE ARCHIVO NO:
   - utiliza repositorios del módulo comercial
   - instancia FPDF
   - dibuja ni define posiciones
   - modifica cotizaciones o existencias
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/contr/categ/venta-db.php';
require_once dirname(__DIR__, 4) . '/contr/categ/catal-db.php';
require_once dirname(__DIR__, 4) . '/contr/login/login-db.php';

function datos(int $id): array
{
    if ($id <= 0) {
        throw new InvalidArgumentException('Cotización no válida.');
    }

    $vent = VENTADB::abrir();
    $cata = CATALDB::abrir();
    $logi = LOGINDB::abrir();

    /* NIVEL 1.1 — CABECERA Y CLIENTE */
    $st = $vent->prepare(
        'SELECT
            c.id,
            c.clien,
            c.vende,
            c.placa,
            c.serie,
            c.numco,
            c.fecha,
            c.vigia,
            c.estad,
            c.obser,
            c.subto,
            c.igv,
            c.total,
            cl.docum,
            cl.razon,
            cl.direc
         FROM cotiz c
         INNER JOIN clien cl ON cl.id = c.clien
         WHERE c.id = :id
         LIMIT 1'
    );
    $st->execute(['id' => $id]);
    $cab = $st->fetch(PDO::FETCH_ASSOC);

    if (!$cab) {
        throw new RuntimeException('No se encontró la cotización.');
    }

    /* NIVEL 1.2 — DETALLE */
    $sd = $vent->prepare(
        'SELECT id,produ,codig,descr,canti,preun,total
         FROM dtall
         WHERE cotiz = :id
         ORDER BY id ASC'
    );
    $sd->execute(['id' => $id]);
    $detal = $sd->fetchAll(PDO::FETCH_ASSOC);

    /* NIVEL 1.3 — DATOS DE CATÁLOGO COMO RESPALDO */
    $ids = [];
    foreach ($detal as $row) {
        $prod = (int)($row['produ'] ?? 0);
        if ($prod > 0) {
            $ids[] = $prod;
        }
    }
    $ids = array_values(array_unique($ids));

    $map = [];
    if ($ids !== []) {
        $ph = [];
        $par = [];

        foreach ($ids as $i => $prod) {
            $key = 'p' . $i;
            $ph[] = ':' . $key;
            $par[$key] = $prod;
        }

        $sp = $cata->prepare(
            'SELECT id,codig,descr
             FROM produ
             WHERE id IN (' . implode(',', $ph) . ')'
        );
        $sp->execute($par);

        foreach ($sp->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int)$row['id']] = $row;
        }
    }

    /* Los valores guardados en el detalle tienen prioridad histórica. */
    foreach ($detal as &$row) {
        $prod = $map[(int)($row['produ'] ?? 0)] ?? [];

        $row['codig'] = $row['codig'] !== null
            ? (string)$row['codig']
            : (string)($prod['codig'] ?? '');

        $row['descr'] = $row['descr'] !== null
            ? (string)$row['descr']
            : (string)($prod['descr'] ?? '');
    }
    unset($row);

    /* NIVEL 1.4 — VENDEDOR */
    $vendeNom = '';
    $vendeId = (int)($cab['vende'] ?? 0);

    if ($vendeId > 0) {
        $sv = $logi->prepare(
            'SELECT nombr,apell
             FROM usuar
             WHERE id = :id
             LIMIT 1'
        );
        $sv->execute(['id' => $vendeId]);
        $usr = $sv->fetch(PDO::FETCH_ASSOC) ?: [];

        $nom = preg_split(
            '/\s+/u',
            trim((string)($usr['nombr'] ?? '')),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        $ape = preg_split(
            '/\s+/u',
            trim((string)($usr['apell'] ?? '')),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        $vendeNom = trim(($nom[0] ?? '') . ' ' . ($ape[0] ?? ''));
    }

    /* NIVEL 2.1 — CABECERA NORMALIZADA */
    $doc = [
        'tipo' => 'COTIZACIÓN',
        'serie' => (string)($cab['serie'] ?? ''),
        'nume' => (int)($cab['numco'] ?? 0),
        'estado' => (string)($cab['estad'] ?? ''),
        'fecha' => (string)($cab['fecha'] ?? ''),
        'vigia' => (string)($cab['vigia'] ?? ''),
        'docum' => (string)($cab['docum'] ?? ''),
        'razon' => (string)($cab['razon'] ?? ''),
        'direc' => (string)($cab['direc'] ?? ''),
        'placa' => (string)($cab['placa'] ?? ''),
        'obser' => (string)($cab['obser'] ?? ''),
        'vende_nom' => $vendeNom,
        'formp' => '',
        'medio' => '',
        'subto' => (float)($cab['subto'] ?? 0),
        'igv' => (float)($cab['igv'] ?? 0),
        'total' => (float)($cab['total'] ?? 0),
        'pie' => 'Cotización informativa. Vigencia según fecha indicada.',
    ];

    /* NIVEL 2.2 — DETALLE NORMALIZADO */
    $detNorm = [];
    foreach ($detal as $row) {
        $detNorm[] = [
            'codig' => (string)($row['codig'] ?? ''),
            'descr' => (string)($row['descr'] ?? ''),
            /* Mantener el DECIMAL original; el PDF decide su formato. */
            'canti' => (string)($row['canti'] ?? '0'),
            'preun' => (string)($row['preun'] ?? '0'),
            'total' => (string)($row['total'] ?? '0'),
        ];
    }

    return ['doc' => $doc, 'detal' => $detNorm];
}
