<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/imext/impor/archi.php
   Módulo: Catálogo / ASINC / IMEXT / IMPORT

   Función:
   Guardar y leer el XLSX integral por lotes.

   FORMATO EXCEL:
   - Una hoja por MARCA: HD, MZ, HY, etc.
   - Cada hoja utiliza el mismo encabezado:
     ID, MARCA, MOTOR, MODEL, DESCR, CODIG, ORIGI, MEDID,
     SUPER, EQUIV, ANIO, QTY, PCOMP, PVENT, FECHA.

   IMPORTANTE:
   MARCA pertenece al intercambio Excel.
   La hoja también representa la marca.
   CATAL no la muestra en la vista interna de Artículos.

   DEPENDENCIA:
   PhpSpreadsheet mediante Composer.
============================================================ */
declare(strict_types=1);

$autoload = dirname(__DIR__, 6) . '/libre/compo/vendor/autoload.php';

if (!is_file($autoload)) {
    throw new RuntimeException(
        'No se encontró libre/compo/vendor/autoload.php.'
    );
}

require_once $autoload;

if (!interface_exists('PhpOffice\\PhpSpreadsheet\\Reader\\IReadFilter')) {
    throw new RuntimeException(
        'PhpSpreadsheet no está disponible en libre/compo/vendor.'
    );
}

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

final class ImpoFiltro implements IReadFilter
{
    public function __construct(
        private int $inicio,
        private int $fin
    ) {
    }

    public function readCell(
        $columnAddress,
        $row,
        $worksheetName = ''
    ): bool {
        return $row === 1
            || ($row >= $this->inicio && $row <= $this->fin);
    }
}

final class ImpoArchi
{
    private const CAMPOS = [
        'id',
        'marca',
        'motor',
        'model',
        'descr',
        'codig',
        'origi',
        'medid',
        'super',
        'equiv',
        'anio',
        'qty',
        'pcomp',
        'pvent',
        'fecha',
    ];

    public function guardar(array $archivo): string
    {
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo recibir el archivo.');
        }

        $ext = strtolower(
            pathinfo(
                (string) ($archivo['name'] ?? ''),
                PATHINFO_EXTENSION
            )
        );

