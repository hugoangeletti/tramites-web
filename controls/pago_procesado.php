<?php
require_once '../dataAccess/config.php';
permisoLogueado();
require_once '../html/head.php';
require_once '../html/header.php';
require_once '../dataAccess/funcionesPhp.php';
require_once '../html/menuTramites.php';

// Gire redirige al colegiado a esta página cuando termina el checkout (return_url),
// agregando status, type y transactionId. Con eso se informa el resultado al WS y
// después se vuelve a consultar el estado, porque el retorno viaja por el navegador
// y no puede tomarse por sí solo como comprobante de pago.
$matricula = $_SESSION['matricula'];
$hashColegiado = $_SESSION['hashColegiado'];
$hashIntencionPago = isset($_SESSION['intencionPagoPendiente']['hash']) ? $_SESSION['intencionPagoPendiente']['hash'] : '';

ini_set('xdebug.var_display_max_depth', -1);
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);
set_time_limit(0);

$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$transactionId = isset($_GET['transactionId']) ? trim($_GET['transactionId']) : '';

// Traducción del retorno de Gire a los estados que acepta el WS. El parámetro
// status trae el código de estado documentado por Gire:
//   0 Sin Estado | 1 Pendiente | 2 En Espera | 3 Autorizada | 4 Entregada | 100 En Revisión
//   200 Paga | 201 Aceptada | 210 Retenida | 299 Liquidación en progreso
//   300 Acreditado | 301 Liquidado | 302 Conciliada | 303 Liberación retenida
//   400 Declinada | 401 Expirada | 402 Abandonada | 403 Fallida | 410-415 Denegada
// Ante cualquier valor no reconocido se informa "espera": deja la cuota pendiente
// y la resuelve la rendición diaria, en vez de darla por aprobada o rechazada sin certeza.
$statusNum = is_numeric($status) ? (int)$status : NULL;

// Si no llegó ningún dato de Gire, el colegiado entró a esta página por su cuenta
// (o la refrescó): no hay resultado que informar y no se toca la intención de pago.
$vieneDeGire = ($status !== '' || $transactionId !== '');
$estadoPago = NULL;

if ($vieneDeGire) {
    if ($transactionId === '-1' || $transactionId === '') {
        // Gire no generó ninguna transacción: no hubo cobro
        $estadoPago = 'denegada';
    } else if ($statusNum !== NULL && $statusNum >= 200 && $statusNum <= 399) {
        $estadoPago = 'aprobada';
    } else if ($statusNum !== NULL && $statusNum >= 400) {
        $estadoPago = 'denegada';
    } else {
        $estadoPago = 'espera';
    }
}

// Se informa el resultado al WS para que actualice la intención de pago.
// respuestaGire guarda tal cual lo que Gire devolvió en la URL, para poder
// reconstruir después qué informó ante cualquier reclamo o diferencia.
if ($estadoPago !== NULL && $hashIntencionPago <> '') {
    $respuestaGire = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    $dataEstado = json_encode(array(
        "hashIntencionPago" => $hashIntencionPago,
        "estado"            => $estadoPago,
        "idGire"            => $transactionId,
        "respuestaGire"     => $respuestaGire
    ));
    $chEstado = curl_init(URL_WS.'/cobranza/marcar_intencion_enviada.php');
    curl_setopt($chEstado, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($chEstado, CURLOPT_POSTFIELDS, $dataEstado);
    curl_setopt($chEstado, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chEstado, CURLOPT_TIMEOUT, 10);
    curl_setopt($chEstado, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($dataEstado)
    ));
    $resultEstado = curl_exec($chEstado);
    $errEstado = curl_error($chEstado);
    curl_close($chEstado);
}

// Recién ahora se relee el estado, para que la sesión quede alineada con
// lo que el WS registró a partir del resultado informado
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, URL_WS.'/colegiado/buscar_colegiado.php?matricula='.$matricula);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
$respuestaWs = curl_exec($ch);
$errWs = curl_error($ch);
curl_close($ch);

