<?php
/* ============================================================
   Ruta: modul/categ/venta/cotiz/cotiz-servi.php
   Módulo: Ventas / Cotización / Servicio

   RESPONSABILIDAD:
   Aplicar las reglas de negocio de Cotización, normalizar
   detalles y calcular los importes antes de llamar al repositorio.

   REGLAS:
   - Cotización no reserva stock.
   - Cotización no modifica stock.
   - Los precios unitarios conservan hasta 10 decimales.
   - Los totales por línea y de cabecera se guardan a 2 decimales.
   - Varios conserva una fila independiente por cada artículo añadido.
============================================================ */
declare(strict_types=1);

final class CotizServ
{
    private const MAX_CANTI = '99999999999';
    private const MAX_CENTS = '99999999999999';

    public function __construct(private CotizRepo $repo) {}

    /** Convierte un precio recibido a DECIMAL canónico con 10 decimales. */
    private function normalizarPrecio(mixed $valor): string
    {
        if (is_float($valor)) {
            if (!is_finite($valor) || $valor < 0) {
                throw new InvalidArgumentException('Precio unitario no válido.');
            }
            $texto = sprintf('%.10F', $valor);
        } elseif (is_int($valor) || is_string($valor)) {
            $texto = trim((string)$valor);
        } else {
            throw new InvalidArgumentException('Precio unitario no válido.');
        }

        if (!preg_match('/^\d{1,12}(?:\.\d{1,10})?$/D', $texto)) {
            throw new InvalidArgumentException('El precio unitario debe ser positivo o cero y admitir hasta 10 decimales.');
        }

        [$entero, $fraccion] = array_pad(explode('.', $texto, 2), 2, '');
        $entero = ltrim($entero, '0');
        $entero = $entero === '' ? '0' : $entero;
        if (strlen($entero) > 12) {
            throw new InvalidArgumentException('El precio unitario supera el máximo permitido.');
        }

        return $entero . '.' . str_pad($fraccion, 10, '0');
    }

    /** Valida que QTY llegue como entero positivo, sin truncar decimales. */
    private function normalizarCantidad(mixed $valor): int
    {
        if (!is_int($valor) && !is_string($valor)) {
            throw new InvalidArgumentException('La cantidad debe ser un entero mayor que cero.');
        }
        $texto = trim((string)$valor);
        if (!preg_match('/^\d+$/D', $texto)) {
            throw new InvalidArgumentException('La cantidad debe ser un entero mayor que cero.');
        }
        $texto = ltrim($texto, '0');
        if ($texto === '' || self::compararEnteros($texto, self::MAX_CANTI) > 0) {
            throw new InvalidArgumentException('La cantidad debe ser un entero mayor que cero y no superar el máximo permitido.');
        }
        return (int)$texto;
    }

    /** Compara enteros decimales positivos representados como cadenas. */
    private static function compararEnteros(string $a, string $b): int
    {
        $a = ltrim($a, '0') ?: '0';
        $b = ltrim($b, '0') ?: '0';
        if (strlen($a) !== strlen($b)) return strlen($a) <=> strlen($b);
        return strcmp($a, $b) <=> 0;
    }

    /** Multiplica una cadena de dígitos por un entero sin perder precisión decimal. */
    private function multiplicarDigitos(string $digitos, int $multiplicador): string
    {
        $digitos = ltrim($digitos, '0') ?: '0';
        if ($digitos === '0' || $multiplicador === 0) return '0';

        $salida = '';
        $acarreo = 0;
        for ($i = strlen($digitos) - 1; $i >= 0; $i--) {
            $producto = ((int)$digitos[$i] * $multiplicador) + $acarreo;
            $salida = (string)($producto % 10) . $salida;
            $acarreo = intdiv($producto, 10);
        }
        while ($acarreo > 0) {
            $salida = (string)($acarreo % 10) . $salida;
            $acarreo = intdiv($acarreo, 10);
        }
        return ltrim($salida, '0') ?: '0';
    }

    /** Suma uno a un entero decimal arbitrario representado como cadena. */
    private function sumarUno(string $valor): string
    {
        $digitos = str_split($valor);
        for ($i = count($digitos) - 1; $i >= 0; $i--) {
            if ($digitos[$i] !== '9') {
                $digitos[$i] = (string)((int)$digitos[$i] + 1);
                return implode('', $digitos);
            }
            $digitos[$i] = '0';
        }
        return '1' . implode('', $digitos);
    }

