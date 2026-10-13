<?php
/* ============================================================
   Ruta: modul/categ/venta/clien/exter-servi.php
   Módulo: Ventas / Cliente / Fuente externa

   FUNCIÓN:
   Consultar una fuente externa de RUC/DNI y normalizar
   únicamente los datos necesarios para Operaciones.

   REGLAS:
   - Los nombres internos respetan la convención máxima
     de 5 caracteres.
   - Los nombres definidos por la API externa se mantienen
     exactamente como los entrega la API.
   - La regla de 5 caracteres no se aplica a nombres propios
     de la API externa.
   - La prueba directa es temporal.
============================================================ */

declare(strict_types=1);


/* ============================================================
   NIVEL 1: SERVICIO DE CLIENTE EXTERNO
============================================================ */

final class OperaClienteExterno
{
    /* ========================================================
       NIVEL 2: CONSULTAR CLIENTE EXTERNO
    ======================================================== */

    public static function consultar(string $docum): array
    {
        /* ====================================================
           NIVEL 3: DETERMINAR TIPO DE DOCUMENTO
        ==================================================== */

        $tipo = strlen($docum) === 11
            ? 'ruc'
            : (strlen($docum) === 8 ? 'dni' : '');

        if ($tipo === '') {
            return [
                'ok' => false,
                'encontrado' => false,
                'mensaje' => 'RUC/DNI no válido.'
            ];
        }


        /* ====================================================
           NIVEL 3: CREDENCIAL TEMPORAL
        ==================================================== */

        $token = 'sk-token';


        /* ====================================================
           NIVEL 3: CONSTRUIR URL

           "numero" pertenece a la API externa.
           Se mantiene sin modificar.
        ==================================================== */

        $url = $tipo === 'ruc'
            ? 'https://api.apis.net.pe/v1/ruc?numero=' . rawurlencode($docum)
            : 'https://api.apis.net.pe/v1/dni?numero=' . rawurlencode($docum);


        /* ====================================================
           NIVEL 3: PREPARAR RESPUESTA
        ==================================================== */

        $respuesta = false;
        $codig = 0;
        $error = '';


        /* ====================================================
           NIVEL 4: CONSULTA MEDIANTE CURL

           Se utiliza cuando curl está disponible.
        ==================================================== */

        if (function_exists('curl_init')) {

            $ch = curl_init($url);

            if ($ch !== false) {

                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $token,
                        'Accept: application/json'
                    ]
                ]);

                $respuesta = curl_exec($ch);
                $codig = (int) curl_getinfo(
                    $ch,
                    CURLINFO_HTTP_CODE
                );

                $error = curl_error($ch);

                curl_close($ch);
            }

        } else {

            /* =================================================
               NIVEL 4: CONSULTA HTTP SIN CURL

               Se utiliza cuando el PHP de localhost no tiene
               disponible la extensión cURL.

               La API y el token son los mismos.
            ================================================= */

            $opci = [
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'ignore_errors' => true,
                    'header' =>
                        'Authorization: Bearer ' . $token . "\r\n" .
                        'Accept: application/json'
                ]
            ];

            $cont = stream_context_create($opci);

            $respuesta = @file_get_contents(
                $url,
                false,
                $cont
            );

            /* ================================================
               NIVEL 5: OBTENER CÓDIGO HTTP
            ================================================= */

            if (
                isset($http_response_header) &&
                is_array($http_response_header)
            ) {
                foreach ($http_response_header as $linea) {
                    if (preg_match(
                        '/^HTTP\/\S+\s+(\d{3})/',
                        $linea,
                        $mact
                    )) {
                        $codig = (int) $mact[1];
                        break;
                    }
                }
            }

            if ($respuesta === false) {
                $error = 'No se pudo consultar la fuente externa.';
            }
        }


        /* ====================================================
           NIVEL 3: VALIDAR CONSULTA
        ==================================================== */

        if ($respuesta === false || $codig !== 200) {
            return [
                'ok' => false,
                'encontrado' => false,
                'disponible' => true,
                'codig' => $codig,
                'mensaje' => $error !== ''
                    ? 'No se pudo consultar la fuente externa.'
                    : 'Cliente no encontrado en fuente externa.'
            ];
        }


        /* ====================================================
           NIVEL 3: DECODIFICAR RESPUESTA
        ==================================================== */

        $data = json_decode(
            $respuesta,
            true
        );

        if (!is_array($data)) {
            return [
                'ok' => false,
                'encontrado' => false,
                'disponible' => true,
                'mensaje' => 'Respuesta externa no válida.'
            ];
        }


        /* ====================================================
           NIVEL 3: PREPARAR DATOS NORMALIZADOS
        ==================================================== */

        $nombres = '';
        $apell = '';
        $nombr = '';
        $direc = '';


        /* ====================================================
           NIVEL 4: RUC

           "nombre" y "direccion" pertenecen a la API externa.
           Se mantienen exactamente igual.
        ==================================================== */

        if ($tipo === 'ruc') {

            $nombr = trim(
                (string) ($data['nombre'] ?? '')
            );

            $direc = trim(
                (string) ($data['direccion'] ?? '')
            );

        } else {

            /* =================================================
               NIVEL 4: DNI

               Estos nombres pertenecen a la API externa:
               - nombres
               - apellidoPaterno
               - apellidoMaterno

               No se modifican.
            ================================================= */

            $nombres = trim(
                (string) ($data['nombres'] ?? '')
            );

            $apellP = trim(
                (string) ($data['apellidoPaterno'] ?? '')
            );

            $apellM = trim(
                (string) ($data['apellidoMaterno'] ?? '')
            );


            /* =================================================
               NIVEL 5: UNIR APELLIDOS
            ================================================= */

            $apell = trim(
                implode(
                    ' ',
                    array_filter([
                        $apellP,
                        $apellM
                    ])
                )
            );


            /* =================================================
               NIVEL 5: FORMAR NOMBRE COMPLETO
            ================================================= */

            $nombr = trim(
                implode(
                    ' ',
                    array_filter([
                        $nombres,
                        $apell
                    ])
                )
            );
        }


        /* ====================================================
           NIVEL 3: VALIDAR RESULTADO
        ==================================================== */

        if ($nombr === '') {
            return [
                'ok' => false,
                'encontrado' => false,
                'disponible' => true,
                'mensaje' => 'Cliente no encontrado en fuente externa.'
            ];
        }


        /* ====================================================
           NIVEL 3: DEVOLVER RESPUESTA NORMALIZADA

           "clien" es interno y respeta 5 caracteres.

           Los nombres dentro de la respuesta normalizada
           pertenecen al contrato interno de Operaciones.
        ==================================================== */

        return [
            'ok' => true,
            'encontrado' => true,
            'disponible' => true,
            'clien' => [
                'tipo_doc' => $tipo === 'ruc' ? 'RUC' : 'DNI',
                'documento' => $docum,
                'razon_social' => $nombr,
                'direccion' => $direc,
                'nombres' => $nombres,
                'apellidos' => $apell
            ]
        ];
    }
}


/* ============================================================
   NIVEL 1: PRUEBA DIRECTA TEMPORAL

   Ejecuta exter-servi.php directamente mediante ?docum=
============================================================ */

if (
    isset($_GET['docum']) &&
    isset($_SERVER['SCRIPT_FILENAME']) &&
    realpath((string)$_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)
) {

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    $docum = trim(
        (string) $_GET['docum']
    );

    $resultado = OperaClienteExterno::consultar(
        $docum
    );

    echo json_encode(
        $resultado,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}