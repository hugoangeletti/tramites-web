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

    $telefonoFijo = isset($_POST['telefonoFijo']) ? trim($_POST['telefonoFijo']) : '';
    $telefonoMovil = isset($_POST['telefonoMovil']) ? trim($_POST['telefonoMovil']) : '';
    $correoElectronico = isset($_POST['correoElectronico']) ? trim($_POST['correoElectronico']) : '';

    if ($correoElectronico == '') {
        $continuar = FALSE;
        $mensaje .= 'Debe ingresar el correo electr&oacute;nico - ';
    } else if (!filter_var($correoElectronico, FILTER_VALIDATE_EMAIL)) {
        $continuar = FALSE;
        $mensaje .= 'El correo electr&oacute;nico ingresado no es v&aacute;lido - ';
    }
    if ($telefonoMovil == '') {
        $continuar = FALSE;
        $mensaje .= 'Debe ingresar el tel&eacute;fono m&oacute;vil - ';
    } else if (!validarCelularArgentino($telefonoMovil)) {
        $continuar = FALSE;
        $mensaje .= 'El tel&eacute;fono m&oacute;vil ingresado no es un n&uacute;mero argentino v&aacute;lido - ';
    }

    if ($continuar) {
        // Datos a enviar en formato array
        $data = array(
            "idColegiado" => $idColegiado,
            "telefonoFijo" => $telefonoFijo,
            "telefonoMovil" => $telefonoMovil,
            "correoElectronico" => $correoElectronico
        );

        // Convertir array a JSON
        $data_string = json_encode($data);

        // URL de la API REST donde se enviarán los datos
        if (ENV == "prod") {
            $url = 'https://webservices.colmed1.com.ar/colegio/ws-colmed/colegiado/actualizar_datos_contacto.php';
        } else {
            $url = 'http://www.colmed1.com/desarrollo/colegio/ws-colmed/colegiado/actualizar_datos_contacto.php';
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
            if ($respuesta['codigo'] == 1) {
                // ok, se actualizó correctamente
            } else {
                $continuar = FALSE;
                $mensaje .= $respuesta['mensaje'];
            }
        } else {
            $continuar = FALSE;
            $mensaje .= "No se pudieron actualizar los datos de contacto";
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
            <input type="hidden"  name="mensaje" id="mensaje" value="Los datos de contacto se actualizaron correctamente.">
            <input type="hidden"  name="clase" id="clase" value="alert alert-success">
        </form>
    </body>
<?php
} else if (isset($hashColegiado) && $hashColegiado <> "") {
    // volvemos a actualizar_contacto.php reenviando por POST lo que el colegiado
    // había escrito, para que no tenga que volver a cargar todo el formulario
?>
    <body onLoad="document.forms['myForm'].submit()">
        <form name="myForm" method="POST" action="actualizar_contacto.php?id=<?php echo $hashColegiado; ?>">
            <input type="hidden" name="mensaje" value="<?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="clase" value="alert alert-danger">
            <input type="hidden" name="telefonoFijo" value="<?php echo htmlspecialchars($telefonoFijo, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="telefonoMovil" value="<?php echo htmlspecialchars($telefonoMovil, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="correoElectronico" value="<?php echo htmlspecialchars($correoElectronico, ENT_QUOTES, 'UTF-8'); ?>">
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
