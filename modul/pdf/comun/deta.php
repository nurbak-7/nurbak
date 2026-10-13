<?php
/* ============================================================
   Ruta: modul/pdf/comun/deta.php
   Módulo: PDF / Común

   NIVEL 4 — DETALLE
   ------------------------------------------------------------
   Dibuja la tabla común de productos/documentos.

   ESTRUCTURA VISUAL:
   IT | CODIGO | DESCRIPCION | QTY | P.UNIT | TOTAL

   La categoría solamente entrega las filas normalizadas.
   Este archivo decide posiciones, anchos, fuente, alineación
   y saltos de página.
============================================================ */
declare(strict_types=1);

function cabec(FPDF $pdf, float $y): float
{
    /* NIVEL 4.1 — ESTRUCTURA DE CABECERA DE TABLA */
    $x = 10;
    $ancho = 190;
    $h = 6;
    $cols = [10, 28, 89, 16, 22, 25];
    $tit = ['IT', 'CODIGO', 'DESCRIPCION', 'QTY', 'P.UNIT', 'TOTAL'];

    $pdf->SetFont('Courier', 'B', 8);
    $pdf->SetTextColor(0, 0, 0);

    $linea = static function (FPDF $pdf, float $x1, float $x2, float $y1): void {
        for ($p = $x1; $p < $x2; $p += 4) {
            $pdf->Line($p, $y1, min($p + 2, $x2), $y1);
        }
    };

    $linea($pdf, $x, $x + $ancho, $y);
    $linea($pdf, $x, $x + $ancho, $y + $h);

    $px = $x;
    foreach ($cols as $w) {
        for ($p = $y; $p < $y + $h; $p += 4) {
            $pdf->Line($px, $p, $px, min($p + 2, $y + $h));
        }
        $px += $w;
    }
    $pdf->Line($x + $ancho, $y, $x + $ancho, $y + $h);

    $pdf->SetXY($x, $y);
    foreach ($cols as $i => $w) {
        $pdf->Cell($w, $h, texto($tit[$i]), 0, $i === count($cols) - 1 ? 1 : 0, 'C');
    }

    return $y + $h;
}

function tabla(FPDF $pdf, array $detal, float $y, float $lim = 258): float
{
    /* NIVEL 4.2 — CUERPO DE DETALLE */
    $x = 10;
    $h = 6;
    $cols = [10, 28, 89, 16, 22, 25];

    $y = cabec($pdf, $y);
    $pdf->SetFont('Courier', '', 8);

    foreach ($detal as $i => $row) {
        /* NIVEL 4.3 — SALTO DE PÁGINA */
        if ($y > $lim) {
            $pdf->AddPage();
            $y = 15;
            $y = cabec($pdf, $y);
            $pdf->SetFont('Courier', '', 8);
        }

        /* NIVEL 4.4 — FILA */
        if ($i % 2 === 0) {
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Rect($x, $y, array_sum($cols), $h, 'F');
        }

        $descr = corta($pdf, (string)($row['descr'] ?? ''), 85);
        $vals = [
            (string)($i + 1),
            (string)($row['codig'] ?? ''),
            $descr,
            fnum($row['canti'] ?? 0),
            fnum($row['preun'] ?? 0),
            fnum($row['total'] ?? 0),
        ];

        $pdf->SetXY($x, $y);
        foreach ($cols as $j => $w) {
            $ali = in_array($j, [0, 3], true) ? 'C' : ($j >= 4 ? 'R' : 'L');
            $pdf->Cell($w, $h, texto($vals[$j]), 0, $j === count($cols) - 1 ? 1 : 0, $ali);
        }
        $y += $h;
    }

    return $y + 4;
}