    /**
     * Calcula QTY × P.UNIT y redondea el importe de línea a centavos.
     * P.UNIT se interpreta como un decimal exacto de 10 posiciones.
     */
    private function totalCentavos(string $preun, int $canti): int
    {
        [$entero, $fraccion] = explode('.', $preun, 2);
        $unidades = ltrim($entero . $fraccion, '0') ?: '0';
        $producto = $this->multiplicarDigitos($unidades, $canti);

        // El producto está escalado a 10 decimales; 8 posiciones son subcentavos.
        $producto = str_pad($producto, 9, '0', STR_PAD_LEFT);
        $centavos = ltrim(substr($producto, 0, -8), '0') ?: '0';
        $resto = substr($producto, -8);
        if (strcmp($resto, '50000000') >= 0) {
            $centavos = $this->sumarUno($centavos);
        }

        if (self::compararEnteros($centavos, self::MAX_CENTS) > 0) {
            throw new InvalidArgumentException('El total de una línea supera el máximo monetario permitido.');
        }
        return (int)$centavos;
    }

    private function formatoImporte(int $centavos): string
    {
        return intdiv($centavos, 100) . '.' . str_pad((string)($centavos % 100), 2, '0', STR_PAD_LEFT);
    }

    private function normalizar(array $detal): array
    {
        $salida = [];
        $indices = [];

        foreach ($detal as $row) {
            if (!is_array($row)) {
                throw new InvalidArgumentException('Hay una línea de detalle no válida.');
            }

            $produ = (int)($row['produ'] ?? 0);
            $canti = $this->normalizarCantidad($row['canti'] ?? null);
            $preun = $this->normalizarPrecio($row['preun'] ?? null);
            $codig = trim((string)($row['codig'] ?? ''));
            $descr = trim((string)($row['descr'] ?? ''));

            if ($produ <= 0) {
                throw new InvalidArgumentException('Producto no válido.');
            }

            // El catálogo define si es VARIOS. Código y descripción de artículos
            // normales provienen del catálogo; solo VARIOS admite datos propios.
            $base = $this->repo->datosProducto($produ);
            $vario = (bool)$base['vario'];
            if (!$vario) {
                $codig = $base['codig'];
                $descr = $base['descr'];
            }
            if (mb_strlen($codig) > 80 || mb_strlen($descr) > 255) {
                throw new InvalidArgumentException('El código o la descripción exceden la longitud permitida.');
            }
            $clave = json_encode([$produ, $codig, $descr, $preun], JSON_UNESCAPED_UNICODE);

            // Varios siempre crea una fila independiente. Los otros artículos
            // conservan la agrupación actual si coinciden sus datos y precio.
            if (!$vario && isset($indices[$clave])) {
                $indice = $indices[$clave];
                $suma = $salida[$indice]['canti'] + $canti;
                if ($suma > (int)self::MAX_CANTI) {
                    throw new InvalidArgumentException('La cantidad acumulada supera el máximo permitido.');
                }
                $salida[$indice]['canti'] = $suma;
                continue;
            }

            $salida[] = [
                'produ' => $produ,
                'codig' => $codig,
                'descr' => $descr,
                'vario' => $vario,
                'canti' => $canti,
                'preun' => $preun,
            ];
            if (!$vario) {
                $indices[$clave] = array_key_last($salida);
            }
        }

        if ($salida === []) {
            throw new InvalidArgumentException('La cotización debe contener productos.');
        }

        return $salida;
    }

    private function calcular(array $detal): array
    {
        $detal = $this->normalizar($detal);
        $importeCents = 0;

        foreach ($detal as &$row) {
            $cents = $this->totalCentavos($row['preun'], $row['canti']);
            $row['total'] = $this->formatoImporte($cents);
            $importeCents += $cents;
            if ($importeCents > (int)self::MAX_CENTS) {
                throw new InvalidArgumentException('El total de la cotización supera el máximo monetario permitido.');
            }
        }
        unset($row);

        // El importe de cotización se trata como precio con IGV incluido.
        // Base = redondeo(total / 1.18), trabajando en centavos enteros.
        $subtoCents = intdiv(($importeCents * 100) + 59, 118);
        $igvCents = $importeCents - $subtoCents;

        return [
            'detal' => $detal,
            'subto' => $this->formatoImporte($subtoCents),
            'igv' => $this->formatoImporte($igvCents),
            'total' => $this->formatoImporte($importeCents),
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
