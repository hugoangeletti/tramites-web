<?php
session_start(); //comentar esta linea si no se trabaja con sesiones
//require_once 'dataAccess/sessionControl.php';
ini_set('default_charset', 'utf8');
date_default_timezone_set("America/Argentina/Buenos_Aires");

$proyecto = "Portal del Colegio de Medicos Distrito I";

if (file_exists(__DIR__ . '/secrets.php')) {
    require_once __DIR__ . '/secrets.php';
} else {
    define("CLAVE_SECRETA", getenv('RECAPTCHA_SECRET') ?: '');
}

define("ENV", 'desa');
//define("ENV", 'prod');

//obtengo el periodo actual
$periodoActual = date('Y');
$mes = date('m');
if ($mes >= 1 && $mes <= 5) {
    $periodoActual -= 1;
}
define("PERIODO_ACTUAL", $periodoActual);
define("IMPRIMIR_TRIMESTRAL", TRUE);

define("CANTIDAD_SOLICITUDES_PERMITIDAS", 10);

require_once (__DIR__) . '/funcionesSeguridad.php';

