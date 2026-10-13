<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/imext/expor/archi.php
   Módulo: Catálogo / ASINC / IMEXT / EXPORT

   Función:
   Crear y completar el XLSX integral.

   FORMATO:
   - Una hoja por marca.
   - Encabezados:
     ID, MARCA, MOTOR, MODEL, DESCR, CODIG, ORIGI, MEDID,
     SUPER, EQUIV, ANIO, QTY, PCOMP, PVENT, FECHA.

   MARCA:
   Solo pertenece al intercambio Excel.
============================================================ */
declare(strict_types=1);

$autoload = dirname(__DIR__, 6) . '/libre/compo/vendor/autoload.php';

if (!is_file($autoload)) {
    throw new RuntimeException(
        'No se encontró libre/compo/vendor/autoload.php.'
    );
}

require_once $autoload;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class ExpoArchi
{
    private const CAMPOS = [
        'ID',
        'MARCA',
        'MOTOR',
        'MODEL',
        'DESCR',
        'CODIG',
        'ORIGI',
        'MEDID',
        'SUPER',
        'EQUIV',
        'ANIO',
        'QTY',
        'PCOMP',
        'PVENT',
        'FECHA',
    ];

    public function crear(string $tipo = 'EXPO_INTEG'): string
    {
        if ($tipo !== 'EXPO_INTEG') {
            throw new InvalidArgumentException(
                'Tipo de exportación no válido.'
            );
        }

        $dir = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'nurbak_asinc';

        if (
            !is_dir($dir)
            && !mkdir($dir, 0775, true)
            && !is_dir($dir)
        ) {
            throw new RuntimeException(
                'No se pudo preparar el almacenamiento.'
            );
        }

        $ruta = $dir
            . DIRECTORY_SEPARATOR
            . 'expo_integral_'
            . bin2hex(random_bytes(8))
            . '.xlsx';

        $book = new Spreadsheet();

        $hoja = $book->getActiveSheet();
        $hoja->setTitle('CATALOGO');

        $book->setActiveSheetIndex(0);
        $writer = IOFactory::createWriter($book, 'Xlsx');
        $writer->save($ruta);

        $book->disconnectWorksheets();
        unset($book);

        return $ruta;
    }

    public function agregar(
        string $ruta,
        array $filas
    ): void {
        if ($filas === []) {
            return;
        }

        $book = IOFactory::load($ruta);

        $base = $book->getSheetByName('CATALOGO');

        if (
            $base instanceof Worksheet
            && $book->getSheetCount() === 1
            && $base->getCell('A1')->getValue() === null
        ) {
            $book->removeSheetByIndex(
                $book->getIndex($base)
            );
        }

        foreach ($filas as $fila) {
            $marca = strtoupper(
                trim((string) ($fila['marca'] ?? ''))
            );

            if ($marca === '') {
                $marca = 'SIN_MARCA';
            }

            $nombre = $this->nombreHoja($marca);
            $sheet = $this->obtenerHoja($book, $nombre);

            if ($sheet->getHighestRow() === 1
                && $sheet->getCell('A1')->getValue() === null
            ) {
                $this->cabecera($sheet);
            }

            $filaExcel = $sheet->getHighestRow() + 1;

            $valores = [
                $fila['id'] ?? '',
                $marca,
                $fila['motor'] ?? '',
                $fila['model'] ?? '',
                $fila['descr'] ?? '',
                $fila['codig'] ?? '',
                $fila['origi'] ?? '',
                $fila['medid'] ?? '',
                $fila['super'] ?? '',
                $fila['equiv'] ?? '',
                $fila['anio'] ?? '',
                $fila['qty'] ?? 0,
                $fila['pcomp'] ?? 0,
                $fila['pvent'] ?? 0,
                $fila['fecha'] ?? '',
            ];

            $sheet->fromArray(
                $valores,
                null,
                'A' . $filaExcel
            );
        }

        $book->setActiveSheetIndex(0);

        $writer = IOFactory::createWriter($book, 'Xlsx');
        $writer->save($ruta);

        $book->disconnectWorksheets();
        unset($book);
    }

    private function obtenerHoja(
        Spreadsheet $book,
        string $nombre
    ): Worksheet {
        $sheet = $book->getSheetByName($nombre);

        if ($sheet instanceof Worksheet) {
            return $sheet;
        }

        $sheet = $book->createSheet();
        $sheet->setTitle($nombre);
        $this->cabecera($sheet);

        return $sheet;
    }

    private function cabecera(Worksheet $sheet): void
    {
        $sheet->fromArray(
            self::CAMPOS,
            null,
            'A1'
        );

        $sheet->freezePane('A2');
        $sheet->getStyle('A1:O1')->getFont()->setBold(true);
    }

    private function nombreHoja(string $marca): string
    {
        $nombre = preg_replace(
            '/[\\\\\\/\\?\\*\\[\\]:]/',
            '_',
            $marca
        );

        $nombre = trim((string) $nombre);

        if ($nombre === '') {
            $nombre = 'SIN_MARCA';
        }

        return mb_substr($nombre, 0, 31);
    }
}
