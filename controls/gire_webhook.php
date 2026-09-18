<?php
// Notificación server-to-server que manda Gire cuando cambia el estado de un
// checkout (webhook). No depende de que el navegador del colegiado complete
// el regreso desde Gire — por eso existe, a diferencia de pago_procesado.php.
//
// Forma real confirmada con un pago de prueba (aprobado, modo test) el
// 2026-09-15. El payload viene envuelto en "data.payment":
//   data.payment.reference        -> el hashIntencionPago que mandamos como "reference" al crear el checkout
//   data.payment.status.code      -> código de estado (mismo rango que "status" en el return_url: 200-399 aprobada, 400+ denegada)
//   data.payment.id               -> id de la transacción en Gire (idéntico a data.payment.source.transaction.transactionId)
//   data.result                   -> booleano de éxito/fracaso general
// Sigue sin haber un mecanismo de autenticación documentado (firma/header
// secreto) para verificar que la notificación es realmente de Gire.
// Además del webhook de una transacción normal, Gire manda uno de tipo
// "checkout_expired" cuando se cumple el timeout del checkout sin que se haya
// completado ningún pago (nunca hubo intento, así que no hay nodo
// "data.payment"). Según la documentación pública (todavía sin confirmar
// contra un caso real): el hash viaja en "data.checkout.reference" y el
// status code en "data.status.code" = 401. Por eso la extracción de abajo
// prueba primero la forma de pago normal y, si no encuentra nada ahí, cae a
// estos campos alternativos. Para este tipo se informa siempre "expirada"
// (INTENCION_PAGO_EXPIRADO = '7' del lado del WS), sin importar el status
// code, porque no hubo intento de pago que clasificar como aprobado/denegado.
// Si status.code no cae en ningún rango conocido, se informa "espera" en vez
// de asumir aprobado o denegado sin certeza.
require_once '../dataAccess/config.php';
require_once '../dataAccess/funcionesPhp.php';

set_time_limit(0);

$cuerpoCrudo = file_get_contents('php://input');
$datos = json_decode($cuerpoCrudo, true);
if (!is_array($datos)) {
    // Por si Gire lo manda como form-encoded en vez de JSON
    $datos = $_POST;
}

// Guarda cabeceras + payload crudo para poder revisar la forma real del
// primer webhook que llegue. logs/ no viaja al repo (ver .gitignore).
$logDir = __DIR__ . '/../dataAccess/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$cabeceras = function_exists('getallheaders') ? getallheaders() : array();
$lineaLog = json_encode(array(
    'fecha'     => date('Y-m-d H:i:s'),
    'headers'   => $cabeceras,
    'query'     => $_GET,
    'cuerpo'    => $cuerpoCrudo,
), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@file_put_contents($logDir . '/gire_webhook.log', $lineaLog . "\n", FILE_APPEND);

$payment = isset($datos['data']['payment']) && is_array($datos['data']['payment']) ? $datos['data']['payment'] : array();
$checkout = isset($datos['data']['checkout']) && is_array($datos['data']['checkout']) ? $datos['data']['checkout'] : array();

// hashIntencionPago es nuestro propio hash (el que se generó al insertar la
// intención de pago), no un id que devuelva Gire. Viaja como "reference" en
// el checkout (lo mandamos nosotros al crearlo) y Gire lo devuelve tal cual —
// dentro de "data.payment.reference" en una transacción normal, o de
// "data.checkout.reference" cuando el checkout venció sin ningún intento de pago.
$hashIntencionPago = isset($payment['reference']) ? $payment['reference'] : (isset($checkout['reference']) ? $checkout['reference'] : null);
$statusCode = isset($payment['status']['code']) ? $payment['status']['code'] : (isset($datos['data']['status']['code']) ? $datos['data']['status']['code'] : null);
$transactionId = isset($payment['source']['transaction']['transactionId']) ? $payment['source']['transaction']['transactionId'] : null;
$tipoWebhook = isset($datos['type']) ? $datos['type'] : null;

$statusNum = is_numeric($statusCode) ? (int)$statusCode : NULL;

// Mismo rango de status que ya usa pago_procesado.php para el regreso por navegador
$estadoPago = NULL;
if ($hashIntencionPago <> '' && $hashIntencionPago !== null) {
    if ($tipoWebhook === 'checkout_expired') {
        $estadoPago = 'expirada';
    } else if ($statusNum !== NULL && $statusNum >= 200 && $statusNum <= 399) {
        $estadoPago = 'aprobada';
    } else if ($statusNum !== NULL && $statusNum >= 400) {
        $estadoPago = 'denegada';
    } else {
        $estadoPago = 'espera';
    }

    llamarWs(URL_WS.'/cobranza/marcar_intencion_enviada.php', 'POST', array(
        "hashIntencionPago" => $hashIntencionPago,
        "estado"            => $estadoPago,
        "idGire"            => $transactionId,
        "respuestaGire"     => $cuerpoCrudo,
    ));
}

// Gire espera una respuesta HTTP 200 simple para dar la notificación por
// entregada; nunca HTML del sitio.
header('Content-Type: application/json');
http_response_code(200);
echo json_encode(array('ok' => true));
