<?php
/* ============================================================
   Ruta: modul/pdf/venta/cotiz/emite.php
   Módulo: PDF / Ventas / Cotización

   RESPONSABILIDAD:
   Presentar exclusivamente la cotización en PDF.

   CONSERVA EL DISEÑO ACTUAL:
   - Cabecera empresarial y cuadro de documento.
   - Datos del cliente y vehículo.
   - Tabla de productos.
   - Resumen de subtotal, IGV y total.

   INCORPORA:
   - Logotipo.
   - Observación de la cotización.
   - Cuentas bancarias BBVA e INTERBANK.
   - Marcas en 8 columnas por 2 filas.

   No consulta bases de datos ni modifica otros documentos.
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/libre/fpdf/fpdf.php';

/* ============================================================
   UTILIDADES PROPIAS DE COTIZACIÓN
============================================================ */
function cotizTexto(string $valor): string
{
    $valor = trim($valor);
    if ($valor === '') {
        return '';
    }

    $conv = @iconv('UTF-8', 'windows-1252//TRANSLIT', $valor);
    return $conv !== false ? $conv : $valor;
}

function cotizCorta(FPDF $pdf, string $valor, float $ancho, string $suf = '...'): string
{
    $valor = cotizTexto($valor);
    if ($valor === '' || $pdf->GetStringWidth($valor) <= $ancho) {
        return $valor;
    }

    $n = strlen($valor);
    while ($n > 1 && $pdf->GetStringWidth(substr($valor, 0, $n) . $suf) > $ancho) {
        $n--;
    }

    return rtrim(substr($valor, 0, $n)) . $suf;
}

function cotizImporte(mixed $valor): string
{
    return number_format((float)$valor, 2, '.', '');
}

function cotizCantidad(mixed $valor): string
{
    $num = (float)$valor;
    $tres = number_format($num, 3, '.', '');
    $dec = substr($tres, -1) !== '0' ? 3 : 2;
    return number_format($num, $dec, '.', '');
}

