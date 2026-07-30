<?php
require_once '../dataAccess/config.php';
permisoLogueado();
require_once '../html/head.php';
require_once '../html/header.php';
require_once '../dataAccess/funcionesPhp.php';

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
        curl_setopt($ch, CURLOPT_URL, 'https://webservices.colmed1.com.ar/colegio/ws-colmed/colegiado/buscar_cuotas_colegiacion.php?idColegiado='.$idColegiado);
    } else {
        curl_setopt($ch, CURLOPT_URL, 'https://www.colmed1.com/desarrollo/colegio/ws-colmed/colegiado/buscar_cuotas_colegiacion.php?idColegiado='.$idColegiado);
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
                    $estadoTesoreria = $respuesta['codigo'];
                } else {
                    if ($respuesta['codigo'] <> 2) {
                        $continuar = FALSE;
                    ?>
                        <h4 style="color: red;"<b>Error al buscar las cuotas de colegiación - <?php echo $respuesta['mensaje']; ?></b></h4>
                    <?php
                    } else {
                        $estadoTesoreria = $respuesta['codigo'];
                        $mensaje = $respuesta['mensaje'];
                        $cuotas = array();                        
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
if ($continuar && isset($estadoTesoreria)) {
    if (substr($estadoTesoreria, 0, 6) <> 'Deudor') {
        $colorEstadoTesoreria = "green";
    } else {
        $colorEstadoTesoreria = "red"; //#dca7a7";
    }
    //#0B83B2
    ?>
    <div class="col-md12"><h6>Tesorería -> Cuotas de colegiación</h6></div>
    <div class="container-fluid p-3" style="background-color: #8699a4 ; color: white">
        <div class="row">
            <div class="col-md-4">
                <h5><?php echo $_SESSION['apellidoNombre']; ?></h5>
                <h5>M.P. <?php echo $_SESSION['matricula']; ?></h5>
            </div>
            <div class="col-md-4">
            </div>
            <div class="col-md-4 text-right">
                <a href="tramites.php" class="btn btn-dark">Volver</a>
            </div>
        </div>
    </div>
    <div class="container-fluid p-3" >
        <?php
        if (sizeof($cuotas) >= 0) {
            $periodoActual = intval(date('Y'));
            $mes = intval(date('m'));
            if ($mes >= 1 && $mes <= 6) {
                $periodoActual -= 1;
            }

            $totalPeriodoActual = 0;
            $totalPeriodoActualActualizado = 0;
            $totalAnteriores = 0;
            $totalAnterioresActualizado = 0;
            $cuotasPeriodoActual = 0;
            foreach ($cuotas as $cuota) {
                if ($cuota['periodo'] == $periodoActual) {
                    $totalPeriodoActual += $cuota['importeUno'];
                    $totalPeriodoActualActualizado += $cuota['importeActualizado'];
                    $cuotasPeriodoActual++;
                } else {
                    $totalAnteriores += $cuota['importeUno'];
                    $totalAnterioresActualizado += $cuota['importeActualizado'];
                }
            }
            ?>
            <div class="row alert alert-info">
            <?php
            if ($totalPeriodoActualActualizado > 0 || $totalAnterioresActualizado > 0) {
                if ($totalPeriodoActualActualizado > 0) {
                    if ($periodoActual == 2024) {
                        //el periodo 2024 se imprimen de a 3 cuotas a pedido de mesa directiva 1/7/2024
                        $cuotasTotales = $cuotasPeriodoActual.' de 12 cuotas.<br>';
                    } else {
                        $cuotasTotales = '';
                    }

                ?>
                    <div class="col-md-2">
                        <b>Per&iacute;odo actual</b> (<?php echo $periodoActual; ?>)
                        <br>
                        <b><?php echo $cuotasTotales; ?></b>
                        <b>Total:</b> $<?php echo $totalPeriodoActualActualizado; ?>
                    </div>
                    <div class="col-md-1">
                        <a href="imprimirChequera.php?id=<?php echo $hashColegiado; ?>" class="btn btn-dark">Imprimir chequera</a>
                    </div>
                <?php                
                }
                if ($totalAnterioresActualizado > 0) {
                ?>
                    <div class="col-md-1">
                        &nbsp;
                    </div>
                    <div class="col-md-2">
                        <b>Per&iacute;odos anteriores</b>
                        <br>
                        <b>Total:</b> $<?php echo $totalAnterioresActualizado; ?>
                    </div>
                    <div class="col-md-2">
                        <a href="imprimirNotaDeuda.php?id=<?php echo $hashColegiado; ?>" class="btn btn-dark">Imprimir deuda anterior</a>
                    </div>
                <?php                
                }
                ?>
                </div>
                <!--<h5>Cuotas a abonar</h5>-->
                <table id="cuotas" class="table">
                    <thead>
                          <tr>
                              <th style="display: none;">Recibo.</th>
                              <th style="text-align: center;">Período-Cuota</th>
                              <th style="text-align: right;">Importe original</th>
                              <th style="text-align: right;">Importe actualizado</th>
                              <th style="text-align: center;">Vencimiento Original</th>
                          </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach ($cuotas as $cuota) {
                              ?>
                        <tr>
                            <td style="display: none;"><?php echo $cuota['idColegiadoDeudaAnualCuota']; ?></td>
                            <td style="text-align: center;"><?php echo $cuota['periodo'].'-'.$cuota['cuota']; ?></td>
                            <td style="text-align: right;"><?php echo number_format($cuota['importeUno'], 2, ',', '.'); ?></td>
                            <td style="text-align: right;"><?php echo number_format($cuota['importeActualizado'], 2, ',', '.'); ?></td>
                            <td style="text-align: center;"><?php echo cambiarFechaFormatoParaMostrar($cuota['vencimiento']); ?></td>
                        </tr>
                              <?php
                            }
                        ?>
                    </tbody>
                </table>
            <?php
            } else {
            ?>
                <h3>No tiene cuotas pendiente de pago</h3>
            <?php                
            }
            ?>
        <?php
        }
        ?>
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
include("../html/footer.php");
?>
  </div>

</body>
