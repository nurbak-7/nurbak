<?php
/* ============================================================
   Ruta: modul/pdf/comun/cabe.php
   Módulo: PDF / Común
   NIVEL 2 — CABECERA
   ------------------------------------------------------------
   Diseña la cabecera dentro de $pag y $blo.
   No define página, márgenes ni dimensiones externas.
============================================================ */
declare(strict_types=1);

function encab(
    FPDF $pdf,
    array $doc,
    array $conf,
    array $pag,
    array $blo
): float {
    $cfg = $conf['empresa'] ?? [];

    $x   = (float)$pag['x'];
    $y   = (float)$pag['y'];
    $anc = (float)$pag['ancu'];
    $alt = (float)$blo['hcabe'];

    $pad    = 1.5;
    $logoAnc = 21.0;
    $docAnc  = 50.0;
    $sep     = 19.0;
    $empAnc  = max(1, $anc - $logoAnc - $sep - $docAnc);

    $xEmp = $x + $logoAnc + $sep;
    $xDoc = $x + $anc - $docAnc;

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.25);

    /* LOGO */
    $logo = trim((string)($cfg['logo'] ?? ''));

    if (
        esFiscal($doc, $conf) &&
        $logo !== '' &&
        is_file($logo)
    ) {
        $tam = max(
            1,
            min(
                $logoAnc,
                $alt - ($pad * 2)
            )
        );

        $pdf->Image(
            $logo,
            $x + $pad,
            $y + $pad,
            $tam,
            $tam
        );
    }

    /* EMPRESA */
    $pdf->SetXY($xEmp, $y);
    $pdf->SetFont('Courier', 'B', 20);
    $pdf->Cell(
        $empAnc,
        6,
        corta(
            $pdf,
            (string)($cfg['nombre'] ?? ''),
            $empAnc
        ),
        0,
        1,
        'C'
    );

    $pdf->SetX($xEmp);
    $pdf->SetFont('Courier', 'B', 8.5);
    $pdf->Cell(
        $empAnc,
        6,
        corta(
            $pdf,
            (string)($cfg['activi'] ?? ''),
            $empAnc
        ),
        0,
        1,
        'C'
    );

    $pdf->SetX($xEmp);
    $pdf->SetFont('Courier', 'B', 7.5);
    $pdf->Cell(
        $empAnc,
        6,
        corta(
            $pdf,
            (string)($cfg['direc'] ?? ''),
            $empAnc
        ),
        0,
        1,
        'C'
    );

    /* DOCUMENTO */
    $docAlt = max(
        1,
        $alt - ($pad * 2)
    );

    $pdf->Rect(
        $xDoc + $pad,
        $y + $pad,
        $docAnc - ($pad * 2),
        $docAlt
    );

    $tip = (string)($doc['tipo'] ?? 'DOCUMENTO');
    $ser = (string)($doc['serie'] ?? '');
    $num = (int)($doc['nume'] ?? 0);

    $docn = $ser !== ''
        ? $ser . '-' . str_pad(
            (string)$num,
            4,
            '0',
            STR_PAD_LEFT
        )
        : str_pad(
            (string)$num,
            4,
            '0',
            STR_PAD_LEFT
        );

    $w = $docAnc - ($pad * 2);

    /* RUC */
    $pdf->SetXY(
        $xDoc + $pad,
        $y + $pad + 1
    );

    $pdf->SetFont('Courier', 'B', 8.5);
    $pdf->Cell(
        $w,
        5,
        texto(
            'R.U.C. ' .
            (string)($cfg['ruc'] ?? '')
        ),
        0,
        1,
        'C'
    );

    /* TIPO */
    $pdf->SetFillColor(235, 235, 235);
    $pdf->SetX($xDoc + $pad);
    $pdf->Cell(
        $w,
        6,
        texto($tip),
        0,
        1,
        'C',
        true
    );

    /* SERIE / NÚMERO */
    $pdf->SetFont('Courier', 'B', 9);
    $pdf->SetX($xDoc + $pad);
    $pdf->Cell(
        $w,
        6,
        texto($docn),
        0,
        1,
        'C'
    );

    /* ESTADO */
    $est = trim((string)($doc['estado'] ?? ''));

    if ($est !== '') {
        $pdf->SetFont('Courier', '', 7.5);
        $pdf->SetXY(
            $xDoc + $pad,
            $y + $alt - $pad - 4
        );
        $pdf->Cell(
            $w,
            4,
            texto($est),
            0,
            0,
            'C'
        );
    }

    return $y + $alt;
}