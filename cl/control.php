<?php
/* cl/control.php */

$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

/* quitar index.php */
$url = str_replace('/index.php','',$url);

/* normalizar */
$url = rtrim($url,'/');

if($url == ''){
    $url = '/';
}

switch($url){

    case '/':
        $vista = 'in.php';
        break;

    case '/productos':
        $vista = 'pd.php';
        break;

    case '/nosotros':
        $vista = 'ns.php';
        break;

    case '/contacto':
        $vista = 'co.php';
        break;

    default:
        $vista = '404.php';
}

/* verificar existencia */
$ruta = __DIR__ . '/../pg/' . $vista;

if(!file_exists($ruta)){
    $ruta = __DIR__ . '/../pg/404.php';
}