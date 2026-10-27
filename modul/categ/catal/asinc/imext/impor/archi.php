<?php
/* ============================================================
   Ruta: modul/categ/catal/asinc/imext/impor/archi.php
   Módulo: Catálogo / ASINC / IMEXT / IMPORT

   Función:
   Guardar y leer el XLSX integral por lotes.

   FORMATO EXCEL:
   - Una hoja por MARCA: HD, MZ, HY, etc.
   - Hoja VR reservada al producto comodín VARIOS, sin aplicación vehicular.
   - Cada hoja utiliza el mismo encabezado:
     ID, MARCA, MOTOR, MODEL, DESCR, CODIG, ORIGI, MEDID,
     SUPER, EQUIV, ANIO, QTY, PCOMP, PVENT, FECHA.

   IMPORTANTE:
   MARCA se registra en vehic y también se intercambia en Excel.
   La hoja representa el código de marca.
   La marca no se muestra en la vista interna de Artículos.

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
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

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

        foreach ($info as $hojaInfo) {
            $nombre = (string) ($hojaInfo['worksheetName'] ?? '');
            if ($nombre === '') {
                continue;
            }

            $lectorHoja = IOFactory::createReaderForFile($ruta);
            $lectorHoja->setReadDataOnly(true);
            $lectorHoja->setLoadSheetsOnly([$nombre]);
            $libro = $lectorHoja->load($ruta);
            $hoja = $libro->getSheetByName($nombre);

            if (!$hoja) {
                $libro->disconnectWorksheets();
                unset($libro);
                throw new RuntimeException(
                    "No se pudo leer la hoja {$nombre} al contar sus filas."
                );
            }

            $ultima = $hoja->getHighestDataRow();
            for ($fila = 2; $fila <= $ultima; $fila++) {
                $conDatos = false;
                foreach (self::CAMPOS as $indice => $campo) {
                    $columna = Coordinate::stringFromColumnIndex($indice + 1);
                    $valor = $hoja->getCell($columna . $fila)->getValue();
                    if ($valor !== null && trim((string) $valor) !== '') {
                        $conDatos = true;
                        break;
                    }
                }
                if ($conDatos) {
                    $total++;
                }
            }

            $libro->disconnectWorksheets();
            unset($libro);
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
        $posicionLogica = 0;
        $terminar = false;

        foreach ($hojas as $info) {
            $nombre = (string) ($info['worksheetName'] ?? '');
            if ($nombre === '') {
                continue;
            }

            $reader = IOFactory::createReaderForFile($ruta);
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly([$nombre]);

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
            $ultima = $sheet->getHighestDataRow();

            for ($filaExcel = 2; $filaExcel <= $ultima; $filaExcel++) {
                $datos = [];
                $vacia = true;

                foreach ($cab as $indice => $nom) {
                    $columna = Coordinate::stringFromColumnIndex($indice + 1);
                    $valor = $sheet->getCell($columna . $filaExcel)->getValue();

                    if ($valor !== null && trim((string) $valor) !== '') {
                        $vacia = false;
                    }

                    if ($nom === 'fecha') {
                        $valor = $this->fechaExcel($valor);
                    }

                    $datos[$nom] = trim((string) ($valor ?? ''));
                }

                // Mismo criterio que contar(): las filas vacías no ocupan posición de lote.
                if ($vacia) {
                    continue;
                }

                $posicionLogica++;

                if ($posicionLogica < $inicio) {
                    continue;
                }

                if ($posicionLogica > $finGlobal) {
                    $terminar = true;
                    break;
                }

                $salida[] = [
                    // Fila física real dentro de la hoja de Excel.
                    'fila' => $filaExcel,
                    'hoja' => $nombre,
                    'datos' => $datos,
                ];
            }

            $book->disconnectWorksheets();
            unset($book);

            if ($terminar || $posicionLogica >= $finGlobal) {
                break;
            }
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
            $hoja = strtoupper(trim($nombre));

            if ($hoja === 'VR') {
                $descr = strtoupper(trim(
                    (string) $sheet->getCell('E2')->getValue()
                ));

                if ($marca !== '' || $descr !== 'VARIOS') {
                    $book->disconnectWorksheets();
                    unset($book);
                    throw new RuntimeException(
                        'La hoja VR debe tener MARCA vacía y DESCR igual a VARIOS en la primera fila de datos.'
                    );
                }
            } else {
                if ($marca === '') {
                    $book->disconnectWorksheets();
                    unset($book);
                    throw new RuntimeException(
                        "La hoja {$nombre} no contiene MARCA."
                    );
                }

                if (strtoupper($marca) !== $hoja) {
                    $book->disconnectWorksheets();
                    unset($book);
                    throw new RuntimeException(
                        "La MARCA de la hoja {$nombre} no coincide con su nombre."
                    );
                }
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
