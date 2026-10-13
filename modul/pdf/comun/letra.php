<?php
/* ============================================================
   Ruta: modul/pdf/comun/letra.php
   Módulo: PDF / Común

   NIVEL 5.1 — SON / TOTAL EN LETRAS
   ------------------------------------------------------------
   Este archivo contiene:
   1. La conversión numérica a letras.
   2. La inserción visual del bloque SON.

   La categoría solamente entrega el TOTAL.
============================================================ */
declare(strict_types=1);

function letra(float|int|string $num): string
{
    /* La lógica de conversión se conserva del PDF de referencia. */
    $num = str_replace(',', '', (string)$num);
    $val = round((float)$num, 2);
    $ent = (int)floor($val);
    $dec = (int)round(($val - $ent) * 100);

    if ($dec === 100) {
        $ent++;
        $dec = 0;
    }

    $uni = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    $decena = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $espe = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
    $cent = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

    $centenas = static function (int $n) use ($uni, $decena, $espe, $cent): string {
        if ($n === 0) return '';
        if ($n === 100) return 'CIEN';

        $txt = '';
        if ($n >= 100) {
            $txt .= $cent[intdiv($n, 100)] . ' ';
            $n %= 100;
        }
        if ($n >= 20) {
            $txt .= $decena[intdiv($n, 10)];
            if (($n % 10) !== 0) $txt .= ' Y ' . $uni[$n % 10];
        } elseif ($n >= 10) {
            $txt .= $espe[$n - 10];
        } elseif ($n > 0) {
            $txt .= $uni[$n];
        }
        return trim($txt);
    };

    if ($ent === 0) {
        $txt = 'CERO';
    } else {
        $txt = '';
        if ($ent >= 1000000000) {
            $mm = intdiv($ent, 1000000000);
            $txt .= $centenas($mm) . ' MIL MILLONES';
            $ent %= 1000000000;
            if ($ent > 0) $txt .= ' ';
        }
        if ($ent >= 1000000) {
            $mil = intdiv($ent, 1000000);
            $txt .= $mil === 1 ? 'UN MILLÓN' : $centenas($mil) . ' MILLONES';
            $ent %= 1000000;
            if ($ent > 0) $txt .= ' ';
        }
        if ($ent >= 1000) {
            $mil = intdiv($ent, 1000);
            $txt .= $mil === 1 ? 'MIL' : $centenas($mil) . ' MIL';
            $ent %= 1000;
            if ($ent > 0) $txt .= ' ';
        }
        if ($ent > 0) $txt .= $centenas($ent);
    }

    return trim($txt) . ' CON ' . str_pad((string)$dec, 2, '0', STR_PAD_LEFT) . '/100 SOLES';
}

function letr(FPDF $pdf, array $doc, float $y, float $ancho): float
{
    /* NIVEL 5.1.1 — INSERCIÓN VISUAL DE SON */
    $txt = 'SON: ' . letra((string)($doc['total'] ?? 0));
    $h = 7;

    $pdf->SetFont('Courier', 'I', 7.5);
    $pdf->SetDrawColor(120, 120, 120);

    $lin = static function (FPDF $pdf, float $a, float $b, float $yy): void {
        for ($x = $a; $x < $b; $x += 4) {
            $pdf->Line($x, $yy, min($x + 2, $b), $yy);
        }
    };

    $lin($pdf, 10, 10 + $ancho, $y);
    $lin($pdf, 10, 10 + $ancho, $y + $h);
    for ($p = $y; $p < $y + $h; $p += 4) {
        $pdf->Line(10, $p, 10, min($p + 2, $y + $h));
        $pdf->Line(10 + $ancho, $p, 10 + $ancho, min($p + 2, $y + $h));
    }

    $pdf->SetXY(12, $y + 1);
    $pdf->Cell($ancho - 4, 5, texto($txt), 0, 1, 'L');

    return $y + $h;
}
