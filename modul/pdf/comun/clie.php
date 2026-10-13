<?php
/* ============================================================
   Ruta: modul/pdf/comun/clie.php
   Módulo: PDF / Común
   NIVEL 3 — DATOS DEL DOCUMENTO / CLIENTE
   ------------------------------------------------------------
   Diseña el bloque dentro de $pag y $blo.
   El bloque conserva su altura; el contenido se adapta.
============================================================ */
declare(strict_types=1);

function clien(
    FPDF $pdf,
    array $doc,
    array $conf,
    array $pag,
    array $blo
): float {
    $x   = (float)$pag['x'];
    $y   = $pdf->GetY();
    $anc = (float)$pag['ancu'];
    $alt = (float)$blo['hclie'];

    $pad = 1.5;
    $gap = 2.0;
    $col = $anc / 2;

    $fisc = esFiscal($doc, $conf);

    /* COLUMNA IZQUIERDA */
    $izq = [
        'FECHA'     => (string)($doc['fecha'] ?? ''),
        'RUC / DNI' => (string)($doc['docum'] ?? ''),
        'CLIENTE'   => (string)($doc['razon'] ?? ''),
        'DIRECCIÓN' => (string)($doc['direc'] ?? ''),
    ];

    /* COLUMNA DERECHA */
    $der = $fisc
        ? [
            'PLACA / MOTOR' => (string)($doc['placa'] ?? ''),
            'FORMA DE PAGO' => (string)($doc['formp'] ?? ''),
            'MEDIO DE PAGO' => (string)($doc['medio'] ?? ''),
            'VENDEDOR'      => (string)($doc['vende_nom'] ?? ''),
        ]
        : [
            'VIGENCIA'      => (string)($doc['vigia'] ?? ''),
            'VENDEDOR'      => (string)($doc['vende_nom'] ?? ''),
            'PLACA / MOTOR' => (string)($doc['placa'] ?? ''),
        ];

    /* MARCO DEL BLOQUE */
    $pdf->SetLineWidth(0.25);
    $pdf->SetDrawColor(120, 120, 120);
    $pdf->Rect($x, $y, $anc, $alt);

    /* ALTURA RESPONSIVE DE FILA */
    $filas = max(
        count($izq),
        count($der)
    );

    $lh = max(
        1,
        ($alt - ($pad * 2)) / $filas
    );

    /* ANCHO DE ETIQUETAS */
    $pdf->SetFont('Courier', 'B', 8.5);

    $ml = 0;
    foreach ($izq as $lab => $_) {
        $ml = max(
            $ml,
            $pdf->GetStringWidth(
                texto($lab)
            )
        );
    }

    $md = 0;
    foreach ($der as $lab => $_) {
        $md = max(
            $md,
            $pdf->GetStringWidth(
                texto($lab)
            )
        );
    }

    $ml += $gap;
    $md += $gap;

    /* COLUMNA IZQUIERDA */
    $yi = $y + $pad;

    foreach ($izq as $lab => $val) {
        $pdf->SetXY(
            $x + $pad,
            $yi
        );

        $pdf->SetFont(
            'Courier',
            'B',
            8.5
        );

        $pdf->Cell(
            $ml,
            $lh,
            texto($lab),
            0,
            0,
            'L'
        );

        $av = max(
            1,
            $col - $pad - $pad - $ml - 4
        );

        $pdf->SetX(
            $x + $pad + $ml
        );

        $pdf->SetFont(
            'Courier',
            '',
            8.5
        );

        $pdf->Cell(
            4,
            $lh,
            ':',
            0,
            0,
            'C'
        );

        $pdf->Cell(
            $av,
            $lh,
            corta(
                $pdf,
                $val,
                $av
            ),
            0,
            0,
            'L'
        );

        $yi += $lh;
    }

/* COLUMNA DERECHA */
$des = 30;

$xd = $x + $col + $des;
$yd = $y + $pad;

/* ANCHO DISPONIBLE DE LA COLUMNA DERECHA */
$av = max(
    1,
    $col - $pad - $pad - $md - 4 - $des
);

foreach ($der as $lab => $val) {

    /* ETIQUETA */
    $pdf->SetXY(
        $xd + $pad,
        $yd
    );

    $pdf->SetFont(
        'Courier',
        'B',
        8.5
    );

    $pdf->Cell(
        $md,
        $lh,
        texto($lab),
        0,
        0,
        'R'
    );

    /* DOS PUNTOS */
    $pdf->Cell(
        4,
        $lh,
        ':',
        0,
        0,
        'C'
    );

    /* VALOR */
    $pdf->SetFont(
        'Courier',
        '',
        8.5
    );

    $pdf->Cell(
        $av,
        $lh,
        corta(
            $pdf,
            $val,
            $av
        ),
        0,
        0,
        'L'
    );

    $yd += $lh;
}

    /* FIN EXACTO DEL BLOQUE */
    return $y + $alt;
}