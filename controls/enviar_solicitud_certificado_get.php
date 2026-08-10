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

    if (isset($_POST['idEntidad']) && $_POST['idEntidad']) {
        $idSolicitudCertificadoWebEntidad = $_POST['idEntidad'];
        $laEntidad = "&idEntidad=".$idSolicitudCertificadoWebEntidad;
    } else {
        if (isset($_POST['presentado']) && $_POST['presentado']) {
            $presentado = $_POST['presentado'];
            $laEntidad = '&entidad='.$presentado;
        } else {
            $continuar = FALSE;
            $mensaje .= "Debe ingresar a que entidad se va a presentar el certificado";
        }
    }

    if ($continuar) {
        //obtener los datos del colegiDO
        ini_set('xdebug.var_display_max_depth', -1);
        ini_set('xdebug.var_display_max_children', -1);
        ini_set('xdebug.var_display_max_data', -1);
        set_time_limit(0);

        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, URL_WS.'/certificado/genera_certificado.php?id='.$hashColegiado.'&idTipoCertificado=6'.$laEntidad);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        //curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);

        $headers = array();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $respuesta = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);

        if ($err) {
            $continuar = FALSE;
            //echo "cURL Error #:" . $err;
        ?>
            <div class="row alert alert-danger">
                <div class="col-md-12 text-left">Disculpe las molestias. Momentaneamente fuera de servicio, intente m&aacute;s tarde</div>
            </div>
        <?php
        } else {
          switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
            case 200:  
                $rta=(json_decode($respuesta,true));
                //var_dump($rta);
                if (isset($rta) && isset($rta['respuesta'])) {
                    $respuesta = $rta['respuesta'];
                    if ($respuesta['codigo'] == 1) {
                        $datos = $respuesta['datos'];
                        $certificadoPdf = $datos['certificadoPDF'];
                    } else {
                        $continuar = FALSE;
                        $mensaje = $respuesta['mensaje'];
                    }
                } else {
                    $continuar = FALSE;
                    ?>
                    <br>
                    <div class="row">
                        <div class="col-md-4">
                            <h5>Momentaneamente fuera de servicio, vuelva a intentar más tarde</a>
                        </div>
                        <div class="col-md-4">
                            <a href="login.php" class="btn btn-info" role="button">Volver</a>
                        </div>
                    </div>
                <?php                
                }
                break;

            case 400:  
                $rta=(json_decode($respuesta,true));
                var_dump($rta);
                $continuar = FALSE;
                break;

            default:
                echo 'Codigo HTTP inesperado: '.$http_code."<br>";
                $continuar = FALSE;
                break;
            }
        }
    } else {
        $continuar = FALSE;
    }
}
if ($continuar) {
?>
        <div class="card drive-card drive-banner mb-4">
        <div class="card-body">
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
       <embed src='data:application/pdf;base64,<?php echo $certificadoPdf; ?>' height="600px" width='100%' type='application/pdf'>
        </div>
        </div>
<?php
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
