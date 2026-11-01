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

    /* Etiqueta, dos puntos y valor en columnas compactas.
       Los tres importes comienzan en la misma coordenada. */
    $anchoEtq = 22.5;
    $anchoDos = 2.5;
    $anchoVal = 24.0;
    $anchoBlo = $anchoEtq + $anchoDos + $anchoVal;
    $xd = $x + $ancho - 15.0 - $anchoBlo;

    $pdf->SetFont('Courier', 'B', 8.5);
    $yy = $y + 4;
    foreach ($campos as $lab => $val) {
        $pdf->SetXY($xd, $yy);
        $pdf->Cell($anchoEtq, 6, texto($lab), 0, 0, 'L');
        $pdf->Cell($anchoDos, 6, ':', 0, 0, 'C');
        $pdf->Cell($anchoVal, 6, fnum($val), 0, 1, 'L');
        $yy += 6;
    }

    return $y + $h;
}
