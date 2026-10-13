<?php
/* ============================================================
   Ruta: modul/pdf/pdf.php
   Módulo: PDF / Coordinador

   NIVEL 0 — ENTRADA PDF
   ------------------------------------------------------------
   Recibe la solicitud de PDF desde cualquier categoría.

   NIVEL 1 — RESOLUCIÓN
   ------------------------------------------------------------
   Identifica:
   - área / módulo
   - documento
   - id del registro

   NIVEL 2 — DATOS
   ------------------------------------------------------------
   Carga SOLO el archivo dato.php de la categoría solicitante.
   La categoría devuelve datos normalizados.

   NIVEL 3 — DISEÑO
   ------------------------------------------------------------
   Entrega esos datos al coordinador común:
       modul/pdf/comun/base.php

   IMPORTANTE:
   Este archivo NO dibuja el PDF.
   Las categorías NO dibujan el PDF.
============================================================ */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/contr/login/contr-auth.php';
require_once dirname(__DIR__, 2) . '/contr/login/login-db.php';
require_once dirname(__DIR__, 2) . '/contr/login/contr-perm.php';

if (!CAuth::autenticado()) {
    http_response_code(401);
    exit('Sesión no válida.');
}

$id = (int)($_GET['id'] ?? 0);
$modu = strtolower(trim((string)($_GET['modu'] ?? '')));
$docu = strtolower(trim((string)($_GET['docu'] ?? '')));

if ($id <= 0) {
    http_response_code(400);
    exit('Documento no válido.');
}

/* ============================================================
   NIVEL 1 — MAPA DE DATOS
   ------------------------------------------------------------
   El mapa apunta exclusivamente a dato.php.
   No existen emite.php dentro de las categorías.
============================================================ */
$mapa = [
    'venta' => [
        'cotiz' => __DIR__ . '/venta/cotiz/dato.php',
        'nvent' => __DIR__ . '/venta/nvent/dato.php',
        'devol' => __DIR__ . '/venta/devol/dato.php',
    ],
    'factu' => [
        'factu' => __DIR__ . '/factu/factu/dato.php',
        'bolet' => __DIR__ . '/factu/bolet/dato.php',
        'ncred' => __DIR__ . '/factu/ncred/dato.php',
        'ndebi' => __DIR__ . '/factu/ndebi/dato.php',
    ],
];

$perm = [
    'venta' => [
        'cotiz' => 'COTIG',
        'nvent' => 'NOVEV',
        'devol' => 'DEVOG',
    ],
    'factu' => [
        'factu' => 'FACTV',
        'bolet' => 'BOLEV',
        'ncred' => 'NCREG',
        'ndebi' => 'NDEBG',
    ],
];

$permCodi = $perm[$modu][$docu] ?? '';
$contPerm = new ContPerm(LOGINDB::abrir());
if ($permCodi === '' || !$contPerm->autorizar($permCodi)) {
    http_response_code(403);
    exit('Operación no autorizada.');
}

/* AUTORIZACIÓN — Paso 4 */

$ruta = $mapa[$modu][$docu] ?? null;

if (!$ruta || !is_file($ruta)) {
    http_response_code(404);
    exit('PDF no disponible para este documento.');
}

/* ============================================================
   NIVEL 2 — CARGA DE DATOS
   ------------------------------------------------------------
   El archivo dato.php normaliza la información del área.
   Su salida esperada es:

   [
       'doc'   => datos de cabecera/contexto,
       'detal' => detalle del documento
   ]
============================================================ */
require_once $ruta;

if (!function_exists('datos')) {
    http_response_code(500);
    exit('No existe el proveedor de datos del PDF.');
}

try {
    $data = datos($id);
} catch (Throwable $e) {
    http_response_code(500);
    exit($e->getMessage());
}

if (!is_array($data) || !isset($data['doc'], $data['detal'])) {
    http_response_code(500);
    exit('Datos de PDF incompletos.');
}

/* ============================================================
   NIVEL 3 — ENTREGA AL DISEÑO COMÚN
   ------------------------------------------------------------
   Desde aquí termina la responsabilidad de la categoría.
   El diseño, la estructura y FPDF pertenecen a comun/.
============================================================ */
require_once __DIR__ . '/comun/conf.php';
require_once __DIR__ . '/comun/base.php';

$conf = cfg();
emite($data['doc'], $data['detal'], $conf);
