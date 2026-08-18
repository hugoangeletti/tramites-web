<?php
require_once '../dataAccess/config.php';
require_once '../dataAccess/funcionesPhp.php';

// Diagnóstico de la pérdida de sesión en mobile: se compara el id de sesión que
// salió hacia Gire (sidSalida) contra la cookie que efectivamente volvió, para
// distinguir si el problema es que la cookie no llega, o que sí llega pero los
// datos de esa sesión ya no están del lado del servidor.
$diagSidSalida = isset($_GET['sidSalida']) ? trim($_GET['sidSalida']) : '';
$diagCookiePresente = isset($_COOKIE[session_name()]);
$diagCookieValor = $diagCookiePresente ? $_COOKIE[session_name()] : '';
$diagSidLlegada = session_id();

// Gire redirige acá cuando termina el checkout (return_url), agregando status,
// type y transactionId. El id de la intención viaja como parámetro propio
// (?intencion=...), no se lee de la sesión: el viaje a Gire y, si el banco pide
// una verificación adicional (3D Secure), a la página del banco y de vuelta,
// puede tardar lo suficiente como para que la sesión PHP venza en el medio. Por
// eso todo lo que informa el resultado al WS se hace ANTES de tocar la sesión
// o de exigir que siga siendo válida.
$hashIntencionPago = isset($_GET['intencion']) ? trim($_GET['intencion']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$transactionId = isset($_GET['transactionId']) ? trim($_GET['transactionId']) : '';

set_time_limit(0);

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
$resultadoInforme = NULL;
if ($estadoPago !== NULL && $hashIntencionPago <> '') {
    $respuestaGire = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    $resultadoInforme = llamarWs(URL_WS.'/cobranza/marcar_intencion_enviada.php', 'POST', array(
        "hashIntencionPago" => $hashIntencionPago,
        "estado"            => $estadoPago,
        "idGire"            => $transactionId,
        "respuestaGire"     => $respuestaGire
    ));
}

// Recién a partir de acá depende de que la sesión siga viva: si sigue siendo
// válida se muestra la pantalla completa (menú, estado actualizado de cuotas);
// si no, un aviso simple invitando a volver a ingresar. El resultado del pago
// ya quedó informado arriba en cualquiera de los dos casos.
$sesionValida = logueado();

require_once '../html/head.php';
require_once '../html/header.php';

$estadoConsultado = FALSE;
if ($sesionValida) {
    permisoLogueado();
    require_once '../html/menuTramites.php';

    $matricula = $_SESSION['matricula'];
    $hashColegiado = $_SESSION['hashColegiado'];

    // Se relee el estado para dejar la sesión alineada con lo que el WS registró
    $rWs = llamarWs(URL_WS.'/colegiado/buscar_colegiado.php?matricula='.$matricula);
    $estadoConsultado = $rWs['ok'];
    if ($estadoConsultado) {
        $intencion = armarIntencionPagoPendiente($rWs['nodo']);
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

        <?php if (!$sesionValida) { ?>
            <div class="alert alert-warning">
                Su sesi&oacute;n expir&oacute; durante el pago (puede pasar si el banco pidi&oacute;
                una verificaci&oacute;n adicional). El resultado ya qued&oacute; registrado; vuelva a
                ingresar para ver el estado actualizado de sus cuotas.
            </div>
        <?php } else if (!$estadoConsultado) { ?>
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
                    <b>Sesi&oacute;n v&aacute;lida al volver:</b> <?php echo $sesionValida ? 'si' : 'no'; ?><br>
                    <b>--- Diagn&oacute;stico de la cookie ---</b><br>
                    <b>Id de sesi&oacute;n al salir hacia Gire:</b> <?php echo htmlspecialchars($diagSidSalida, ENT_QUOTES, 'UTF-8'); ?><br>
                    <b>&iquest;Lleg&oacute; la cookie de sesi&oacute;n?:</b> <?php echo $diagCookiePresente ? 'si' : 'NO'; ?><br>
                    <b>Id de sesi&oacute;n al volver:</b> <?php echo htmlspecialchars($diagSidLlegada, ENT_QUOTES, 'UTF-8'); ?><br>
                    <b>&iquest;Coinciden?:</b> <?php echo ($diagSidSalida <> '' && $diagSidSalida === $diagSidLlegada) ? 'SI - la cookie viajó bien, el problema está en el servidor' : 'NO - la cookie no volvió (o volvió con otro id)'; ?><br>
                    <b>--- fin diagn&oacute;stico ---</b><br>
                    <b>Estado informado al WS:</b> <?php echo htmlspecialchars($estadoPago, ENT_QUOTES, 'UTF-8'); ?><br>
                    <?php if ($resultadoInforme !== NULL) { ?>
                        <b>Respuesta del WS:</b>
                        <?php echo htmlspecialchars(json_encode($resultadoInforme['cruda']), ENT_QUOTES, 'UTF-8'); ?>
                    <?php } else { ?>
                        <b>Sin intenci&oacute;n de pago en la URL:</b> no se inform&oacute; al WS.
                    <?php } ?>
                </small>
            </div>
        <?php } ?>

        <?php if ($sesionValida) { ?>
            <a href="cuotas.php?id=<?php echo $hashColegiado; ?>" class="btn btn-info">Ver mis cuotas</a>
            <a href="tramites.php" class="btn btn-secondary">Volver a tr&aacute;mites</a>
        <?php } else { ?>
            <a href="login.php" class="btn btn-info">Volver a ingresar</a>
        <?php } ?>
    </div>
</div>
<?php
if ($sesionValida) {
    require_once "../html/menuTramitesClose.php";
}
include("../html/footer.php");
?>
  </div>

</body>
