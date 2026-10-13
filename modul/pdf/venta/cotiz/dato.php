<?php
/* ============================================================
   Ruta: modul/pdf/venta/cotiz/dato.php
   Módulo: PDF / Ventas / Cotización

   NIVEL 1 — ORIGEN DE DATOS
   ------------------------------------------------------------
   Consulta los datos propios de COTIZACIÓN mediante el
   repositorio existente.

   NIVEL 2 — NORMALIZACIÓN
   ------------------------------------------------------------
   Convierte la respuesta del módulo VENTA a la estructura que
   el PDF común entiende.

   ESTE ARCHIVO NO:
   - instancia FPDF
   - dibuja
   - define posiciones
   - genera QR
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/contr/categ/venta-db.php';
require_once dirname(__DIR__, 4) . '/contr/categ/catal-db.php';
require_once dirname(__DIR__, 4) . '/contr/login/login-db.php';
require_once dirname(__DIR__, 4) . '/modul/categ/venta/cotiz/cotiz-repos.php';

function datos(int $id): array
{
    if ($id <= 0) {
        throw new InvalidArgumentException('Cotización no válida.');
    }

    $vent = VENTADB::abrir();
    $cata = CATALDB::abrir();
    $logi = LOGINDB::abrir();

    $repo = new CotizRepo($vent, $cata, $logi);
    $data = $repo->obtener($id);

    if (!is_array($data) || empty($data['cotiz'])) {
        throw new RuntimeException('No se encontró la cotización.');
    }

    $cot = $data['cotiz'];

    /* NIVEL 2 — CABECERA NORMALIZADA */
    $doc = [
        'tipo' => 'COTIZACIÓN',
        'serie' => (string)($cot['serie'] ?? ''),
        'nume' => (int)($cot['numco'] ?? 0),
        'estado' => (string)($cot['estad'] ?? ''),
        'fecha' => (string)($cot['fecha'] ?? ''),
        'docum' => (string)($cot['docum'] ?? ''),
        'razon' => (string)($cot['razon'] ?? ''),
        'direc' => (string)($cot['direc'] ?? ''),
        'placa' => (string)($cot['placa'] ?? ''),
        'vigia' => (string)($cot['vigia'] ?? ''),
        'vende_nom' => (string)($cot['vende_nom'] ?? ''),
        'formp' => '',
        'medio' => '',
        'subto' => (float)($cot['subto'] ?? 0),
        'igv' => (float)($cot['igv'] ?? 0),
        'total' => (float)($cot['total'] ?? 0),
        'pie' => 'Cotización informativa. Vigencia según fecha indicada.',
    ];

    /* NIVEL 2 — DETALLE NORMALIZADO */
    $detal = [];
    foreach (($data['detalles'] ?? []) as $row) {
        $detal[] = [
            'codig' => (string)($row['codig'] ?? ''),
            'descr' => (string)($row['descr'] ?? ''),
            'canti' => (float)($row['canti'] ?? 0),
            'preun' => (float)($row['preun'] ?? 0),
            'total' => (float)($row['total'] ?? 0),
        ];
    }

    return ['doc' => $doc, 'detal' => $detal];
}
