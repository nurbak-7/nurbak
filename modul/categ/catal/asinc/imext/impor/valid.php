<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/imext/impor/valid.php
   Módulo: Catálogo / ASINC / IMEXT / IMPORT
   Función: Validar una fila integral antes de persistirla.

   FORMATO:
   id, marca, motor, model, descr, codig, origi, medid,
   super, equiv, anio, qty, pcomp, pvent, fecha.

   MARCA:
   Pertenece al intercambio Excel.
   La hoja debe tener el mismo nombre de la marca.
============================================================ */
declare(strict_types=1);

final class ImpoValid
{
    private const CAMPOS = [
        'id', 'marca', 'motor', 'model', 'descr', 'codig',
        'origi', 'medid', 'super', 'equiv', 'anio', 'qty',
        'pcomp', 'pvent', 'fecha'
    ];

    public function fila(
        array $fila,
        string $hoja = ''
    ): array {
        foreach (self::CAMPOS as $campo) {
            if (!array_key_exists($campo, $fila)) {
                throw new InvalidArgumentException(
                    "Falta la columna {$campo}."
                );
            }
        }

        $marca = strtoupper(
            trim((string) $fila['marca'])
        );
        $hoja = strtoupper(trim($hoja));
        $motor = trim((string) $fila['motor']);
        $model = trim((string) $fila['model']);
        $descr = trim((string) $fila['descr']);
        $esVar = strtoupper($descr) === 'VARIOS';

        if ($hoja === 'VR' || $esVar) {
            if ($hoja !== 'VR' || !$esVar) {
                throw new InvalidArgumentException(
                    'El producto VARIOS debe intercambiarse en la hoja independiente VR.'
                );
            }

            if ($marca !== '' || $motor !== '' || $model !== ''
                || trim((string) $fila['anio']) !== '') {
                throw new InvalidArgumentException(
                    'La fila VARIOS de la hoja VR no debe contener MARCA, MOTOR, MODEL ni ANIO.'
                );
            }

            $qty = $this->numero($fila['qty'], 'QTY');
            $pcomp = $this->numero($fila['pcomp'], 'PCOMP');
            $pvent = $this->numero($fila['pvent'], 'PVENT');

            if ($qty < 0 || $pcomp < 0 || $pvent < 0) {
                throw new InvalidArgumentException(
                    'QTY, PCOMP y PVENT no pueden ser negativos.'
                );
            }
            if ($qty != 0.0) {
                throw new InvalidArgumentException(
                    'VARIOS es un producto comodín sin stock propio; QTY debe ser 0.'
                );
            }

            return [
                'id' => $this->id($fila['id']),
                'marca' => '',
                'motor' => '',
                'model' => '',
                'descr' => 'VARIOS',
                'codig' => $this->nulo($fila['codig']),
                'origi' => $this->nulo($fila['origi']),
                'medid' => $this->nulo($fila['medid']),
                'super' => $this->nulo($fila['super']),
                'equiv' => $this->nulo($fila['equiv']),
                'anio' => null,
                'qty' => 0.0,
                'pcomp' => $pcomp,
                'pvent' => $pvent,
                'fecha' => $this->fecha($fila['fecha']),
                'vario' => true,
            ];
        }

        if ($marca === '') {
            throw new InvalidArgumentException(
                'MARCA es obligatoria en IMEXT.'
            );
        }

        if (preg_match('/^.{2}$/uD', $marca) !== 1) {
            throw new InvalidArgumentException(
                'MARCA debe tener exactamente dos caracteres.'
            );
        }

        if ($hoja !== '' && $marca !== $hoja) {
            throw new InvalidArgumentException(
                "MARCA {$marca} no coincide con la hoja {$hoja}."
            );
        }

        if ($motor === '') {
            throw new InvalidArgumentException('MOTOR es obligatorio.');
        }

        if ($model === '') {
            throw new InvalidArgumentException('MODEL es obligatorio.');
        }

        if ($descr === '') {
            throw new InvalidArgumentException('DESCR es obligatorio.');
        }

        $anioTxt = trim((string) $fila['anio']);
        $anio = $this->anio($anioTxt);
        $qty = $this->numero($fila['qty'], 'QTY');
        $pcomp = $this->numero($fila['pcomp'], 'PCOMP');
        $pvent = $this->numero($fila['pvent'], 'PVENT');
        $super = $this->nulo($fila['super']);
        $fecha = $this->fecha($fila['fecha']);

        if ($qty < 0) {
            throw new InvalidArgumentException('QTY no puede ser negativo.');
        }

        if ($pcomp < 0) {
            throw new InvalidArgumentException('PCOMP no puede ser negativo.');
        }

        if ($pvent < 0) {
            throw new InvalidArgumentException('PVENT no puede ser negativo.');
        }

        return [
            'id' => $this->id($fila['id']),
            'marca' => $marca,
            'motor' => $motor,
            'model' => $model,
            'descr' => $descr,
            'codig' => $this->nulo($fila['codig']),
            'origi' => $this->nulo($fila['origi']),
            'medid' => $this->nulo($fila['medid']),
            'super' => $super,
            'equiv' => $this->nulo($fila['equiv']),
            'anio' => $anio,
            '_anio_txt' => $anioTxt,
            'qty' => $qty,
            'pcomp' => $pcomp,
            'pvent' => $pvent,
            'fecha' => $fecha,
        ];
    }

    private function id(mixed $valor): ?int
    {
        $valor = trim((string) $valor);
        return $valor === ''
            ? null
            : $this->entero($valor, 'ID');
    }

    private function anio(mixed $valor): int
    {
        $valor = trim((string) $valor);

        if (preg_match('/^\d{2}-\d{4}-\d{2}$/', $valor)) {
            return (int) substr($valor, 0, 2) + 2000;
        }

        return $this->entero($valor, 'ANIO');
    }

    private function entero(mixed $valor, string $campo): int
    {
        if (!is_numeric($valor) || (int) $valor < 0) {
            throw new InvalidArgumentException(
                "{$campo} no es válido."
            );
        }

        return (int) $valor;
    }

    private function numero(mixed $valor, string $campo): float
    {
        $valor = str_replace(',', '.', trim((string) $valor));

        if ($valor === '' || !is_numeric($valor)) {
            throw new InvalidArgumentException(
                "{$campo} no es válido."
            );
        }

        return (float) $valor;
    }

    private function fecha(mixed $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        foreach (
            ['Y-m-d H:i:s', 'Y-m-d', 'd/m/Y H:i:s', 'd/m/Y']
            as $formato
        ) {
            $f = DateTime::createFromFormat($formato, $valor);

            if (
                $f instanceof DateTime
                && $f->format($formato) === $valor
            ) {
                return $f->format('Y-m-d H:i:s');
            }
        }

        throw new InvalidArgumentException(
            'FECHA no es válida.'
        );
    }

    private function nulo(mixed $valor): ?string
    {
        $valor = trim((string) $valor);
        return $valor === '' ? null : $valor;
    }
}
