<?php
require_once '../dataAccess/config.php';
permisoLogueado();
require_once '../html/head.php';
require_once '../html/header_embed.php';
require_once '../dataAccess/funcionesPhp.php';

$continuar = true;
$mensaje = "";
if (isset($_POST['matricula']) && $_POST['matricula'] <> "") {
    $matricula = $_POST['matricula'];
} else {
    $continuar = FALSE;
    $mensaje .= 'Matrícula erronea - ';
}
if (isset($_POST['mail']) && $_POST['mail'] <> "") {
    $mail = $_POST['mail'];
} else {
    $continuar = FALSE;
    $mensaje .= 'Correo Electrónico erroneo - ';
}
if (isset($_POST['tipoTelefono']) && $_POST['tipoTelefono'] <> "") {
    $tipoTelefono = $_POST['tipoTelefono'];
} else {
    $continuar = FALSE;
    $mensaje .= 'Tipo de Teléfono erroneo - ';
}
if (isset($_POST['telefono']) && $_POST['telefono'] <> "") {
    $telefono = $_POST['telefono'];
} else {
    $continuar = FALSE;
    $mensaje .= 'Teléfono erroneo - ';
}

if ($continuar) {
    //obtener los datos del colegiDO
    ini_set('xdebug.var_display_max_depth', -1);
    ini_set('xdebug.var_display_max_children', -1);
    ini_set('xdebug.var_display_max_data', -1);
    set_time_limit(0);

    $ch = curl_init();
    
    if (ENV == "prod") {
        curl_setopt($ch, CURLOPT_URL, 'http://webservices.colmed1.com.ar/colegio/ws-colmed/colegiado/solicitar_ampliacion_seguro.php?matricula='.$matricula.'&tipoTelefono='.$tipoTelefono.'&telefono='.$telefono);
    } else {
        curl_setopt($ch, CURLOPT_URL, 'http://www.colmed1.com/desarrollo/colegio/ws-colmed/colegiado/solicitar_ampliacion_seguro.php?matricula='.$matricula.'&tipoTelefono='.$tipoTelefono.'&telefono='.$telefono);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    //curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);

    $headers = array();
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $respuesta = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);

    if ($err) {
      echo "cURL Error #:" . $err;
    } else {
      switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
        case 200:  
            $rta=(json_decode($respuesta,true));
            //var_dump($rta);
            if (isset($rta) && isset($rta['respuesta'])) {
                $respuesta = $rta['respuesta'];
                if ($respuesta['codigo'] == 1) {
                    $certificado = $respuesta['certificado'];
                } else {
                    /*
                    ?>
                    <h4 style="color: red;"<b>Error al buscar certificado - <?php echo $respuesta['mensaje']; ?></b></h4>
                    <?php
                    */
                    //$continuar = FALSE;
                }
            } else {
                $continuar = FALSE;
                $mensaje .= 'Momentaneamente fuera de servicio, vuelva a intentar más tarde - ';
            }
            break;

        case 400:  
            $rta=(json_decode($respuesta,true));
            $continuar = FALSE;
            $mensaje .= 'ERROR 400 - ';
            break;

        default:
            $mensaje .= 'Codigo HTTP inesperado: '.$http_code;
            $continuar = FALSE;
            break;
        }

    }
} else {
    $continuar = FALSE;
}
$resultado['mensaje'] = $mensaje;
if ($continuar) {
    $resultado['clase'] = 'alert alert-success'; 
} else {
    $resultado['clase'] = 'alert alert-danger'; 
}
?>
<body onLoad="document.forms['myForm'].submit()">
    <form name="myForm"  method="POST" action="tramites.php">
        <input type="hidden"  name="mensaje" id="mensaje" value="<?php echo $resultado['mensaje']; ?>">
        <input type="hidden"  name="clase" id="clase" value="<?php echo $resultado['clase']; ?>">
        <input type="hidden"  name="estado" id="estado" value="<?php echo $continuar; ?>">
    </form>
</body>