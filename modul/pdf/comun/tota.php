<?php
/* ============================================================
   Ruta: modul/pdf/comun/tota.php
   Módulo: PDF / Común

   NIVEL 5.3 — TOTALES
   ------------------------------------------------------------
   Dibuja únicamente los valores monetarios.

   ESTRUCTURA:
   SUBTOTAL
   IGV 18%
   TOTAL

   No genera QR.
   No convierte a letras.
   No consulta la base de datos.
============================================================ */
declare(strict_types=1);

function totl(FPDF $pdf, array $doc, float $y): float
{
    $x = 132;
    $ancho = 68;
    $h = 6;

    $campos = [
        'SUBTOTAL' => $doc['subto'] ?? 0,
        'IGV 18%'  => $doc['igv'] ?? 0,
        'TOTAL'    => $doc['total'] ?? 0,
    ];

    $pdf->SetFont('Courier', 'B', 8.5);
    $pdf->SetDrawColor(120, 120, 120);

    $alto = count($campos) * $h;
    for ($p = $y; $p < $y + $alto; $p += 4) {
        $pdf->Line($x, $p, min($x + 2, $x + 3), $p);
        $pdf->Line($x + $ancho - 2, $p, $x + $ancho, $p);
    }
    for ($p = $y; $p < $y + $alto; $p += 4) {
        $pdf->Line($x, $p, $x, min($p + 2, $y + $alto));
        $pdf->Line($x + $ancho, $p, $x + $ancho, min($p + 2, $y + $alto));
    }

    $yy = $y;
    foreach ($campos as $label => $valor) {
        $pdf->SetXY($x, $yy);
        $pdf->Cell(43, $h, texto($label), 0, 0, 'L');
        $pdf->Cell(25, $h, fnum($valor), 0, 1, 'R');
        $yy += $h;
    }

    return $y + $alto + 2;
}

function qtotl(FPDF $pdf, array $doc, float $y, float $ancho): float
{
    /* NIVEL 5.3.1 — CAJA INFERIOR DE TOTALES */
    $x = 10;
    $xd = 125;
    $h = 30;
    $pdf->SetDrawColor(120, 120, 120);

    $lin = static function (FPDF $pdf, float $a, float $b, float $yy): void {
        for ($x = $a; $x < $b; $x += 4) {
            $pdf->Line($x, $yy, min($x + 2, $b), $yy);
        }
    };

    $lin($pdf, $x, $x + $ancho, $y + $h);
    for ($p = $y; $p < $y + $h; $p += 4) {
        $pdf->Line($x, $p, $x, min($p + 2, $y + $h));
        $pdf->Line($x + $ancho, $p, $x + $ancho, min($p + 2, $y + $h));
    }
    $lin($pdf, $x, $x + $ancho, $y);

    /* NIVEL 5.3.2 — VALORES */
    $campos = [
        'SUBTOTAL S/' => $doc['subto'] ?? 0,
        'IGV 18%' => $doc['igv'] ?? 0,
        'TOTAL S/' => $doc['total'] ?? 0,
    ];

    $pdf->SetFont('Courier', 'B', 8.5);
    $yy = $y + 4;
    foreach ($campos as $lab => $val) {
        $pdf->SetXY($xd, $yy);
        $pdf->Cell(45, 6, texto($lab), 0, 0, 'L');
        $pdf->Cell(15, 6, fnum($val), 0, 1, 'R');
        $yy += 6;
    }

    return $y + $h;
}
