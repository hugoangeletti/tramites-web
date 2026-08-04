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
    $matricula = $_SESSION['matricula'];

    if (isset($_POST['idTipoCertificado']) && $_POST['idTipoCertificado'] <> "") {
        $idTipoCertificado = $_POST['idTipoCertificado'];
    } else {
        $continuar = FALSE;
        $mensaje .= "Debe ingresar el tipo de certificado";
    }
    if (isset($_POST['idEntidad']) && $_POST['idEntidad']) {
        $idSolicitudCertificadoWebEntidad = $_POST['idEntidad'];
        $presentado = NULL;
    } else {
        if (isset($_POST['presentado']) && $_POST['presentado']) {
            $presentado = $_POST['presentado'];
            $idSolicitudCertificadoWebEntidad = NULL;
        } else {
            $continuar = FALSE;
            $mensaje .= "Debe ingresar a que entidad se va a presentar el certificado";
        }
    }

    if ($continuar) {
        // Datos a enviar en formato array
        $data = array(
            "id" => $hashColegiado,
            'idTipoCertificado' => $idTipoCertificado,
            "idEntidad" => $idSolicitudCertificadoWebEntidad,
            "presentado" => $presentado
        );

        // Convertir array a JSON
        $data_string = json_encode($data);

        // URL de la API REST donde se enviarán los datos
        if (ENV == "prod") {
            $url = 'http://webservices.colmed1.com.ar/colegio/ws-colmed/certificado/genera_certificado.php';
        } else {
            $url = 'http://www.colmed1.com/desarrollo/colegio/ws-colmed/certificado/genera_certificado.php';
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
        $respuesta = (json_decode($result,true));
        if (isset($respuesta)) {
            if ($respuesta['codigo'] == '1') {
                $certificadoPdf = $respuesta['certificadoPDF'];
                $hashCertificado = $respuesta['hashCertificado'];
            } else {
                $certificadoPdf = NULL;
                $continuar = FALSE;
                $mensaje .= $respuesta['mensaje'];
            }
        } else {
            $certificadoPdf = NULL;
            $continuar = FALSE;
            $mensaje .= "No se pudo generar la solicitud del certificado";
        }
    }
}
if ($continuar) {
    if (isset($certificadoPdf) && $certificadoPdf <> "") {
    ?>
        <body onLoad="document.forms['myForm'].submit()">
            <form name="myForm"  method="POST" action="imprimirCertificado.php?id=<?php echo $hashCertificado; ?>">
            </form>
        </body>
    <?php
    } else {
    ?>
        <body onLoad="document.forms['myForm'].submit()">
            <form name="myForm"  method="POST" action="solicitar_certificado.php?id=<?php echo $hashColegiado; ?>">
                <input type="hidden" name="mensaje" id="mensaje" value="La solicitud se creó correctamente.">
                <input type="hidden" name="clase" id="clase" value="alert alert-success">
            </form>
        </body>
    <?php   
    }
} else {
?>
    <div class="row alert alert-danger">
        <div class="col-md-10">
            <h4 class=""><b><?php echo $mensaje; ?></b></h4>
        </div>
        <div class="col-md-2">
            <?php 
            if (isset($hashColegiado) && $hashColegiado <> "") {
            ?>
                <a href="solicitar_certificado.php?id=<?php echo $hashColegiado; ?>" class="btn btn-dark">Volver</a>
            <?php 
            } else {
            ?>
                <a href="tramites.php" class="btn btn-primary">Volver</a>
            <?php 
            }
            ?>
        </div>
    </div>
<?php
}
require_once "../html/menuTramitesClose.php";
include("../html/footer.php");
?>
</div>

</body>

<?php 
/*
//esto es para mostrar en el mismo php el certificado emitido
<!--
        <div class="row">
            <div class="col-md-5">
                <h5>Certificado</h5>
            </div>
            <div class="col-md-5">
                <h5><?php echo $_SESSION['apellidoNombre']; ?></h5>
                <h5>M.P. <?php echo $_SESSION['matricula']; ?></h5>
            </div>
            <div class="col-md-2">
                <a href="solicitar_certificado.php?id=<?php echo $hashColegiado; ?>" class="btn btn-dark">Volver</a>
            </div>
        </div>
    <div class="container-fluid p-3" >
       <embed src='data:application/pdf;base64,<?php echo $certificadoPdf; ?>' height="600px" width='100%' type='application/pdf'>   
    </div>
    --> 
*/