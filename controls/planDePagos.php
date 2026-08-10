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
    
    curl_setopt($ch, CURLOPT_URL, URL_WS.'/colegiado/buscar_cuotas_plan_pago.php?idColegiado='.$idColegiado);
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
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h4 class="mb-0">Plan de pagos</h4>
                <div class="d-flex align-items-center flex-wrap">
                    <span id="leyendaSeleccion" class="text-muted mr-2">Seleccione cuotas a abonar</span>
                    <span id="totalSeleccionadoTexto" class="mr-2" style="display:none;">
                        <b>Total a pagar:</b> $<span id="totalActualizadoTexto">0</span>
                    </span>
                    <button type="submit" form="formCuotas" id="btnSiguiente" class="btn btn-success btn-sm" style="display:none;">Siguiente</button>
                </div>
            </div>
        <?php
        if (sizeof($cuotas) > 0) {
            ?>
            <form id="formCuotas" action="metodo_pago.php?id=<?php echo $hashColegiado; ?>&origen=plan" method="POST">
                <input type="hidden" id="totalActualizado" name="totalActualizado" value="0">
                <div class="table-responsive">
                <table id="cuotas" class="table">
                    <thead>
                          <tr>
                              <th style="text-align: center; width: 80px;">
                                  <input type="checkbox" id="seleccionarTodos" onclick="seleccionarTodo(this)">
                                  <br><small>Todos</small>
                              </th>
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
                                <td style="text-align: center;">
                                    <input type="checkbox"
                                           name="cuotas_seleccionadas[]"
                                           value="<?php echo $cuota['idPlanPagoCuota']; ?>"
                                           id="check_<?php echo $cuota['idPlanPagoCuota']; ?>"
                                           data-importe="<?php echo $cuota['importeActualizado']; ?>"
                                           onclick="cambiaTotalCuotas(this.dataset.importe, this.id)">
                                </td>
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
            </form>
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
<script>
    function actualizarVistaTotal(total) {
        $('#totalActualizado').val(total);
        $('#totalActualizadoTexto').text(Number(total).toLocaleString('es-AR'));
        if (total > 0) {
            $('#leyendaSeleccion').hide();
            $('#totalSeleccionadoTexto').show();
            $('#btnSiguiente').show();
        } else {
            $('#leyendaSeleccion').show();
            $('#totalSeleccionadoTexto').hide();
            $('#btnSiguiente').hide();
        }
    }

    function cambiaTotalCuotas(importe, idCheckbox) {
        let total = parseInt($('#totalActualizado').val()) || 0;
        let monto = parseInt(importe) || 0;

        if ($('#' + idCheckbox).is(':checked')) {
            total += monto;
        } else {
            total -= monto;
        }

        actualizarVistaTotal(total);

        return total;
    }

    function seleccionarTodo(source) {
        const checkboxes = $('#cuotas tbody input[type="checkbox"]');
        checkboxes.prop('checked', source.checked);
        actualizarTotalMasivo();
    }

    function actualizarTotalMasivo() {
        let nuevoTotal = 0;

        $('#cuotas tbody input[type="checkbox"]:checked').each(function() {
            let importe = parseFloat($(this).data('importe')) || 0;
            nuevoTotal += importe;
        });

        nuevoTotal = Math.round(nuevoTotal * 100) / 100;
        actualizarVistaTotal(nuevoTotal);
    }
</script>
</body>