$estadoConsultado = FALSE;

if (!$errWs) {
    $rtaWs = json_decode($respuestaWs, true);
    if (isset($rtaWs['respuesta']) && isset($rtaWs['respuesta']['codigo']) && $rtaWs['respuesta']['codigo'] == 1) {
        $estadoConsultado = TRUE;
        $intencion = armarIntencionPagoPendiente($rtaWs['respuesta']);

        // Se deja la sesión alineada con lo que dice el WS
        if ($intencion !== NULL) {
            $_SESSION['intencionPagoPendiente'] = $intencion;
        } else {
            unset($_SESSION['intencionPagoPendiente']);
        }
    }
}
?>
<div class="card drive-card drive-banner mb-4">
    <div class="card-body">
        <h4 class="mb-3">Resultado del pago</h4>
        <?php if ($estadoPago == 'aprobada') { ?>
            <div class="alert alert-success">
                <b>Su pago fue aprobado.</b> La acreditaci&oacute;n se confirma con la rendici&oacute;n
                diaria de la cobranza, por lo que las cuotas abonadas pueden seguir figurando como
                pendientes hasta que se procese. No es necesario que vuelva a abonarlas.
            </div>
        <?php } else if ($estadoPago == 'denegada') { ?>
            <div class="alert alert-danger">
                <b>Su pago fue rechazado.</b> No se registr&oacute; ning&uacute;n cobro. Puede volver a
                intentarlo desde Cuotas de colegiaci&oacute;n, con el mismo u otro medio de pago.
            </div>
        <?php } else if ($estadoPago == 'espera') { ?>
            <div class="alert alert-info">
                <b>Su pago qued&oacute; pendiente.</b> Si eligi&oacute; un medio de pago en efectivo,
                el cobro se registra cuando abone el cup&oacute;n. Las cuotas pueden seguir figurando
                como pendientes hasta que se procese la rendici&oacute;n.
            </div>
        <?php } else { ?>
            <div class="alert alert-secondary">
                No hay un resultado de pago para mostrar. Puede consultar el estado de sus cuotas
                desde Cuotas de colegiaci&oacute;n.
            </div>
        <?php } ?>

        <?php if (!$estadoConsultado) { ?>
            <div class="alert alert-warning">
                No pudimos actualizar el estado de sus cuotas en este momento. Vuelva a consultarlas
                en unos minutos.
            </div>
        <?php } ?>

        <?php if (GIRE_MODO_TEST) { ?>
            <div class="alert alert-secondary">
                <small>
                    <b>Datos recibidos de Gire (visible solo en modo prueba):</b><br>
                    <?php foreach ($_GET as $clave => $valor) { ?>
                        <?php echo htmlspecialchars($clave, ENT_QUOTES, 'UTF-8'); ?>:
                        <?php echo htmlspecialchars(is_array($valor) ? json_encode($valor) : $valor, ENT_QUOTES, 'UTF-8'); ?><br>
                    <?php } ?>
                    <b>Estado informado al WS:</b> <?php echo htmlspecialchars($estadoPago, ENT_QUOTES, 'UTF-8'); ?><br>
                    <?php if ($hashIntencionPago <> '') { ?>
                        <b>Respuesta del WS:</b>
                        <?php echo htmlspecialchars($errEstado ? $errEstado : $resultEstado, ENT_QUOTES, 'UTF-8'); ?>
                    <?php } else { ?>
                        <b>Sin intenci&oacute;n de pago en sesi&oacute;n:</b> no se inform&oacute; al WS.
                    <?php } ?>
                </small>
            </div>
        <?php } ?>

        <a href="cuotas.php?id=<?php echo $hashColegiado; ?>" class="btn btn-info">Ver mis cuotas</a>
        <a href="tramites.php" class="btn btn-secondary">Volver a tr&aacute;mites</a>
    </div>
</div>
<?php
require_once "../html/menuTramitesClose.php";
include("../html/footer.php");
?>
  </div>

</body>
