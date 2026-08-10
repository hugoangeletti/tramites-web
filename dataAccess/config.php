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
    define("GIRE_API_KEY", getenv('GIRE_API_KEY') ?: '');
    define("GIRE_ACCESS_TOKEN", getenv('GIRE_ACCESS_TOKEN') ?: '');
}

define("GIRE_CHECKOUT_URL", "https://api.bdp.gire.com/p/checkout");

define("ENV", 'desa');
//define("ENV", 'prod');

// Mientras la integración con Gire no esté habilitada, los botones de pago
// en línea no se muestran en ningún entorno. Poner en TRUE para activarlos.
define("MOSTRAR_PAGO_EN_LINEA", TRUE);

define("PATH_HOME", (ENV == "prod") ? "http://www.colmed1.com.ar/tramites-web/" : "http://localhost/tramites-web/");

// URL base del web service, usada por todos los controladores
define("URL_WS", (ENV == "prod")
    ? "http://webservices.colmed1.com.ar/colegio/ws-colmed"
    : "http://www.colmed1.com/desarrollo/colegio/ws-colmed");

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

