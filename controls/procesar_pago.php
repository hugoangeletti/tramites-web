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

// Si la intención ya se envió a Gire, el pago espera la rendición diaria: no se
// genera un checkout nuevo para evitar que se abone dos veces la misma deuda
if ($continuar && !empty($_SESSION['intencionPagoPendiente']['enviada'])) {
    $continuar = FALSE;
    $mensaje .= "Este pago ya fue enviado y est&aacute; pendiente de acreditaci&oacute;n. No es necesario volver a abonarlo.";
}

// Sin credenciales de Gire la API responde 401 "el API Key es obligatorio", así que
// se avisa antes de llamarla. secrets.php no viaja por git: hay que cargarlo en cada servidor.
if ($continuar && (trim(GIRE_API_KEY) == '' || trim(GIRE_ACCESS_TOKEN) == '')) {
    $continuar = FALSE;
    $mensaje .= "Faltan las credenciales de Gire en dataAccess/secrets.php de este servidor.";
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
        "return_url"  => PATH_HOME . "controls/pago_procesado.php",
        "test"        => GIRE_MODO_TEST,
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
    // Los nombres de los headers van en minúscula, tal cual los documenta Gire
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'x-api-key: ' . GIRE_API_KEY,
        'x-access-token: ' . GIRE_ACCESS_TOKEN,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($dataString)
    ));
    $result = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $rta = json_decode($result, true);
    if (!$err && isset($rta['result']) && $rta['result'] === true && isset($rta['data']['url'])) {
        $urlCheckout = $rta['data']['url'];

        // Se avisa al WS que la intención ya se envió a Gire, para que no se
        // pueda volver a pagar ni anular mientras espera la rendición diaria
        $dataEnviada = json_encode(array("hashIntencionPago" => $hashIntencionPago));
        $chEnviada = curl_init(URL_WS.'/cobranza/marcar_intencion_enviada.php');
        curl_setopt($chEnviada, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($chEnviada, CURLOPT_POSTFIELDS, $dataEnviada);
        curl_setopt($chEnviada, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chEnviada, CURLOPT_TIMEOUT, 10);
        curl_setopt($chEnviada, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($dataEnviada)
        ));
        curl_exec($chEnviada);
        curl_close($chEnviada);

        // La sesión se actualiza igual, así el aviso ya sale correcto al volver
        if (isset($_SESSION['intencionPagoPendiente'])) {
            $_SESSION['intencionPagoPendiente']['enviada'] = TRUE;
        }
    } else {
        $continuar = FALSE;
        $mensaje .= "No se pudo generar el checkout de pago. Intente nuevamente.";
        // Mientras se esté probando, se muestra lo que devolvió Gire para poder diagnosticar
        if (GIRE_MODO_TEST) {
            if ($err) {
                $mensaje .= " Error de conexi&oacute;n: " . $err;
            } else {
                $mensaje .= " Respuesta de la API (HTTP " . $httpCode . "): " . $result;
            }
        }
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
