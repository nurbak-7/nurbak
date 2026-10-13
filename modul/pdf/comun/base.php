<?php
/* ============================================================
   Ruta: modul/pdf/comun/base.php
   Módulo: PDF / Común
   FUNCIÓN: Coordinador de ESTRUCTURA del PDF.
   ------------------------------------------------------------
   La PÁGINA contiene los BLOQUES.
   Cada bloque define su propio rectángulo:
   X, Y, ancho y alto.

   Los archivos comunes solo diseñan dentro del bloque recibido.
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/libre/fpdf/fpdf.php';

require_once __DIR__ . '/cabe.php';
require_once __DIR__ . '/clie.php';
require_once __DIR__ . '/deta.php';
require_once __DIR__ . '/letra.php';
require_once __DIR__ . '/tota.php';
require_once __DIR__ . '/qr.php';
require_once __DIR__ . '/pie.php';

/* ============================================================
   PÁGINA
============================================================ */
$pag = [
    'anc'  => 210,
    'alt'  => 297,
    'mizq' => 10,
    'mder' => 10,
    'msup' => 10,
    'minf' => 10,
    'x'    => 10,
    'y'    => 10,
    'ancu' => 190,
    'limy' => 287,
];

/* ============================================================
   BLOQUES
   ------------------------------------------------------------
   Cada bloque es un contenedor real dentro de la página.
   El bloque no cambia de tamaño para acomodar el diseño.
============================================================ */
$blo = [
    'cabe' => [
        'x'   => $pag['x'],
        'y'   => $pag['y'],
        'anc' => $pag['ancu'],
        'alt' => 24,
    ],

    'clie' => [
        'x'   => $pag['x'],
        'y'   => 0,
        'anc' => $pag['ancu'],
        'alt' => 24,
    ],

    'deta' => [
        'x'    => $pag['x'],
        'y'    => 0,
        'anc'  => $pag['ancu'],
        'alt'  => 0,
        'henca'=> 8,
        'hfila'=> 5,
    ],

    'letr' => [
        'x'   => $pag['x'],
        'y'   => 0,
        'anc' => $pag['ancu'],
        'alt' => 5,
    ],

    'qr' => [
        'x'   => $pag['x'],
        'y'   => 0,
        'anc' => $pag['ancu'],
        'alt' => 25,
    ],

    'tota' => [
        'x'   => $pag['x'],
        'y'   => 0,
        'anc' => $pag['ancu'],
        'alt' => 15,
    ],

    'pie' => [
        'x'   => $pag['x'],
        'y'   => 0,
        'anc' => $pag['ancu'],
        'alt' => 15,
    ],
];

/* ============================================================
   SEPARACIONES
   ------------------------------------------------------------
   Son independientes de las alturas de los bloques.
============================================================ */
$esp = [
    'cabe_clie' => 8,
    'clie_deta' => 10,
    'gral'      => 3,
    'tota_pie'  => 10,
];

/* ============================================================
   UTILIDAD — TEXTO
============================================================ */
function texto(string $valor): string
{
    $valor = trim($valor);

    if ($valor === '') {
        return '';
    }

    $conv = @iconv('UTF-8', 'windows-1252//TRANSLIT', $valor);

    return $conv !== false ? $conv : $valor;
}

/* ============================================================
   UTILIDAD — TEXTO SEGÚN ANCHO
============================================================ */
function corta(
    FPDF $pdf,
    string $valor,
    float $ancho,
    string $suf = '...'
): string {
    $valor = texto($valor);

    if ($valor === '' || $pdf->GetStringWidth($valor) <= $ancho) {
        return $valor;
    }

    $n = strlen($valor);

    while (
        $n > 1 &&
        $pdf->GetStringWidth(substr($valor, 0, $n) . $suf) > $ancho
    ) {
        $n--;
    }

    return rtrim(substr($valor, 0, $n)) . $suf;
}

/* ============================================================
   UTILIDAD — NÚMERO
============================================================ */
function fnum(mixed $valor): string
{
    return number_format((float)$valor, 2, '.', '');
}

/* ============================================================
   COORDINACIÓN DE BLOQUES
============================================================ */
function armaBlo(array &$blo, array $pag, array $esp, bool $fisc): void
{
    $blo['cabe']['y'] = $pag['y'];

    $blo['clie']['y'] =
        $blo['cabe']['y'] +
        $blo['cabe']['alt'] +
        $esp['cabe_clie'];

    $blo['pie']['y'] =
        $pag['limy'] -
        $blo['pie']['alt'];

    if ($fisc) {
        $blo['tota']['y'] =
            $blo['pie']['y'] -
            $esp['tota_pie'] -
            $blo['tota']['alt'];

        $blo['qr']['y'] = $blo['tota']['y'];

        $blo['letr']['y'] =
            $blo['tota']['y'] -
            $esp['gral'] -
            $blo['letr']['alt'];

        $fin = $blo['letr']['y'];
    } else {
        $blo['tota']['y'] =
            $blo['pie']['y'] -
            $esp['tota_pie'] -
            $blo['tota']['alt'];

        $fin = $blo['tota']['y'];
    }

    $blo['deta']['y'] =
        $blo['clie']['y'] +
        $blo['clie']['alt'] +
        $esp['clie_deta'];

    $blo['deta']['alt'] = max(
        0,
        $fin - $blo['deta']['y']
    );
}

