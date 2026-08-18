<?php
// ENV se define antes de arrancar la sesión porque en producción hace falta
// configurar la cookie de sesión (SameSite=None; Secure) con session_set_cookie_params()
// antes de llamar a session_start() — no se puede hacer después.
//define("ENV", 'desa');
define("ENV", 'prod');

if (ENV == "prod") {
    // Por defecto, los navegadores tratan la cookie de sesión como SameSite=Lax,
    // que NO viaja cuando se vuelve de un salto entre dominios hecho por POST —
    // así redirige típicamente la verificación 3D Secure del banco al volver del
    // pago con Gire (por eso la sesión se perdía en mobile con tarjeta, pero no en
    // desktop). SameSite=None arregla eso, pero exige que la cookie sea Secure
    // (solo viaja por HTTPS): por eso PATH_HOME pasa a https:// más abajo, y hace
    // falta que el servidor redirija http-> https siempre (ver .htaccess).
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'None',
    ));
}

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

// Modo prueba del checkout de Gire (permite operar con las tarjetas de test).
// Por ahora queda en TRUE en todos los entornos, incluido producción.
// Poner en FALSE cuando se habiliten los pagos reales.
define("GIRE_MODO_TEST", TRUE);

// Mientras la integración con Gire no esté habilitada, los botones de pago
// en línea no se muestran en ningún entorno. Poner en TRUE para activarlos.
define("MOSTRAR_PAGO_EN_LINEA", TRUE);

// En prod va HTTPS: la cookie Secure (ver arriba) no se guarda si el sitio se
// sirve por HTTP. El WS interno (URL_WS, más abajo) sigue sin soportar HTTPS,
// pero es un dominio y una conexión servidor-a-servidor distinta a esta.
define("PATH_HOME", (ENV == "prod") ? "https://www.colmed1.com.ar/portal/" : "http://localhost/tramites-web/");

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

