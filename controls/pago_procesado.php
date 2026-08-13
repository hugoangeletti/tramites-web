<?php
require_once '../dataAccess/config.php';
permisoLogueado();
require_once '../html/head.php';
require_once '../html/header.php';
require_once '../dataAccess/funcionesPhp.php';
require_once '../html/menuTramites.php';

// Gire redirige al colegiado a esta página cuando termina el checkout (return_url).
// Ese retorno viaja por el navegador, así que NO se toma como comprobante de pago:
// la confirmación real llega por el webhook al WS. Acá se vuelve a consultar el
// estado en el servidor para saber si la intención de pago ya se cerró.
$matricula = $_SESSION['matricula'];
$hashColegiado = $_SESSION['hashColegiado'];

ini_set('xdebug.var_display_max_depth', -1);
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);
set_time_limit(0);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, URL_WS.'/colegiado/buscar_colegiado.php?matricula='.$matricula);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
$respuestaWs = curl_exec($ch);
$errWs = curl_error($ch);
curl_close($ch);

$estadoConsultado = FALSE;
$intencionPendiente = TRUE;

if (!$errWs) {
    $rtaWs = json_decode($respuestaWs, true);
    if (isset($rtaWs['respuesta']) && isset($rtaWs['respuesta']['codigo']) && $rtaWs['respuesta']['codigo'] == 1) {
        $estadoConsultado = TRUE;
        $intencion = armarIntencionPagoPendiente($rtaWs['respuesta']);
        $intencionPendiente = ($intencion !== NULL);

        // Se deja la sesión alineada con lo que dice el WS
        if ($intencionPendiente) {
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
        <?php if (!$estadoConsultado) { ?>
            <div class="alert alert-warning">
                No pudimos confirmar el estado de su pago en este momento. Si el pago se realiz&oacute;,
                se ver&aacute; reflejado en unos minutos. Vuelva a consultar sus cuotas m&aacute;s tarde.
            </div>
        <?php } else if (!$intencionPendiente) { ?>
            <div class="alert alert-success">
                <b>Su pago fue confirmado.</b> Las cuotas abonadas ya no figuran como pendientes.
            </div>
        <?php } else { ?>
            <div class="alert alert-info">
                <b>Su pago est&aacute; pendiente de acreditaci&oacute;n.</b> La confirmaci&oacute;n se realiza
                con la rendici&oacute;n diaria de la cobranza, por lo que las cuotas abonadas pueden seguir
                figurando como pendientes hasta que se procese. No es necesario que vuelva a abonarlas.
            </div>
        <?php } ?>

        <?php if (GIRE_MODO_TEST && !empty($_GET)) { ?>
            <div class="alert alert-secondary">
                <small>
                    <b>Datos recibidos de Gire (visible solo en modo prueba):</b><br>
                    <?php foreach ($_GET as $clave => $valor) { ?>
                        <?php echo htmlspecialchars($clave, ENT_QUOTES, 'UTF-8'); ?>:
                        <?php echo htmlspecialchars(is_array($valor) ? json_encode($valor) : $valor, ENT_QUOTES, 'UTF-8'); ?><br>
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
