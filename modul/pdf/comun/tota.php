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
    /* Reutiliza el mismo cuadro discontinuo que Factura y Boleta.
       Cotización no dibuja QR; solo comparte la presentación de totales. */
    return qtotl($pdf, $doc, $y, 190.0);
}

function qtotl(FPDF $pdf, array $doc, float $y, float $ancho): float
{
    /* NIVEL 5.3.1 — CAJA INFERIOR DE TOTALES */
    $x = 10;
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
        'IGV 18%'     => $doc['igv'] ?? 0,
        'TOTAL S/'    => $doc['total'] ?? 0,
    ];

    /* Alineación con la columna TOTAL de la tabla de productos.
       Los importes comparten un mismo borde derecho para alinear sus puntos
       decimales, con una pequeña entrada respecto al borde de la columna. */
    $colsDet = [10.0, 28.0, 89.0, 16.0, 22.0, 25.0];
    $xTotal = 10.0 + array_sum(array_slice($colsDet, 0, 5));
    $centroTotal = $xTotal + ($colsDet[5] / 2);

    $pdf->SetFont('Courier', 'B', 8.5);
    $anchoEtq = $pdf->GetStringWidth('SUBTOTAL S/') + 1.0;
    $anchoDos = 2.0;
    $fmt = [];
    foreach ($campos as $lab => $val) {
        $fmt[$lab] = fnum($val);
    }
    $anchoVal = max(array_map(
        static fn(string $v): float => $pdf->GetStringWidth($v),
        array_values($fmt)
    )) + 1.5;

    /* Mantiene el centro visual de un importe habitual sobre la columna
       TOTAL, pero acerca el campo numérico al separador ':' sin perder
       la alineación decimal entre las tres cantidades. */
    $anchoTexto = $pdf->GetStringWidth('000.00');
    $finVal = $centroTotal + ($anchoTexto / 2);
    $iniVal = $finVal - $anchoVal;
    $iniDos = $iniVal - $anchoDos;
    $iniEtq = $iniDos - $anchoEtq;

    $yy = $y + 4;
    foreach ($campos as $lab => $val) {
        $pdf->SetXY($iniEtq, $yy);
        $pdf->Cell($anchoEtq, 6, texto($lab), 0, 0, 'L');
        $pdf->SetXY($iniDos, $yy);
        $pdf->Cell($anchoDos, 6, ':', 0, 0, 'C');
        $pdf->SetXY($iniVal, $yy);
        $pdf->Cell($anchoVal, 6, $fmt[$lab], 0, 1, 'R');
        $yy += 6;
    }

    return $y + $h;
}