function cotizLineas(FPDF $pdf, string $texto, float $ancho): array
{
    $texto = cotizTexto($texto);
    if ($texto === '') {
        return [''];
    }

    $palabras = preg_split('/\s+/', $texto, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $lineas = [];
    $linea = '';

    foreach ($palabras as $palabra) {
        if ($pdf->GetStringWidth($palabra) > $ancho) {
            if ($linea !== '') {
                $lineas[] = $linea;
                $linea = '';
            }
            $partes = str_split($palabra);
            foreach ($partes as $car) {
                if ($linea !== '' && $pdf->GetStringWidth($linea . $car) > $ancho) {
                    $lineas[] = $linea;
                    $linea = '';
                }
                $linea .= $car;
            }
            continue;
        }

        $prueba = $linea === '' ? $palabra : $linea . ' ' . $palabra;
        if ($linea === '' || $pdf->GetStringWidth($prueba) <= $ancho) {
            $linea = $prueba;
            continue;
        }

        $lineas[] = $linea;
        $linea = $palabra;
    }

    if ($linea !== '') {
        $lineas[] = $linea;
    }

    return $lineas !== [] ? $lineas : [''];
}

/* ============================================================
   CABECERA — MISMA DISTRIBUCIÓN DEL PDF ACTUAL
============================================================ */
function cotizCabecera(FPDF $pdf, array $doc, array $conf): void
{
    $emp = $conf['empresa'] ?? [];

    $x = 10.0;
    $y = 10.0;
    $logo = trim((string)($emp['logo'] ?? ''));

    if ($logo !== '' && is_file($logo)) {
        $pdf->Image($logo, 12, 12, 19, 19);
    }

    $xEmp = 33.0;
    $wEmp = 116.0;

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY($xEmp, $y + 1);
    $pdf->SetFont('Courier', 'B', 18);
    $pdf->Cell(
        $wEmp,
        7,
        cotizCorta($pdf, (string)($emp['nombre'] ?? ''), $wEmp - 2),
        0,
        1,
        'C'
    );

    $pdf->SetX($xEmp);
    $pdf->SetFont('Courier', 'B', 7.8);
    $pdf->Cell(
        $wEmp,
        5,
        cotizCorta($pdf, (string)($emp['activi'] ?? ''), $wEmp - 2),
        0,
        1,
        'C'
    );

    $pdf->SetX($xEmp);
    $pdf->SetFont('Courier', 'B', 7.2);
    $pdf->Cell(
        $wEmp,
        5,
        cotizCorta($pdf, (string)($emp['direc'] ?? ''), $wEmp - 2),
        0,
        1,
        'C'
    );

    /* Cuadro derecho del documento, conservando el estilo actual. */
    $dx = 154.0;
    $dy = 10.0;
    $dw = 46.0;
    $dh = 25.0;

    $pdf->SetDrawColor(80, 80, 80);
    $pdf->SetLineWidth(0.25);
    $pdf->Rect($dx, $dy, $dw, $dh);

    $pdf->SetFont('Courier', 'B', 8);
    $pdf->SetXY($dx + 1, $dy + 1);
    $pdf->Cell($dw - 2, 5, cotizTexto('R.U.C. ' . (string)($emp['ruc'] ?? '')), 0, 1, 'C');

    $pdf->SetFillColor(235, 235, 235);
    $pdf->SetX($dx + 1);
    $pdf->Cell($dw - 2, 5, cotizTexto('COTIZACIÓN'), 0, 1, 'C', true);

    $serie = (string)($doc['serie'] ?? '');
    $nume = str_pad((string)(int)($doc['nume'] ?? 0), 4, '0', STR_PAD_LEFT);
    $numero = $serie !== '' ? $serie . '-' . $nume : $nume;

    $pdf->SetFont('Courier', 'B', 9);
    $pdf->SetX($dx + 1);
    $pdf->Cell($dw - 2, 5, cotizTexto($numero), 0, 1, 'C');

    $pdf->SetFont('Courier', '', 7.5);
    $pdf->SetX($dx + 1);
    $pdf->Cell($dw - 2, 4, cotizTexto((string)($doc['estado'] ?? '')), 0, 0, 'C');
}

/* ============================================================
   DATOS DEL CLIENTE Y VEHÍCULO
============================================================ */
function cotizCliente(FPDF $pdf, array $doc): void
{
    $x = 10.0;
    $y = 40.0;
    $w = 190.0;
    $h = 26.0;

    $pdf->SetDrawColor(120, 120, 120);
    $pdf->SetLineWidth(0.25);
    $pdf->Rect($x, $y, $w, $h);

    $izq = [
        ['FECHA', (string)($doc['fecha'] ?? '')],
        ['RUC / DNI', (string)($doc['docum'] ?? '')],
        ['CLIENTE', (string)($doc['razon'] ?? '')],
        ['DIRECCIÓN', (string)($doc['direc'] ?? '')],
    ];

    $der = [
        ['VIGENCIA', (string)($doc['vigia'] ?? '')],
        ['VENDEDOR', (string)($doc['vende_nom'] ?? '')],
        ['PLACA / MOTOR', (string)($doc['placa'] ?? '')],
    ];

    $altoFila = 5.2;
    $yi = $y + 1.4;
    $pdf->SetFont('Courier', 'B', 8);

    foreach ($izq as [$lab, $val]) {
        $pdf->SetXY($x + 2, $yi);
        $pdf->Cell(22, $altoFila, cotizTexto($lab), 0, 0, 'L');
        $pdf->Cell(3, $altoFila, ':', 0, 0, 'C');
        $pdf->SetFont('Courier', '', 8);
        $pdf->Cell(67, $altoFila, cotizCorta($pdf, $val, 66), 0, 0, 'L');
        $pdf->SetFont('Courier', 'B', 8);
        $yi += $altoFila;
    }

    $yd = $y + 1.4;
    foreach ($der as [$lab, $val]) {
        $pdf->SetXY($x + 103, $yd);
        $pdf->Cell(25, $altoFila, cotizTexto($lab), 0, 0, 'R');
        $pdf->Cell(3, $altoFila, ':', 0, 0, 'C');
        $pdf->SetFont('Courier', '', 8);
        $pdf->Cell(57, $altoFila, cotizCorta($pdf, $val, 56), 0, 0, 'L');
        $pdf->SetFont('Courier', 'B', 8);
        $yd += $altoFila;
    }
}

/* ============================================================
   DETALLE — TABLA ACTUAL DE SEIS COLUMNAS
============================================================ */
function cotizTrazoDiscontinuo(FPDF $pdf, float $x1, float $x2, float $y): void
{
    for ($x = $x1; $x < $x2; $x += 4) {
        $pdf->Line($x, $y, min($x + 2, $x2), $y);
    }
}

function cotizTablaCab(FPDF $pdf, float $y): float
{
    $x = 10.0;
    $h = 6.0;
    $cols = [10.0, 28.0, 89.0, 16.0, 22.0, 25.0];
    $tit = ['IT', 'CODIGO', 'DESCRIPCION', 'QTY', 'P.UNIT', 'TOTAL'];
    $ancho = array_sum($cols);

    $pdf->SetDrawColor(80, 80, 80);
    $pdf->SetLineWidth(0.2);
    cotizTrazoDiscontinuo($pdf, $x, $x + $ancho, $y);
    cotizTrazoDiscontinuo($pdf, $x, $x + $ancho, $y + $h);

    $px = $x;
    foreach ($cols as $cw) {
        for ($py = $y; $py < $y + $h; $py += 4) {
            $pdf->Line($px, $py, $px, min($py + 2, $y + $h));
        }
        $px += $cw;
    }
    for ($py = $y; $py < $y + $h; $py += 4) {
        $pdf->Line($px, $py, $px, min($py + 2, $y + $h));
    }

    $pdf->SetFont('Courier', 'B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY($x, $y);
    foreach ($cols as $i => $cw) {
        $pdf->Cell($cw, $h, cotizTexto($tit[$i]), 0, $i === count($cols) - 1 ? 1 : 0, 'C');
    }

    return $y + $h;
}

function cotizFilas(FPDF $pdf, array $detal, int $inicio, int $cantidad, float $y): float
{
    $x = 10.0;
    $h = 6.0;
    $cols = [10.0, 28.0, 89.0, 16.0, 22.0, 25.0];

    $pdf->SetFont('Courier', '', 8);
    $pdf->SetTextColor(0, 0, 0);

    for ($i = $inicio; $i < $inicio + $cantidad; $i++) {
        $row = $detal[$i];

        if (($i % 2) === 0) {
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Rect($x, $y, array_sum($cols), $h, 'F');
        }

        $valores = [
            (string)($i + 1),
            (string)($row['codig'] ?? ''),
            cotizCorta($pdf, (string)($row['descr'] ?? ''), 85),
            cotizCantidad($row['canti'] ?? 0),
            cotizImporte($row['preun'] ?? 0),
            cotizImporte($row['total'] ?? 0),
        ];

        $pdf->SetXY($x, $y);
        foreach ($cols as $j => $cw) {
            $ali = in_array($j, [0, 3], true) ? 'C' : ($j >= 4 ? 'R' : 'L');
            $pdf->Cell($cw, $h, cotizTexto($valores[$j]), 0, $j === count($cols) - 1 ? 1 : 0, $ali);
        }
        $y += $h;
    }

    return $y;
}

/* ============================================================
   TOTALES — MISMO FORMATO DEL PDF ACTUAL
============================================================ */
function cotizTotales(FPDF $pdf, array $doc, float $y): float
{
    $x = 132.0;
    $w = 68.0;
    $h = 6.0;
    $alto = 18.0;

    $pdf->SetDrawColor(120, 120, 120);
    $pdf->SetLineWidth(0.2);

    for ($p = $y; $p < $y + $alto; $p += 4) {
        $pdf->Line($x, $p, min($x + 2, $x + 3), $p);
        $pdf->Line($x + $w - 2, $p, $x + $w, $p);
        $pdf->Line($x, $p, $x, min($p + 2, $y + $alto));
        $pdf->Line($x + $w, $p, $x + $w, min($p + 2, $y + $alto));
    }

    $campos = [
        'SUBTOTAL' => $doc['subto'] ?? 0,
        'IGV 18%' => $doc['igv'] ?? 0,
        'TOTAL' => $doc['total'] ?? 0,
    ];

    $yy = $y;
    $pdf->SetFont('Courier', 'B', 8.5);
    foreach ($campos as $lab => $valor) {
        $pdf->SetXY($x + 1, $yy);
        $pdf->Cell(43, $h, cotizTexto($lab), 0, 0, 'L');
        $pdf->Cell(23, $h, cotizImporte($valor), 0, 1, 'R');
        $yy += $h;
    }

    return $y + $alto;
}

/* ============================================================
   OBSERVACIÓN
============================================================ */
function cotizObservacion(FPDF $pdf, string $observacion, float $y): float
{
    $x = 10.0;
    $wTxt = 160.0;
    $lineas = cotizLineas($pdf, $observacion, $wTxt);
    $altoLinea = 3.8;
    $alto = max(5.0, count($lineas) * $altoLinea);

    $pdf->SetFont('Courier', 'B', 7.8);
    $pdf->SetXY($x, $y);
    $pdf->Cell(25, $altoLinea, cotizTexto('OBSERVACIÓN'), 0, 0, 'L');
    $pdf->Cell(3, $altoLinea, ':', 0, 0, 'C');

    $pdf->SetFont('Courier', '', 7.8);
    foreach ($lineas as $i => $linea) {
        $pdf->SetXY($x + 29, $y + ($i * $altoLinea));
        $pdf->Cell($wTxt, $altoLinea, $linea, 0, 0, 'L');
    }

    return $y + $alto;
}

/* ============================================================
   CUENTAS BANCARIAS — ANTES DEL CUADRO DE MARCAS
============================================================ */
function cotizCuentas(FPDF $pdf, float $y): float
{
    $x = 10.0;
    $cols = [48.0, 71.0, 71.0];
    $tit = ['FORMAS DE PAGO', 'CUENTA CORRIENTE', 'CUENTA CCI'];
    $filas = [
        ['BBVA', '0011-0115-0200723142', '011-115-000200723142-32'],
        ['INTERBANK', '200-300705429-5', '003-200-003007054295-33'],
    ];
    $hh = 5.0;
    $hr = 5.0;

    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.2);

    $px = $x;
    $pdf->SetFont('Courier', 'B', 7.5);
    foreach ($cols as $i => $cw) {
        $pdf->SetFillColor(0, 0, 0);
        $pdf->Rect($px, $y, $cw, $hh, 'DF');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($px + 0.5, $y);
        $pdf->Cell($cw - 1, $hh, cotizTexto($tit[$i]), 0, 0, 'C');
        $px += $cw;
    }

    $pdf->SetTextColor(0, 0, 0);
    $yy = $y + $hh;
    foreach ($filas as $fila) {
        $px = $x;
        foreach ($cols as $i => $cw) {
            $pdf->Rect($px, $yy, $cw, $hr);
            $pdf->SetFont('Courier', $i === 0 ? 'B' : '', 7.5);
            $pdf->SetXY($px + 0.5, $yy);
            $pdf->Cell($cw - 1, $hr, cotizCorta($pdf, (string)$fila[$i], $cw - 2), 0, 0, 'C');
            $px += $cw;
        }
        $yy += $hr;
    }

    return $yy;
}

/* ============================================================
   MARCAS — EXACTAMENTE 8 COLUMNAS POR 2 FILAS
============================================================ */
function cotizMarcas(FPDF $pdf, float $y): float
{
    $marcas = [
        [
            ['HONDA', [0, 0, 0]],
            ['HYUNDAI', [0, 48, 105]],
            ['MAZDA', [0, 166, 214]],
            ['MITSUBISHI', [128, 42, 132]],
            ['NISSAN', [0, 153, 76]],
            ['SUZUKI', [210, 35, 42]],
            ['SUBARU', [0, 105, 190]],
            ['TOYOTA', [0, 0, 0]],
        ],
        [
            ['CHANGAN', [0, 0, 0]],
            ['CHERY', [0, 0, 0]],
            ['DFSK', [0, 0, 0]],
            ['DONGFENG', [0, 0, 0]],
            ['FAW', [0, 0, 0]],
            ['GRET WALL', [0, 0, 0]],
            ['GEELY', [0, 0, 0]],
            ['CHEVROLET', [0, 166, 214]],
        ],
    ];

    $x = 12.0;
    $w = 186.0;
    $cw = $w / 8;
    $alto = 15.0;

    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.65);
    $pdf->Line($x, $y, $x, $y + $alto);
    $pdf->Line($x + $w, $y, $x + $w, $y + $alto);

    foreach ($marcas as $fila => $items) {
        foreach ($items as $col => [$nombre, $color]) {
            $pdf->SetTextColor($color[0], $color[1], $color[2]);
            $pdf->SetFont('Courier', 'B', $fila === 0 ? 7.6 : 7.2);
            $pdf->SetXY($x + ($col * $cw), $y + 1.0 + ($fila * 6.5));
            $pdf->Cell($cw, 5.0, cotizTexto($nombre), 0, 0, 'C');
        }
    }

    $pdf->SetTextColor(0, 0, 0);
    return $y + $alto;
}

