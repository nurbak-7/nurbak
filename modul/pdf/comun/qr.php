<?php
/* ============================================================
   Ruta: modul/pdf/comun/qr.php
   Módulo: PDF / Común

   NIVEL 5.2 — QR
   ------------------------------------------------------------
   Todo el mecanismo QR es común.

   La categoría NO genera QR.
   Solo entrega datos normalizados del documento.

   Para documentos fiscales el contenido se arma aquí con:
   RUC | SERIE-NÚMERO | TOTAL
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/libre/phpqrcode/qrlib.php';

function qrcon(array $doc, array $conf): string
{
    $ruc = (string)($conf['empresa']['ruc'] ?? '');
    $ser = (string)($doc['serie'] ?? '');
    $num = (int)($doc['nume'] ?? 0);
    $tot = fnum($doc['total'] ?? 0);

    return $ruc . '|' . $ser . '-' . str_pad((string)$num, 4, '0', STR_PAD_LEFT) . '|' . $tot;
}

function qrgen(string $cont, string $ruta, int $ec = QR_ECLEVEL_L, int $tam = 3, int $marg = 2): void
{
    if ($cont === '') {
        throw new InvalidArgumentException('Contenido QR vacío.');
    }

    $dir = dirname($ruta);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('No se pudo crear el directorio del QR.');
    }

    QRcode::png($cont, $ruta, $ec, $tam, $marg);
}

function qrtemp(string $cont, int $ec = QR_ECLEVEL_L, int $tam = 3, int $marg = 2): string
{
    $tmp = tempnam(sys_get_temp_dir(), 'qr_');
    if ($tmp === false) {
        throw new RuntimeException('No se pudo crear el temporal del QR.');
    }

    $ruta = $tmp . '.png';
    @unlink($tmp);

    try {
        qrgen($cont, $ruta, $ec, $tam, $marg);
        return $ruta;
    } catch (Throwable $e) {
        @unlink($ruta);
        throw $e;
    }
}

function qrborr(string $ruta): void
{
    if ($ruta !== '' && is_file($ruta)) {
        @unlink($ruta);
    }
}

function qrco(FPDF $pdf, array $doc, array $conf, float $y, float $ancho): float
{
    /* NIVEL 5.2.1 — GENERAR */
    $qr = qrtemp(qrcon($doc, $conf));

    try {
        /* NIVEL 5.2.2 — INSERTAR EN EL BLOQUE INFERIOR */
        $pdf->Image($qr, 14, $y + 3, 24, 24);
    } finally {
        /* NIVEL 5.2.3 — LIBERAR TEMPORAL */
        qrborr($qr);
    }

    return $y + 30;
}
