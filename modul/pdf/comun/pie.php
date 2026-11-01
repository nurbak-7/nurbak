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
    if ($obs !== '') {
        $pdf->SetTextColor(0, 0, 0);

        if ($fisc) {
            /* Factura y Boleta: observación centrada debajo del QR.
               Usa la misma tipografía y tamaño que la línea SON.
               Se mantiene dentro del ancho disponible bajo el QR. */
            $pdf->SetFont('Courier', 'I', 7.5);
            $lineaObs = 'OBSERVACIÓN: ' . $obs;
            $anchoObs = 48.0;
            $lineaObs = corta($pdf, $lineaObs, $anchoObs);

            $pdf->SetXY(10.0, $y + 2.0);
            $pdf->Cell($anchoObs, 3.0, $lineaObs, 0, 0, 'C');
        } else {
            /* Cotización conserva su ubicación debajo de los totales. */
            $pdf->SetFont('Courier', 'B', 7.2);
            $pdf->SetXY(10, $y + 0.5);
            $pdf->Cell(27, 3.8, texto('OBSERVACIÓN:'), 0, 0, 'L');

            $pdf->SetFont('Courier', '', 7.0);
            $pdf->SetXY(38, $y + 0.5);
            $pdf->MultiCell(162, 3.4, texto($obs), 0, 'L');
        }
    }

    /* CUENTAS BANCARIAS — SOLO COTIZACIÓN */
    if ($cotiz) {
        $emp = $conf['empresa'] ?? [];
        $cuen = $emp['cuen'] ?? [];
        $tit = $cuen['titu'] ?? ['FORMAS DE PAGO', 'CUENTA CORRIENTE', 'CUENTA CCI'];
        $fil = $cuen['fila'] ?? [];
        $yCuen = max($y + 7.0, $pdf->GetY() + 1.5);

        if ($fil !== []) {
            /* Bloque compacto alineado a la izquierda.
               Las columnas toman el ancho de su contenido más un margen corto.
               Todos los textos permanecen centrados dentro de su columna. */
            $x = 10.0;
            $valsFil = [];
            foreach ($fil as $fila) {
                $valsFil[] = [
                    (string)($fila['banc'] ?? ''),
                    (string)($fila['cta'] ?? ''),
                    (string)($fila['cci'] ?? ''),
                ];
            }

            $pdf->SetFont('Courier', 'B', 7.5);
            $cols = [];
            for ($i = 0; $i < 3; $i++) {
                $max = $pdf->GetStringWidth(texto((string)($tit[$i] ?? '')));
                foreach ($valsFil as $vals) {
                    $pdf->SetFont('Courier', $i === 0 ? 'B' : '', 7.5);
                    $max = max($max, $pdf->GetStringWidth(texto($vals[$i])));
                }
                $cols[$i] = $max + 3.0;
            }

            $hh = 4.2;
            $hr = 4.0;
            $ancho = array_sum($cols);

            /* Encabezados blancos con texto azul oscuro. */
            $pdf->SetFont('Courier', 'B', 7.5);
            $pdf->SetTextColor(0, 48, 105);
            $px = $x;
            foreach ($cols as $i => $cw) {
                $pdf->SetXY($px, $yCuen);
                $pdf->Cell($cw, $hh, texto((string)($tit[$i] ?? '')), 0, 0, 'C');
                $px += $cw;
            }

            /* Separadores horizontales discretos; sin cajas ni marco exterior. */
            $pdf->SetDrawColor(190, 198, 208);
            $pdf->SetLineWidth(0.15);
            $yy = $yCuen + $hh;
            $pdf->Line($x, $yy, $x + $ancho, $yy);

            foreach ($valsFil as $vals) {
                $px = $x;
                foreach ($cols as $i => $cw) {
                    $pdf->SetFont('Courier', $i === 0 ? 'B' : '', 7.5);
                    $pdf->SetTextColor(20, 35, 55);
                    $pdf->SetXY($px, $yy);
                    $pdf->Cell($cw, $hr, texto($vals[$i]), 0, 0, 'C');
                    $px += $cw;
                }
                $yy += $hr;
                $pdf->Line($x, $yy, $x + $ancho, $yy);
            }

            $yMarc = $yy + 3.0;
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