/* ============================================================
   PÁGINA Y DETALLE
============================================================ */
function cotizNuevaPagina(FPDF $pdf, array $doc, array $conf): float
{
    $pdf->AddPage('P', 'A4');
    cotizCabecera($pdf, $doc, $conf);
    cotizCliente($pdf, $doc);
    return cotizTablaCab($pdf, 74.0);
}

/* ============================================================
   EMISIÓN
============================================================ */
function emiteCotiz(array $doc, array $detal, array $conf): void
{
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->SetMargins(10, 10, 10);

    $serie = (string)($doc['serie'] ?? '');
    $nume = (int)($doc['nume'] ?? 0);
    $numero = $serie !== ''
        ? $serie . '-' . str_pad((string)$nume, 4, '0', STR_PAD_LEFT)
        : str_pad((string)$nume, 4, '0', STR_PAD_LEFT);

    $pdf->SetTitle(cotizTexto('COTIZACIÓN ' . $numero));
    $pdf->SetAuthor(cotizTexto((string)($conf['empresa']['nombre'] ?? '')));
    $pdf->SetCreator('Nurbak');

    /* Altura calculada para observaciones extensas. */
    $obsTexto = (string)($doc['obser'] ?? '');
    $pdf->SetFont('Courier', '', 7.8);
    $obsLineas = cotizLineas($pdf, $obsTexto, 160.0);
    $obsAlto = max(5.0, count($obsLineas) * 3.8);

    /* El bloque final debe quedar completo antes del pie. */
    $pieY = 286.0;
    $altoCierre = 18.0 + 2.0 + $obsAlto + 2.0 + 15.0 + 6.0 + 15.0;
    $maxTotY = $pieY - 6.0 - $altoCierre;
    $maxFilasFinal = (int)floor(($maxTotY - 5.0 - 80.0) / 6.0);
    $maxFilasFinal = max(1, $maxFilasFinal);

    $totalFilas = count($detal);
    $extra = max(0, $totalFilas - $maxFilasFinal);
    $pagInter = $extra > 0 ? (int)ceil($extra / 32) : 0;

    $pdf->AddPage('P', 'A4');
    cotizCabecera($pdf, $doc, $conf);
    cotizCliente($pdf, $doc);
    $y = cotizTablaCab($pdf, 74.0);

    $indice = 0;
    while ($pagInter > 0) {
        $tomar = (int)ceil($extra / $pagInter);
        $tomar = min(32, $tomar);
        $y = cotizFilas($pdf, $detal, $indice, $tomar, $y);
        $indice += $tomar;
        $extra -= $tomar;
        $pagInter--;

        $y = cotizNuevaPagina($pdf, $doc, $conf);
    }

    $restantes = $totalFilas - $indice;
    if ($restantes > 0) {
        $y = cotizFilas($pdf, $detal, $indice, $restantes, $y);
    }

    /* CIERRE: totales, observación, depósitos y marcas. */
    $yTot = max(200.0, $y + 5.0);
    cotizTotales($pdf, $doc, $yTot);

    $yObs = $yTot + 20.0;
    $yFinObs = cotizObservacion($pdf, $obsTexto, $yObs);

    $yCta = $yFinObs + 2.0;
    $yFinCta = cotizCuentas($pdf, $yCta);

    $yMar = $yFinCta + 6.0;
    cotizMarcas($pdf, $yMar);

    /* Pie informativo que ya mostraba la cotización actual. */
    $pie = trim((string)($doc['pie'] ?? ''));
    if ($pie !== '') {
        $pdf->SetFont('Courier', '', 7.5);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetXY(10, $pieY);
        $pdf->Cell(190, 4, cotizTexto($pie), 0, 0, 'C');
        $pdf->SetTextColor(0, 0, 0);
    }

    $nom = preg_replace('/[^A-Za-z0-9_-]/', '', $numero) ?: 'cotizacion';
    $pdf->Output('I', 'PDF-' . $nom . '.pdf');
}
