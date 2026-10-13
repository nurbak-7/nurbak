<?php
/* ============================================================
   Ruta: modul/pdf/comun/pie.php
   Módulo: PDF / Común

   NIVEL 6 — PIE
   ------------------------------------------------------------
   Último bloque visual del documento.
   Las categorías solo entregan el texto del pie si corresponde.
============================================================ */
declare(strict_types=1);

function pie(FPDF $pdf, array $doc): void
{
    $txt = trim((string)($doc['pie'] ?? ''));
    if ($txt === '') {
        return;
    }

    $pdf->SetFont('Courier', '', 7.5);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->SetXY(10, 286);
    $pdf->Cell(190, 4, texto($txt), 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
}
