<?php
require_once '../dataAccess/config.php';
permisoLogueado();
require_once '../html/head.php';
require_once '../html/header.php';
require_once '../dataAccess/funcionesPhp.php';

$continuar = true;
if (isset($_GET['id']) && $_GET['id'] == $_SESSION['hashColegiado']) {
    $hashColegiado = $_SESSION['hashColegiado'];

    $origen = (isset($_GET['origen']) && $_GET['origen'] == 'curso') ? 'curso' : 'colegiacion';
    if ($origen == 'curso') {
        if (isset($_GET['idCurso']) && $_GET['idCurso'] <> '') {
            $idCurso = $_GET['idCurso'];
        } else {
            $continuar = FALSE;
        }
    }

    $estadoIntencion = ($origen == 'curso')
        ? (isset($_SESSION['intencionesPagoPendientesCurso'][$idCurso]['estado']) ? $_SESSION['intencionesPagoPendientesCurso'][$idCurso]['estado'] : 'enviada')
        : (isset($_SESSION['intencionPagoPendiente']['estado']) ? $_SESSION['intencionPagoPendiente']['estado'] : 'enviada');
    if ($estadoIntencion <> 'iniciada' && $estadoIntencion <> 'enviada') {
        // Gire ya informó un resultado (aprobada/denegada/espera): no se puede anular
        $continuar = FALSE;
        $mensajeGuard = "El pago ya fue procesado, no puede anularse.";
    } else if (isset($_POST['hashIntencionPago']) && $_POST['hashIntencionPago'] <> '') {
        $hashIntencionPago = $_POST['hashIntencionPago'];
    } else {
        $continuar = FALSE;
    }
} else {
    $continuar = FALSE;
}

if ($continuar) {
    $data = array("hashIntencionPago" => $hashIntencionPago);
    $dataString = json_encode($data);

    $url = URL_WS.'/cobranza/anular_intencion_pago.php';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($dataString)
    ));
    $result = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    $rta = json_decode($result, true);
    if (!$err && isset($rta['codigo']) && $rta['codigo'] == 1) {
        if ($origen == 'curso') {
            unset($_SESSION['intencionesPagoPendientesCurso'][$idCurso]);
        } else {
            unset($_SESSION['intencionPagoPendiente']);
        }
        $mensaje = "La intenci&oacute;n de pago fue anulada correctamente.";
        $clase = "alert alert-success";
    } else {
        $continuar = FALSE;
        $mensaje = (isset($rta['mensaje']) && $rta['mensaje'] <> '' && $rta['mensaje'] <> 'OK') ? $rta['mensaje'] : "No se pudo anular la intenci&oacute;n de pago. Intente nuevamente.";
        $clase = "alert alert-danger";
    }
} else {
    $mensaje = isset($mensajeGuard) ? $mensajeGuard : "Solicitud inv&aacute;lida.";
    $clase = "alert alert-danger";
}

if (isset($hashColegiado) && $hashColegiado <> "") {
    $urlVolver = (isset($origen) && $origen == 'curso')
        ? 'cuotas_curso.php?id=' . $hashColegiado . '&reg=' . (isset($idCurso) ? $idCurso : '')
        : 'cuotas.php?id=' . $hashColegiado;
?>
    <body onLoad="document.forms['myForm'].submit()">
        <form name="myForm" method="POST" action="<?php echo $urlVolver; ?>">
            <input type="hidden" name="mensaje" value="<?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="clase" value="<?php echo htmlspecialchars($clase, ENT_QUOTES, 'UTF-8'); ?>">
        </form>
    </body>
<?php
} else {
?>
    <div class="row alert alert-danger">
        <div class="col-md-10">
            <h4 class=""><b><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></b></h4>
        </div>
        <div class="col-md-2">
            <a href="tramites.php" class="btn btn-danger">Volver</a>
        </div>
    </div>
<?php
}
include("../html/footer.php");
?>
  </div>

</body>
