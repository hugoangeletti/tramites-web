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
    
    curl_setopt($ch, CURLOPT_URL, URL_WS.'/colegiado/buscar_cuotas_colegiacion.php?idColegiado='.$idColegiado);
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

                // Se refresca acá y no solo en tramites.php/pago_procesado.php: si el
                // colegiado entra directo a esta página, el dato en sesión puede estar
                // desactualizado (por ejemplo, si la intención se anuló desde otro lado)
                $intencionPagoPendiente = armarIntencionPagoPendiente($respuesta);
                if ($intencionPagoPendiente !== NULL) {
                    $_SESSION['intencionPagoPendiente'] = $intencionPagoPendiente;
                } else {
                    unset($_SESSION['intencionPagoPendiente']);
                }

                if ($respuesta['codigo'] == 1) {
                    $cuotas = $respuesta['datos'];
                    $estadoTesoreria = $respuesta['codigo'];
                } else {
                    if ($respuesta['codigo'] <> 2) {
                        $continuar = FALSE;
                    ?>
                        <h4 style="color: red;"><b>Error al buscar las cuotas de colegiación - <?php echo $respuesta['mensaje']; ?></b></h4>
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
    <?php if (isset($_POST['mensaje']) && $_POST['mensaje'] <> "OK") { ?>
        <div class="<?php echo $_POST['clase']; ?> alert-dismissible fade show mb-4" role="alert">
            <?php echo $_POST['mensaje']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php } ?>
    <?php if (isset($_SESSION['intencionPagoPendiente'])) {
        $intencionPendiente = $_SESSION['intencionPagoPendiente'];
        $cantCuotasPendientes = sizeof($intencionPendiente['cuotas']);
        // Si ya se envió a Gire, el pago espera la rendición diaria: no se puede
        // volver a pagar ni anular, solo se informa la situación
        $intencionEnviada = !empty($intencionPendiente['enviada']);
    ?>
        <div class="alert alert-warning mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <?php if ($intencionEnviada) { ?>
                        Tiene un pago de
                        <b>$<?php echo number_format($intencionPendiente['total'], 0, ',', '.'); ?></b>
                        (<?php echo $cantCuotasPendientes; ?> cuota<?php echo $cantCuotasPendientes == 1 ? '' : 's'; ?>)
                        <b>pendiente de acreditaci&oacute;n</b>. La confirmaci&oacute;n se realiza con la
                        rendici&oacute;n diaria de la cobranza, por lo que las cuotas pueden seguir figurando
                        como impagas hasta que se procese. No es necesario que vuelva a abonarlas.
                    <?php } else { ?>
                        Ya tiene una <b>intenci&oacute;n de pago pendiente</b> por
                        <b>$<?php echo number_format($intencionPendiente['total'], 0, ',', '.'); ?></b>
                        (<?php echo $cantCuotasPendientes; ?> cuota<?php echo $cantCuotasPendientes == 1 ? '' : 's'; ?>).
                        Puede completar el pago o anularla para seleccionar otras cuotas.
                    <?php } ?>
                </div>
                <?php if (!$intencionEnviada) { ?>
                    <div class="mt-2 mt-md-0">
                        <?php if (MOSTRAR_PAGO_EN_LINEA) { ?>
                            <form action="procesar_pago.php?id=<?php echo $hashColegiado; ?>" method="POST" class="d-inline">
                                <input type="hidden" name="hashIntencionPago" value="<?php echo htmlspecialchars($intencionPendiente['hash'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="totalActualizado" value="<?php echo htmlspecialchars($intencionPendiente['total'], ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="btn btn-success btn-sm">Pagar</button>
                            </form>
                        <?php } ?>
                        <form action="anular_intencion_pago.php?id=<?php echo $hashColegiado; ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Confirma que desea anular la intención de pago pendiente?');">
                            <input type="hidden" name="hashIntencionPago" value="<?php echo htmlspecialchars($intencionPendiente['hash'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Anular</button>
                        </form>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
    <div class="card drive-card drive-banner mb-4">
        <div class="card-body">
        <?php
        if (sizeof($cuotas) >= 0) {
            $totalPeriodoActual = 0;
            $totalPeriodoActualActualizado = 0;
            $totalAnteriores = 0;
            $totalAnterioresActualizado = 0;
            $cuotasPeriodoActual = 0;
            foreach ($cuotas as $cuota) {
                if ($cuota['periodo'] == PERIODO_ACTUAL) {
                    $totalPeriodoActual += $cuota['importeUno'];
                    $totalPeriodoActualActualizado += $cuota['importeActualizado'];
                    $cuotasPeriodoActual++;
                } else {
                    $totalAnteriores += $cuota['importeUno'];
                    $totalAnterioresActualizado += $cuota['importeActualizado'];
                }
            }
            $hayIntencionPendiente = isset($_SESSION['intencionPagoPendiente']);
            ?>
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h4 class="mb-0">Cuotas de colegiación</h4>
                <?php if ($hayIntencionPendiente) { ?>
                    <span class="text-muted mr-2">Complete o anule el pago pendiente para seleccionar nuevas cuotas.</span>
                <?php } else { ?>
                    <div class="d-flex align-items-center flex-wrap">
                        <span id="leyendaSeleccion" class="text-muted mr-2">Seleccione cuotas a abonar</span>
                        <span id="totalSeleccionadoTexto" class="mr-2" style="display:none;">
                            <b>Total a pagar:</b> $<span id="totalActualizadoTexto">0</span>
                        </span>
                        <button type="submit" form="formCuotas" id="btnSiguiente" class="btn btn-success btn-sm" style="display:none;">Siguiente</button>
                    </div>
                <?php } ?>
            </div>
            <?php
            if ($totalPeriodoActualActualizado > 0 || $totalAnterioresActualizado > 0) {
            ?>
                <form id="formCuotas" action="metodo_pago.php?id=<?php echo $hashColegiado; ?>" method="POST">
                    <input type="hidden" id="totalActualizado" name="totalActualizado" value="<?php echo $totalAnterioresActualizado; ?>">
                    <input type="hidden" id="tieneDeudaAnterior" name="tieneDeudaAnterior" value="<?php echo ($totalAnterioresActualizado > 0) ? '1' : '0'; ?>">

                    <div class="table-responsive">
                    <table id="cuotas" class="table">
                        <thead>
                            <tr>
                                <th style="text-align: center; width: 80px;">
                                    <input type="checkbox" id="seleccionarTodos" onclick="seleccionarTodo(this)" <?php echo $hayIntencionPendiente ? 'disabled' : ''; ?>>
                                    <br><small>Todos</small>
                                </th>
                                <th style="text-align: center;">Período-Cuota</th>
                                <th style="text-align: right;">Importe original</th>
                                <th style="text-align: right;">Importe actualizado</th>
                                <th style="text-align: center;">Vencimiento Original</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cuotasEnIntencionPago = isset($_SESSION['intencionPagoPendiente']['cuotas']) ? $_SESSION['intencionPagoPendiente']['cuotas'] : array();
                            foreach ($cuotas as $cuota) {
                                $esAnterior = ($cuota['periodo'] <> PERIODO_ACTUAL);
                                $enIntencionPago = in_array($cuota['idColegiadoDeudaAnualCuota'], $cuotasEnIntencionPago);
                                $checkboxDisabled = $esAnterior || $hayIntencionPendiente;
                                $tituloCheckbox = $esAnterior
                                    ? 'Cuota vencida, debe abonarse'
                                    : ($hayIntencionPendiente ? 'Ya tiene una intención de pago pendiente. Debe abonarla o anularla primero.' : '');
                            ?>
                            <tr>
                                <td style="text-align: center;">
                                    <input type="checkbox"
                                           name="cuotas_seleccionadas[]"
                                           value="<?php echo $cuota['idColegiadoDeudaAnualCuota']; ?>"
                                           id="check_<?php echo $cuota['idColegiadoDeudaAnualCuota']; ?>"
                                           data-importe="<?php echo $cuota['importeActualizado']; ?>"
                                           data-periodo="<?php echo $esAnterior ? 'anterior' : 'actual'; ?>"
                                           onclick="cambiaTotalCuotas(this.dataset.importe, this.id)"
                                           <?php echo $esAnterior ? 'checked' : ''; ?>
                                           <?php echo $checkboxDisabled ? 'disabled' : ''; ?>
                                           <?php echo $tituloCheckbox ? 'title="'.htmlspecialchars($tituloCheckbox, ENT_QUOTES, 'UTF-8').'"' : ''; ?>>
                                    <?php if ($esAnterior) { ?>
                                        <input type="hidden" name="cuotas_seleccionadas[]" value="<?php echo $cuota['idColegiadoDeudaAnualCuota']; ?>">
                                    <?php } ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php echo $cuota['periodo'].'-'.$cuota['cuota']; ?>
                                    <?php if ($esAnterior) { ?>
                                        <br><span class="badge badge-danger">Vencida</span>
                                    <?php } ?>
                                    <?php if ($enIntencionPago) { ?>
                                        <br><span class="badge badge-warning" title="Ya tiene una intención de pago pendiente por esta cuota">Pago pendiente de confirmación</span>
                                    <?php } ?>
                                </td>
                                <td style="text-align: right;"><?php echo number_format($cuota['importeUno'], 0, ',', '.'); ?></td>
                                <td style="text-align: right;"><?php echo number_format($cuota['importeActualizado'], 0, ',', '.'); ?></td>
                                <td style="text-align: center;"><?php echo cambiarFechaFormatoParaMostrar($cuota['vencimiento']); ?></td>
                            </tr>
                            <?php } ?>
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
    // Marca si entre las cuotas seleccionadas hay alguna de un período anterior (deuda vencida)
    function actualizarFlagDeudaAnterior() {
        let hayDeudaAnterior = $('#cuotas tbody input[type="checkbox"]:checked[data-periodo="anterior"]').length > 0;
        $('#tieneDeudaAnterior').val(hayDeudaAnterior ? '1' : '0');
    }

    // Actualiza el input oculto que se envía en el form, el texto del total
    // visible junto al botón de pago, y alterna la leyenda "Seleccione cuotas a abonar"
    function actualizarVistaTotal(total) {
        $('#totalActualizado').val(total);
        $('#totalActualizadoTexto').text(Number(total).toLocaleString('es-AR'));
        actualizarFlagDeudaAnterior();
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

    // Al cargar, las cuotas de períodos anteriores ya vienen marcadas: refleja
    // ese total inicial en la leyenda/botón sin esperar a que el usuario haga click
    $(function() {
        let totalInicial = parseInt($('#totalActualizado').val()) || 0;
        actualizarVistaTotal(totalInicial);
    });

    function cambiaTotalCuotas(importe, idCheckbox) {
        let total = parseInt($('#totalActualizado').val()) || 0;
        let monto = parseInt(importe) || 0;

        // Si el checkbox está marcado sumamos, si no restamos
        if ($('#' + idCheckbox).is(':checked')) {
            total += monto;
        } else {
            total -= monto;
        }

        actualizarVistaTotal(total);

        return total;
    }

    /*
    function seleccionarTodo(source) {
    // Buscamos todos los checkboxes dentro del cuerpo de la tabla
    const checkboxes = document.querySelectorAll('#cuotas tbody input[type="checkbox"]');
    
    // Ponemos el total a 0 antes de recalcular para evitar duplicados
    $('#totalActualizado').val(0);
    
    checkboxes.forEach(function(checkbox) {
        // Marcamos o desmarcamos según el checkbox "maestro"
        checkbox.checked = source.checked;
        
        // Llamamos a la función que ya teníamos para que sume los importes
        // Usamos una versión modificada o simplemente llamamos a cambiaTotalCuotas manualmente
        if (source.checked) {
            actualizarTotalMasivo();
        } else {
            $('#totalActualizado').val(0);
            $('#bloque_confirmar').hide();
        }
    });
    }


// Función auxiliar para recalcular todo de una vez
function actualizarTotalMasivo() {
    let nuevoTotal = 0;
    $('#cuotas tbody input[type="checkbox"]:checked').each(function() {
        // Obtenemos el importe desde el atributo que le pasamos en el onclick original
        // Para que esto funcione mejor, es recomendable guardar el importe en un data-attribute
        let row = $(this).closest('tr');
        let importe = parseFloat(row.find('td:eq(4)').text().replace('$', '').replace('.', '').replace(',', '.')) || 0;
        nuevoTotal += importe;
    });

    $('#totalActualizado').val(nuevoTotal.toFixed(2));
    if (nuevoTotal > 0) {
        $('#bloque_confirmar').show();
    } else {
        $('#bloque_confirmar').hide();
    }
}
*/
    function seleccionarTodo(source) {
    // 1. Seleccionamos los checkboxes de las cuotas, excepto las vencidas
    // (esas ya vienen marcadas y deshabilitadas, no se pueden desmarcar)
    const checkboxes = $('#cuotas tbody input[type="checkbox"]:not([data-periodo="anterior"])');

    // 2. Los marcamos o desmarcamos todos según el "maestro"
    checkboxes.prop('checked', source.checked);

    // 3. Recalculamos el total (las cuotas vencidas siguen marcadas y suman igual)
    actualizarTotalMasivo();
}

function actualizarTotalMasivo() {
    let nuevoTotal = 0;

    // Recorremos solo los que están marcados para sumar
    $('#cuotas tbody input[type="checkbox"]:checked').each(function() {
        // Usamos el data-importe que agregamos en el PHP
        let importe = parseFloat($(this).data('importe')) || 0;
        nuevoTotal += importe;
    });

    // Redondear y mostrar
    nuevoTotal = Math.round(nuevoTotal * 100) / 100;
    actualizarVistaTotal(nuevoTotal);

    console.log("Total masivo calculado: " + nuevoTotal);
}

</script>
</body>
