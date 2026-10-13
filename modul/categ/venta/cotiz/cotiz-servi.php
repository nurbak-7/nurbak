<?php
/* ============================================================
   Ruta: modul/categ/venta/cotiz/cotiz-servi.php
   Módulo: Ventas / Cotización / Servicio

   RESPONSABILIDAD:
   Aplicar las reglas de negocio de Cotización, normalizar
   detalles y calcular los importes oficiales antes de llamar
   al repositorio.

   REGLAS:
   - Cotización no reserva stock.
   - Cotización no modifica stock.
   - Cotización no genera salss.
============================================================ */
declare(strict_types=1);

final class CotizServ
{
    public function __construct(private CotizRepo $repo) {}

    private function normalizar(array $detal): array
    {
        $agr = [];

        foreach ($detal as $row) {
            $produ = (int)($row['produ'] ?? 0);
            $canti = (float)($row['canti'] ?? 0);
            $preun = (float)($row['preun'] ?? 0);

            if ($produ <= 0 || $canti <= 0 || $preun < 0) {
                throw new InvalidArgumentException('Producto, cantidad o precio no válidos.');
            }

            if (!isset($agr[$produ])) {
                $agr[$produ] = [
                    'produ'=>$produ,
                    'canti'=>0.0,
                    'preun'=>$preun
                ];
            }

            $agr[$produ]['canti'] += $canti;
            $agr[$produ]['preun'] = $preun;
        }

        if ($agr === []) {
            throw new InvalidArgumentException('La cotización debe contener productos.');
        }

        return array_values($agr);
    }

    private function calcular(array $detal): array
    {
        $detal = $this->normalizar($detal);
        $importe = 0.0;

        foreach ($detal as &$row) {
            $row['total'] = round($row['canti'] * $row['preun'], 2);
            $importe += $row['total'];
        }
        unset($row);

        $total = round($importe, 2);
        $subto = round($total / 1.18, 2);
        $igv = round($total - $subto, 2);

        return [
            'detal'=>$detal,
            'subto'=>$subto,
            'igv'=>$igv,
            'total'=>$total
        ];
    }

    public function guardar(
        int $id,
        int $clien,
        int $vende,
        string $placa,
        string $fecha,
        string $vigia,
        string $obser,
        array $detal
    ): array {
        $placa = strtoupper(trim($placa));

        if ($placa !== '' && !preg_match('/^[A-Z0-9]{3}-[A-Z0-9]{3}$/', $placa)) {
            throw new InvalidArgumentException('La placa debe tener el formato SSS-SSS.');
        }

        $fech = DateTime::createFromFormat('!Y-m-d', $fecha);
        $vigi = DateTime::createFromFormat('!Y-m-d', $vigia);

        if (!$fech || $fech->format('Y-m-d') !== $fecha) {
            throw new InvalidArgumentException('Fecha de cotización no válida.');
        }

        $fin = clone $fech;
        $fin->modify('+3 days');
        if (!$vigi || $vigi->format('Y-m-d') !== $vigia || $vigi->format('Y-m-d') !== $fin->format('Y-m-d')) {
            throw new InvalidArgumentException('La vigencia de la cotización debe ser de 3 días.');
        }

        $calculo = $this->calcular($detal);

        return $this->repo->guardar(
            $id,
            $clien,
            $vende,
            $placa,
            $fecha,
            $vigia,
            $obser,
            $calculo['detal'],
            $calculo['subto'],
            $calculo['igv'],
            $calculo['total']
        );
    }

    public function cancelar(int $id): void
    {
        $this->repo->cancelar($id);
    }
}
