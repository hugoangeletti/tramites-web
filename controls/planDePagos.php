<?php
require_once '../dataAccess/config.php';
permisoLogueado();
require_once '../html/head.php';
require_once '../html/header.php';
require_once '../dataAccess/funcionesPhp.php';
require_once '../html/menuTramites.php';

$continuar = true;
if (isset($_GET['id']) && $_GET['id'] == $_SESSION['hashColegiado']) {
    $idColegiado = $_SESSION['idColegiado'];
    $hashColegiado = $_SESSION['hashColegiado'];
    $matricula = $_SESSION['matricula'];

    //obtener los datos del colegiDO
    ini_set('xdebug.var_display_max_depth', -1);
    ini_set('xdebug.var_display_max_children', -1);
    ini_set('xdebug.var_display_max_data', -1);
    set_time_limit(0);

    $ch = curl_init();
    
    if (ENV == "prod") {
        curl_setopt($ch, CURLOPT_URL, 'https://webservices.colmed1.com.ar/colegio/ws-colmed/colegiado/buscar_cuotas_plan_pago.php?idColegiado='.$idColegiado);
    } else {
        curl_setopt($ch, CURLOPT_URL, 'http://www.colmed1.com/desarrollo/colegio/ws-colmed/colegiado/buscar_cuotas_plan_pago.php?idColegiado='.$idColegiado);
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
        $continuar = FALSE;
    ?>
        <div class="row alert alert-danger">
            <div class="col-md-12 text-left">Disculpe las molestias. Momentaneamente fuera de servicio, intente m&aacute;s tarde</div>
        </div>
    <?php
    //echo "cURL Error #:" . $err;
    } else {
      switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
        case 200:  
            $rta=(json_decode($respuesta,true));
            //var_dump($rta);
            if (isset($rta) && isset($rta['respuesta'])) {
                $respuesta = $rta['respuesta'];
                if ($respuesta['codigo'] == 1) {
                    $cuotas = $respuesta['datos'];
                    $estadoTesoreria = $respuesta['estado_tesoreria'];
                } else {
                    if ($respuesta['codigo'] <> 2) {
                        $continuar = FALSE;
                    ?>
                        <h4 style="color: red;"><b>Error al buscar las cuotas de plan de pagos - <?php echo $respuesta['mensaje']; ?></b></h4>
                    <?php
                    }
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
if ($continuar) {
    if (substr($estadoTesoreria, 0, 6) <> 'Deudor') {
        $colorEstadoTesoreria = "green";
    } else {
        $colorEstadoTesoreria = "red"; //#dca7a7";
    }
    //#0B83B2
    ?>
    <div class="card drive-card drive-banner mb-4">
        <div class="card-body">
            <h6>Tesorería -> Cuotas de colegiación</h6>
        <?php
        if (sizeof($cuotas) > 0) {
            ?>
            <div class="row">
                <div class="col-md-2">
                    <a href="imprimirChequeraPlanPago.php?id=<?php echo $hashColegiado; ?>" class="btn btn-dark">Imprimir cuotas</a>
                </div>
            </div>
            <h5>Cuotas a abonar</h5>
            <div class="table-responsive">
            <table id="cuotas" class="table">
                <thead>
                      <tr>
                          <th>Recibo</th>
                          <th style="text-align: center;">Cuota</th>
                          <th style="text-align: right;">Importe original</th>
                          <th style="text-align: right;">Importe actualizado</th>
                          <th style="text-align: center;">Vencimiento Original</th>
                      </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($cuotas as $cuota) {
                        if ((isset($cuota['fechaPago']) && $cuota['fechaPago'] <> "0000-00-00") || ($cuota['estado'] <> 1)) { continue; }
                        ?>
                        <tr>
                            <td><?php echo $cuota['idPlanPagoCuota']; ?></td>
                            <td style="text-align: center;"><?php echo $cuota['cuota']; ?></td>
                            <td style="text-align: right;"><?php echo number_format($cuota['importe'], 0, ',', '.'); ?></td>
                            <td style="text-align: right;"><?php echo number_format($cuota['importeActualizado'], 0, ',', '.'); ?></td>
                            <td style="text-align: center;"><?php echo cambiarFechaFormatoParaMostrar($cuota['vencimiento']); ?></td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
            </div>
        <?php
        } else {
        ?>
            <h3>No tiene cuotas pendiente de pago</h3>
        <?php
        }
        ?>
    </div>
    </div>
<?php
} else {
?>
    <div class="col-md-12">
        <h2 class="alert alert-danger">ERROR AL INGRESAR</h2>
    </div>
    <a href="tramites.php" class="btn btn-primary">Volver</a>
<?php
}
require_once "../html/menuTramitesClose.php";
include("../html/footer.php");
?>
  </div>

</body>
