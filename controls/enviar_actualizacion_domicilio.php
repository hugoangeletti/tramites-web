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

    $calle = isset($_POST['calle']) ? trim($_POST['calle']) : '';
    $numero = isset($_POST['numero']) ? trim($_POST['numero']) : '';
    $piso = isset($_POST['piso']) ? trim($_POST['piso']) : '';
    $departamento = isset($_POST['departamento']) ? trim($_POST['departamento']) : '';
    $laterales = isset($_POST['laterales']) ? trim($_POST['laterales']) : '';
    $idZona = isset($_POST['idZona']) ? trim($_POST['idZona']) : '';
    $idLocalidad = isset($_POST['idLocalidad']) ? trim($_POST['idLocalidad']) : '';
    $localidadTexto = isset($_POST['localidadTexto']) ? trim($_POST['localidadTexto']) : '';
    $codigoPostal = isset($_POST['codigoPostal']) ? trim($_POST['codigoPostal']) : '';

    if ($calle == '') {
        $continuar = FALSE;
        $mensaje .= 'Debe ingresar la calle - ';
    }
    if ($numero == '') {
        $continuar = FALSE;
        $mensaje .= 'Debe ingresar el n&uacute;mero - ';
    }
    // el texto libre de localidad solo es v&aacute;lido como alternativa cuando el
    // Partido es "OTRAS LOCALIDADES" (idZona == 8); para el resto de los partidos
    // se debe seleccionar la localidad del listado si o si.
    if ($idZona == '8') {
        if ($idLocalidad == '' && $localidadTexto == '') {
            $continuar = FALSE;
            $mensaje .= 'Debe seleccionar la localidad, o ingresarla si no la encuentra en el listado - ';
        }
    } else {
        if ($idLocalidad == '') {
            $continuar = FALSE;
            $mensaje .= 'Debe seleccionar la localidad - ';
        }
        $localidadTexto = '';
    }
    if ($codigoPostal == '') {
        $continuar = FALSE;
        $mensaje .= 'Debe ingresar el c&oacute;digo postal - ';
    }

    if ($continuar) {
        // Datos a enviar en formato array
        $data = array(
            "idColegiado" => $idColegiado,
            "calle" => $calle,
            "numero" => $numero,
            "piso" => $piso,
            "departamento" => $departamento,
            "laterales" => $laterales,
            "idLocalidad" => ($idLocalidad <> '') ? $idLocalidad : NULL,
            "localidadTexto" => ($idLocalidad == '') ? $localidadTexto : NULL,
            "codigoPostal" => $codigoPostal
        );

        // Convertir array a JSON
        $data_string = json_encode($data);

        // URL de la API REST donde se enviarán los datos
        if (ENV == "prod") {
            $url = 'https://webservices.colmed1.com.ar/colegio/ws-colmed/colegiado/actualizar_domicilio_real.php';
        } else {
            $url = 'http://www.colmed1.com/desarrollo/colegio/ws-colmed/colegiado/actualizar_domicilio_real.php';
        }

        // Inicializar cURL
        $ch = curl_init($url);

        // Configurar la llamada POST con cURL
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Encabezados HTTP para indicar que se envía JSON
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ));

        // Ejecutar la solicitud y obtener la respuesta
        $result = curl_exec($ch);

        // Cerrar la sesión cURL
        curl_close($ch);
        $respuesta = (json_decode($result, true));
        if (isset($respuesta) && isset($respuesta['codigo'])) {
            if ($respuesta['codigo'] != 1) {
                $continuar = FALSE;
            }
            $mensaje .= $respuesta['mensaje'];
        } else {
            $continuar = FALSE;
            var_dump($respuesta);
            $mensaje .= "No se pudo actualizar el domicilio. ";
        }
    }
} else {
    $continuar = FALSE;
    $mensaje = "ERROR AL INGRESAR";
}
if ($continuar) {
?>
    <body onLoad="document.forms['myForm'].submit()">
        <form name="myForm"  method="POST" action="tramites.php">
            <input type="hidden"  name="mensaje" id="mensaje" value="El domicilio se actualiz&oacute; correctamente.">
            <input type="hidden"  name="clase" id="clase" value="alert alert-success">
        </form>
    </body>
<?php
} else if (isset($hashColegiado) && $hashColegiado <> "") {
    // volvemos a actualizar_domicilio_particular.php reenviando por POST lo que el
    // colegiado había escrito, para que no tenga que volver a cargar todo el formulario
?>
    <body onLoad="document.forms['myForm'].submit()">
        <form name="myForm" method="POST" action="actualizar_domicilio_particular.php?id=<?php echo $hashColegiado; ?>">
            <input type="hidden" name="mensaje" value="<?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="clase" value="alert alert-danger">
            <input type="hidden" name="calle" value="<?php echo htmlspecialchars($calle, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="numero" value="<?php echo htmlspecialchars($numero, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="piso" value="<?php echo htmlspecialchars($piso, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="departamento" value="<?php echo htmlspecialchars($departamento, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="laterales" value="<?php echo htmlspecialchars($laterales, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="idZona" value="<?php echo htmlspecialchars($idZona, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="idLocalidad" value="<?php echo htmlspecialchars($idLocalidad, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="localidadTexto" value="<?php echo htmlspecialchars($localidadTexto, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="codigoPostal" value="<?php echo htmlspecialchars($codigoPostal, ENT_QUOTES, 'UTF-8'); ?>">
        </form>
    </body>
<?php
} else {
?>
    <div class="row alert alert-danger">
        <div class="col-md-10">
            <h4 class=""><b><?php echo $mensaje; ?></b></h4>
        </div>
        <div class="col-md-2">
            <a href="tramites.php" class="btn btn-primary">Volver</a>
        </div>
    </div>
<?php
}
require_once "../html/menuTramitesClose.php";
include("../html/footer.php");
?>
</div>

</body>