        if ($ext !== 'xlsx') {
            throw new RuntimeException('El archivo debe ser XLSX.');
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
                'No se pudo preparar el almacenamiento temporal.'
            );
        }

        $ruta = $dir
            . DIRECTORY_SEPARATOR
            . 'impo_'
            . bin2hex(random_bytes(8))
            . '.xlsx';

        if (!move_uploaded_file(
            (string) $archivo['tmp_name'],
            $ruta
        )) {
            throw new RuntimeException(
                'No se pudo guardar el archivo.'
            );
        }

        $this->validarLibro($ruta);

        return $ruta;
    }

    public function contar(string $ruta): int
    {
        $this->cargarDependencia();

        $reader = IOFactory::createReaderForFile($ruta);
        $reader->setReadDataOnly(true);

        $info = $reader->listWorksheetInfo($ruta);
        $total = 0;

        foreach ($info as $hoja) {
            $total += max(
                0,
                (int) ($hoja['totalRows'] ?? 0) - 1
            );
        }

        if ($total < 1) {
            throw new RuntimeException(
                'El archivo no contiene registros.'
            );
        }

        return $total;
    }

    public function filas(
        string $ruta,
        int $inicio,
        int $cantidad
    ): array {
        $this->cargarDependencia();

        if ($inicio < 1 || $cantidad < 1) {
            return [];
        }

        $finGlobal = $inicio + $cantidad - 1;

        $lectorInfo = IOFactory::createReaderForFile($ruta);
        $lectorInfo->setReadDataOnly(true);
        $hojas = $lectorInfo->listWorksheetInfo($ruta);

        $salida = [];
        $desplazamiento = 0;

        foreach ($hojas as $info) {
            $nombre = (string) ($info['worksheetName'] ?? '');
            $totalFilas = max(
                0,
                (int) ($info['totalRows'] ?? 0) - 1
            );

            if ($totalFilas < 1) {
                continue;
            }

            $inicioHoja = $desplazamiento + 1;
            $finHoja = $desplazamiento + $totalFilas;

            if (
                $finHoja < $inicio
                || $inicioHoja > $finGlobal
            ) {
                $desplazamiento = $finHoja;
                continue;
            }

            $desdeLocal = max(
                1,
                $inicio - $desplazamiento
            );

            $hastaLocal = min(
                $totalFilas,
                $finGlobal - $desplazamiento
            );

            $reader = IOFactory::createReaderForFile($ruta);
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly([$nombre]);
            $reader->setReadFilter(
                new ImpoFiltro(
                    $desdeLocal + 1,
                    $hastaLocal + 1
                )
            );

            $book = $reader->load($ruta);
            $sheet = $book->getSheetByName($nombre);

            if (!$sheet) {
                $book->disconnectWorksheets();
                unset($book);
                throw new RuntimeException(
                    "No se pudo leer la hoja {$nombre}."
                );
            }

            $cab = $this->encabezados($sheet);
            $this->validarEncabezados($cab);

            for (
                $filaLocal = $desdeLocal;
                $filaLocal <= $hastaLocal;
                $filaLocal++
            ) {
                $datos = [];
                $vacia = true;

                foreach ($cab as $indice => $nom) {
                    $columna = Coordinate::stringFromColumnIndex(
                        $indice + 1
                    );

                    $valor = $sheet
                        ->getCell($columna . ($filaLocal + 1))
                        ->getValue();

                    if (
                        $valor !== null
                        && trim((string) $valor) !== ''
                    ) {
                        $vacia = false;
                    }

                    if ($nom === 'fecha') {
                        $valor = $this->fechaExcel($valor);
                    }

                    $datos[$nom] = trim(
                        (string) ($valor ?? '')
                    );
                }

                if ($vacia) {
                    continue;
                }

                $salida[] = [
                    'fila' => $desplazamiento + $filaLocal,
                    'hoja' => $nombre,
                    'datos' => $datos,
                ];
            }

            $book->disconnectWorksheets();
            unset($book);

            if ($desplazamiento + $hastaLocal >= $finGlobal) {
                break;
            }

            $desplazamiento = $finHoja;
        }

        return $salida;
    }

    private function validarLibro(string $ruta): void
    {
        $this->cargarDependencia();

        $reader = IOFactory::createReaderForFile($ruta);
        $reader->setReadDataOnly(true);

        $info = $reader->listWorksheetInfo($ruta);

        if ($info === []) {
            throw new RuntimeException(
                'El archivo no contiene hojas.'
            );
        }

        foreach ($info as $hojaInfo) {
            $nombre = (string) ($hojaInfo['worksheetName'] ?? '');

            if (trim($nombre) === '') {
                throw new RuntimeException(
                    'Existe una hoja sin nombre válido.'
                );
            }

            $readerHoja = IOFactory::createReaderForFile($ruta);
            $readerHoja->setReadDataOnly(true);
            $readerHoja->setLoadSheetsOnly([$nombre]);

            $book = $readerHoja->load($ruta);
            $sheet = $book->getSheetByName($nombre);

            if (!$sheet) {
                $book->disconnectWorksheets();
                unset($book);
                throw new RuntimeException(
                    "No se pudo abrir la hoja {$nombre}."
                );
            }

            $cab = $this->encabezados($sheet);
            $this->validarEncabezados($cab);

            if ($sheet->getHighestRow() < 2) {
                $book->disconnectWorksheets();
                unset($book);
                throw new RuntimeException(
                    "La hoja {$nombre} no contiene registros."
                );
            }

            $marca = trim(
                (string) $sheet
                    ->getCell('B2')
                    ->getValue()
            );

            if ($marca === '') {
                $book->disconnectWorksheets();
                unset($book);
                throw new RuntimeException(
                    "La hoja {$nombre} no contiene MARCA."
                );
            }

            if (
                strtoupper($marca)
                !== strtoupper(trim($nombre))
            ) {
                $book->disconnectWorksheets();
                unset($book);
                throw new RuntimeException(
                    "La MARCA de la hoja {$nombre} no coincide con su nombre."
                );
            }

            $book->disconnectWorksheets();
            unset($book);
        }
    }

    private function encabezados(object $sheet): array
    {
        $cab = [];

        foreach ($sheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $cab[] = strtolower(
                    trim((string) $cell->getValue())
                );
            }
        }

        return $cab;
    }

    private function validarEncabezados(array $cab): void
    {
        if ($cab !== self::CAMPOS) {
            throw new RuntimeException(
                'Los encabezados de IMEXT no coinciden con la plantilla integral.'
            );
        }
    }

    private function fechaExcel(mixed $valor): mixed
    {
        if ($valor === null || trim((string) $valor) === '') {
            return '';
        }

        if (
            is_numeric($valor)
            && (float) $valor > 0
            && (float) $valor < 100000
        ) {
            try {
                return ExcelDate::excelToDateTimeObject(
                    (float) $valor
                )->format('Y-m-d H:i:s');
            } catch (Throwable) {
                return $valor;
            }
        }

        return $valor;
    }

    private function cargarDependencia(): void
    {
        $autoload = dirname(__DIR__, 6)
            . '/libre/compo/vendor/autoload.php';

        if (!is_file($autoload)) {
            throw new RuntimeException(
                'PhpSpreadsheet no está instalado. Ejecute composer install.'
            );
        }

        require_once $autoload;

        if (!class_exists(IOFactory::class)) {
            throw new RuntimeException(
                'No se encontró PhpSpreadsheet.'
            );
        }
    }
}