/* ============================================================
   COORDINADOR GENERAL
============================================================ */
function emite(
    array $doc,
    array $detal,
    array $conf
): void {
    global $pag, $blo, $esp;

    $fisc = esFiscal($doc, $conf);

    $pdf = new FPDF(
        $conf['orie'],
        $conf['unit'],
        $conf['page']
    );

    $pdf->SetAutoPageBreak(false, 0);

    $tipo = (string)($doc['tipo'] ?? 'DOCUMENTO');
    $serie = (string)($doc['serie'] ?? '');
    $nume = (int)($doc['nume'] ?? 0);

    $num = $serie !== ''
        ? $serie . '-' . str_pad((string)$nume, 4, '0', STR_PAD_LEFT)
        : str_pad((string)$nume, 4, '0', STR_PAD_LEFT);

    $pdf->SetTitle($tipo . ' ' . $num);
    $pdf->SetAuthor((string)($conf['empresa']['nombre'] ?? ''));
    $pdf->SetCreator('Nurbak');
    $pdf->SetMargins($pag['mizq'], $pag['msup'], $pag['mder']);

    /* PÁGINA */
    $pdf->AddPage();

    armaBlo($blo, $pag, $esp, $fisc);

    /* CABECERA */
    $pCab = [
        'x'    => $blo['cabe']['x'],
        'y'    => $blo['cabe']['y'],
        'ancu' => $blo['cabe']['anc'],
    ];

    $bCab = [
        'hcabe' => $blo['cabe']['alt'],
    ];

    $pdf->SetXY(
        $blo['cabe']['x'],
        $blo['cabe']['y']
    );

    encab(
        $pdf,
        $doc,
        $conf,
        $pCab,
        $bCab
    );

    /* CLIENTE */
    $pCli = [
        'x'    => $blo['clie']['x'],
        'y'    => $blo['clie']['y'],
        'ancu' => $blo['clie']['anc'],
    ];

    $bCli = [
        'hclie' => $blo['clie']['alt'],
    ];

    $pdf->SetXY(
        $blo['clie']['x'],
        $blo['clie']['y']
    );

    clien(
        $pdf,
        $doc,
        $conf,
        $pCli,
        $bCli
    );

    /* DETALLE */
    $ini = $blo['deta']['y'];
    $lim = $blo['deta']['y'] + $blo['deta']['alt'];

    $pdf->SetXY(
        $blo['deta']['x'],
        $blo['deta']['y']
    );

    $y = tabla(
        $pdf,
        $detal,
        $ini,
        $lim
    );

    /* Si el detalle agotó el contenedor reservado, la tabla o el
       proceso de detalle puede haber dejado la posición fuera del
       cierre. En ese caso se abre la siguiente página. */
    if ($y > $lim) {
        $pdf->AddPage();
        $pdf->SetXY($pag['x'], $pag['y']);

        armaBlo($blo, $pag, $esp, $fisc);

        $pCab['y'] = $blo['cabe']['y'];
        $pCli['y'] = $blo['clie']['y'];

        $pdf->SetXY(
            $blo['cabe']['x'],
            $blo['cabe']['y']
        );
        encab($pdf, $doc, $conf, $pCab, $bCab);

        $pdf->SetXY(
            $blo['clie']['x'],
            $blo['clie']['y']
        );
        clien($pdf, $doc, $conf, $pCli, $bCli);
    }

    /* CIERRE */
    if ($fisc) {
        letr(
            $pdf,
            $doc,
            $blo['letr']['y'],
            $blo['letr']['anc']
        );

        qrco(
            $pdf,
            $doc,
            $conf,
            $blo['qr']['y'],
            $blo['qr']['anc']
        );

        qtotl(
            $pdf,
            $doc,
            $blo['tota']['y'],
            $blo['tota']['anc']
        );
    } else {
        totl(
            $pdf,
            $doc,
            $blo['tota']['y']
        );
    }

    /* PIE */
    $pdf->SetXY(
        $blo['pie']['x'],
        $blo['pie']['y']
    );

    pie(
        $pdf,
        $doc
    );

    $nom = preg_replace(
        '/[^A-Za-z0-9_-]/',
        '',
        $num
    ) ?: 'documento';

    $pdf->Output(
        'I',
        'PDF-' . $nom . '.pdf'
    );
}

/* ============================================================
   REGLA COMÚN — DOCUMENTO FISCAL
============================================================ */
function esFiscal(
    array $doc,
    array $conf
): bool {
    $tipo = strtoupper(
        trim((string)($doc['tipo'] ?? ''))
    );

    $lista = $conf['fisc'] ?? [];

    return in_array($tipo, $lista, true);
}
