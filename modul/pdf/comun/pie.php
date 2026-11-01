<?php
/* ============================================================
   Ruta: modul/pdf/comun/pie.php
   Módulo: PDF / Común

   NIVEL 6 — PIE
   ------------------------------------------------------------
   Observación: Factura, Boleta y Cotización.
   Cuentas y marcas: uso exclusivo de Cotización.
============================================================ */
declare(strict_types=1);

function pie(
    FPDF $pdf,
    array $doc,
    array $conf = [],
    array $blo = []
): void {
    $tipo = strtoupper(trim((string)($doc['tipo'] ?? '')));
    $cotiz = in_array($tipo, ['COTIZACIÓN', 'COTIZACION'], true);
    $fisc = in_array($tipo, $conf['fisc'] ?? [], true);
    $y = (float)($blo['y'] ?? 272.0);

    /* OBSERVACIÓN — LOS TRES DOCUMENTOS */
    $obs = trim((string)($doc['obser'] ?? ''));
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Courier', 'B', 7.2);
    $yObs = $fisc ? $y + 9.0 : $y + 0.5;
    $pdf->SetXY(10, $yObs);
    $pdf->Cell(27, 3.8, texto('OBSERVACIÓN:'), 0, 0, 'L');

    $pdf->SetFont('Courier', '', 7.0);
    $pdf->SetXY(38, $yObs);
    if ($fisc) {
        /* El cierre fiscal deja una sola línea debajo del marco QR/totales. */
        $pdf->Cell(162, 3.4, corta($pdf, $obs, 162), 0, 1, 'L');
    } elseif ($obs !== '') {
        $pdf->MultiCell(162, 3.4, texto($obs), 0, 'L');
    } else {
        $pdf->Cell(162, 3.4, '', 0, 1, 'L');
    }

    /* CUENTAS BANCARIAS — SOLO COTIZACIÓN */
    if ($cotiz) {
        $emp = $conf['empresa'] ?? [];
        $cuen = $emp['cuen'] ?? [];
        $tit = $cuen['titu'] ?? ['FORMAS DE PAGO', 'CUENTA CORRIENTE', 'CUENTA CCI'];
        $fil = $cuen['fila'] ?? [];
        $yCuen = max($y + 7.0, $pdf->GetY() + 1.5);

        if ($fil !== []) {
            $x = 10.0;
            $cols = [48.0, 71.0, 71.0];
            $hh = 5.0;
            $hr = 5.0;
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetLineWidth(0.2);

            $px = $x;
            $pdf->SetFont('Courier', 'B', 7.5);
            foreach ($cols as $i => $cw) {
                $pdf->SetFillColor(0, 0, 0);
                $pdf->Rect($px, $yCuen, $cw, $hh, 'DF');
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetXY($px + 0.5, $yCuen);
                $pdf->Cell($cw - 1, $hh, texto((string)($tit[$i] ?? '')), 0, 0, 'C');
                $px += $cw;
            }

            $pdf->SetTextColor(0, 0, 0);
            $yy = $yCuen + $hh;
            foreach ($fil as $fila) {
                $vals = [
                    (string)($fila['banc'] ?? ''),
                    (string)($fila['cta'] ?? ''),
                    (string)($fila['cci'] ?? ''),
                ];
                $px = $x;
                foreach ($cols as $i => $cw) {
                    $pdf->Rect($px, $yy, $cw, $hr);
                    $pdf->SetFont('Courier', $i === 0 ? 'B' : '', 7.5);
                    $pdf->SetXY($px + 0.5, $yy);
                    $pdf->Cell($cw - 1, $hr, corta($pdf, $vals[$i], $cw - 2), 0, 0, 'C');
                    $px += $cw;
                }
                $yy += $hr;
            }
            $yMarc = $yy + 4.0;
        } else {
            $yMarc = $yCuen;
        }

        /* MARCAS — 8 COLUMNAS × 2 FILAS */
        $marc = $emp['marc'] ?? [];
        if ($marc !== []) {
            $x = 12.0;
            $w = 186.0;
            $cw = $w / 8;
            $alto = 15.0;

            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetLineWidth(0.65);
            $pdf->Line($x, $yMarc, $x, $yMarc + $alto);
            $pdf->Line($x + $w, $yMarc, $x + $w, $yMarc + $alto);

            foreach (array_slice($marc, 0, 2) as $fila => $items) {
                foreach (array_slice($items, 0, 8) as $col => $item) {
                    $color = $item['colo'] ?? [0, 0, 0];
                    $color = array_pad(array_slice($color, 0, 3), 3, 0);
                    $pdf->SetTextColor((int)$color[0], (int)$color[1], (int)$color[2]);
                    $pdf->SetFont('Courier', 'B', $fila === 0 ? 7.6 : 7.2);
                    $pdf->SetXY($x + ($col * $cw), $yMarc + 1.0 + ($fila * 6.5));
                    $pdf->Cell($cw, 5.0, texto((string)($item['nomb'] ?? '')), 0, 0, 'C');
                }
            }
        }
    }

    /* PIE INFORMATIVO — MISMA POSICIÓN PARA TODOS */
    $txt = trim((string)($doc['pie'] ?? ''));
    if ($txt !== '') {
        $pdf->SetFont('Courier', '', 7.5);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetXY(10, 286);
        $pdf->Cell(190, 4, texto($txt), 0, 1, 'C');
    }

    $pdf->SetTextColor(0, 0, 0);
}
