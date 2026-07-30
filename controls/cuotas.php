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
            if ($totalPeriodoActualActualizado > 0 || $totalAnterioresActualizado > 0) {
            ?>
                <form action="generar_intencion_pago.php?id=<?php echo $hashColegiado; ?>" method="POST">
                    <div class="row alert alert-info">
                        <?php
                        $col_boton_gire = 0;
                        if ($totalPeriodoActualActualizado > 0) {
                            $cuotasTotales = IMPRIMIR_TRIMESTRAL ? $cuotasPeriodoActual.' de 12 cuotas.<br>' : '';
                        ?>
                            <div class="col-md-2">
                                <b>Per&iacute;odo actual</b> (<?php echo PERIODO_ACTUAL; ?>)
                                <br>
                                <b><?php echo $cuotasTotales; ?></b>
                                <b>Total:</b> $<?php echo number_format($totalPeriodoActualActualizado, 2, ',', '.'); ?>
                            </div>
                            <div class="col-md-2">
                                <a href="imprimirChequera.php?id=<?php echo $hashColegiado; ?>" class="btn btn-dark btn-sm">Imprimir chequera</a>
                            </div>
                        <?php     
                            $col_boton_gire += 4;           
                        }
                        if ($totalAnterioresActualizado > 0) {
                        ?>
                            <div class="col-md-2">
                                <b>Per&iacute;odos anteriores</b>
                                <br>
                                <b>Total:</b> $<?php echo number_format($totalAnterioresActualizado, 2, ',', '.'); ?>
                            </div>
                            <div class="col-md-2">
                                <a href="imprimirNotaDeuda.php?id=<?php echo $hashColegiado; ?>" class="btn btn-dark btn-sm">Imprimir deuda anterior</a>
                            </div>
                        <?php                
                            $col_boton_gire += 4;
                        }
                        $col_boton_gire = 12 - $col_boton_gire;
                        ?>

                        <!-- ID ÚNICO: bloque_confirmar_top -->
                        <div class="col-md-<?php echo $col_boton_gire; ?>" id="bloque_confirmar" style="display:none; border-left: 1px solid #ccc;">
                            <div class="row">
                                <div class="col-md-7 text-right">
                                    <label>Total seleccionado:</label>
                                    <input type="text" id="totalActualizado" name="totalActualizado" value="0" class="form-control text-right" readonly>
                                </div>
                                <div class="col-md-5">
                                    <br>
                                    <button type="submit" class="btn btn-success">Pagar ahora</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table id="cuotas" class="table">
                        <thead>
                            <tr>
                                <th style="text-align: center; width: 80px;">
                                    <input type="checkbox" id="seleccionarTodos" onclick="seleccionarTodo(this)">
                                    <br><small>Todos</small>
                                </th>
                                <th style="text-align: center;">Período-Cuota</th>
                                <th style="text-align: right;">Importe original</th>
                                <th style="text-align: right;">Importe actualizado</th>
                                <th style="text-align: center;">Vencimiento Original</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cuotas as $cuota) { ?>
                            <tr>
                                <td style="text-align: center;">
                                    <input type="checkbox" 
                                           name="cuotas_seleccionadas[]" 
                                           value="<?php echo $cuota['idColegiadoDeudaAnualCuota']; ?>"
                                           id="check_<?php echo $cuota['idColegiadoDeudaAnualCuota']; ?>" 
                                           data-importe="<?php echo $cuota['importeActualizado']; ?>"
                                           onclick="cambiaTotalCuotas(this.dataset.importe, this.id)">
                                </td>
                                <td style="text-align: center;"><?php echo $cuota['periodo'].'-'.$cuota['cuota']; ?></td>
                                <td style="text-align: right;"><?php echo number_format($cuota['importeUno'], 2, ',', '.'); ?></td>
                                <td style="text-align: right;"><?php echo number_format($cuota['importeActualizado'], 2, ',', '.'); ?></td>
                                <td style="text-align: center;"><?php echo cambiarFechaFormatoParaMostrar($cuota['vencimiento']); ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
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
<script>
    function cambiaTotalCuotas(importe, idCheckbox) {
        let total = parseInt($('#totalActualizado').val()) || 0;
        let monto = parseInt(importe) || 0;

        // Si el checkbox está marcado sumamos, si no restamos
        if ($('#' + idCheckbox).is(':checked')) {
            total += monto;
        } else {
            total -= monto;
        }

        // ACTUALIZAMOS EL TOTAL EN EL INPUT OCULTO
        $('#totalActualizado').val(total);

        // LÓGICA DE APARICIÓN:
        /*
        if (total > 0) {
            $('#bloque_confirmar').fadeIn(); // Esto quita el display:none
            $('#bloque_forma_pago').fadeIn(); 
        } else {
            $('#bloque_confirmar').fadeOut(); // Esto vuelve a poner display:none
            $('#bloque_forma_pago').fadeOut();
        }
        */
        if (total > 0) {
            $('#bloque_confirmar').show(); // o .css('display', 'block')
            $('#bloque_total').show(); // o .css('display', 'block')
        } else {
            $('#bloque_confirmar').hide(); // o .css('display', 'none')
            $('#bloque_total').hide(); // o .css('display', 'none')
        }

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
    // 1. Seleccionamos todos los checkboxes de las cuotas
    const checkboxes = $('#cuotas tbody input[type="checkbox"]');
    
    // 2. Los marcamos o desmarcamos todos según el "maestro"
    checkboxes.prop('checked', source.checked);

    // 3. Calculamos el total una sola vez (fuera del bucle)
    if (source.checked) {
        actualizarTotalMasivo();
    } else {
        // Si desmarcamos todo, reseteamos a cero directamente
        $('#totalActualizado').val(0);
        $('#bloque_confirmar').hide(); // Asegúrate que el ID sea el correcto
        console.log("Se desmarcaron todas las cuotas.");
    }
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
    $('#totalActualizado').val(nuevoTotal);
    
    console.log("Total masivo calculado: " + nuevoTotal);

    // Mostrar el botón de pago
    if (nuevoTotal > 0) {
        $('#bloque_confirmar').show();
    }
}

</script>
</body>
