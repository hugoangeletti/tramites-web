<?php
require_once '../dataAccess/config.php';
permisoLogueado();
require_once '../html/head.php';
require_once '../html/header.php';
require_once '../dataAccess/funcionesPhp.php';
require_once '../html/menuTramites.php';

$continuar = true;
$mensaje = "";
if (isset($_GET['id']) && $_GET['id'] == $_SESSION['hashColegiado']) {
    $idColegiado = $_SESSION['idColegiado'];
    $hashColegiado = $_SESSION['hashColegiado'];

    if (isset($_POST['hashIntencionPago']) && $_POST['hashIntencionPago'] <> '') {
        $hashIntencionPago = $_POST['hashIntencionPago'];
    } else {
        $continuar = FALSE;
        $mensaje .= "Falta la intención de pago a procesar. ";
    }

    if (isset($_POST['totalActualizado'])) {
        $valorLimpio = str_replace(',', '.', $_POST['totalActualizado']);
        if (filter_var($valorLimpio, FILTER_VALIDATE_FLOAT) !== false) {
            $totalActualizado = $valorLimpio;
        } else {
            $continuar = FALSE;
            $mensaje .= "El total a pagar no es válido. ";
        }
    } else {
        $continuar = FALSE;
        $mensaje .= "Falta el total a pagar. ";
    }
} else {
    $continuar = FALSE;
}

if ($continuar) {
    // Buscamos el teléfono y el mail de contacto del colegiado para armar el "customer" de Gire
    ini_set('xdebug.var_display_max_depth', -1);
    ini_set('xdebug.var_display_max_children', -1);
    ini_set('xdebug.var_display_max_data', -1);
    set_time_limit(0);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, URL_WS.'/colegiado/buscar_datos_contacto.php?idColegiado='.$idColegiado);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    $respuestaContacto = curl_exec($ch);
    curl_close($ch);

    $rtaContacto = json_decode($respuestaContacto, true);
    $telefono = '';
    $email = isset($_SESSION['user_entidad']['mail']) ? $_SESSION['user_entidad']['mail'] : '';
    if (isset($rtaContacto['respuesta']['datos'])) {
        $datosContacto = $rtaContacto['respuesta']['datos'];
        $telefono = !empty($datosContacto['telefonoMovil']) ? $datosContacto['telefonoMovil'] : (!empty($datosContacto['telefonoFijo']) ? $datosContacto['telefonoFijo'] : '');
        if (!empty($datosContacto['email'])) {
            $email = $datosContacto['email'];
        }
    }

    $dni = isset($_SESSION['user_entidad']['dni']) ? $_SESSION['user_entidad']['dni'] : '';

    // Armamos el checkout según la documentación de Gire: https://docs.bdp.gire.com/WDAYpKYAjXvLsFv1sXlV6
    $data = array(
        "total"       => (float)$totalActualizado,
        "description" => "Pago de cuotas - Colegio de Médicos Distrito I",
        "currency"    => "ars",
        "reference"   => $hashIntencionPago,
        "return_url"  => PATH_HOME . "controls/tramites.php",
        "test"        => (ENV != "prod"),
        "customer"    => array(
            "email"          => $email,
            "name"           => $_SESSION['apellidoNombre'],
            "identification" => $dni,
            "phone"          => $telefono,
            "uid"            => (string)$idColegiado,
        ),
    );

    $dataString = json_encode($data);

    $ch = curl_init(GIRE_CHECKOUT_URL);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Api-Key: ' . GIRE_API_KEY,
        'X-Access-Token: ' . GIRE_ACCESS_TOKEN,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($dataString)
    ));
    $result = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    $rta = json_decode($result, true);
    if (!$err && isset($rta['result']) && $rta['result'] === true && isset($rta['data']['url'])) {
        $urlCheckout = $rta['data']['url'];
    } else {
        $continuar = FALSE;
        $mensaje .= "No se pudo generar el checkout de pago. Intente nuevamente.";
    }
}

if ($continuar) {
?>
    <body onLoad="window.location.href='<?php echo htmlspecialchars($urlCheckout, ENT_QUOTES, 'UTF-8'); ?>'">
        <div class="card drive-card drive-banner mb-4">
            <div class="card-body">
                <p>Redirigiendo al medio de pago...</p>
                <a href="<?php echo htmlspecialchars($urlCheckout, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">Continuar al pago</a>
            </div>
        </div>
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
require_once "../html/menuTramitesClose.php";
include("../html/footer.php");
?>
  </div>

</body>
