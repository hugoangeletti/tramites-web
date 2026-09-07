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

    $r = llamarWs(URL_WS.'/colegiado/buscar_cuotas_plan_pago.php?idColegiado='.$idColegiado);

    if ($r['error']) {
        $continuar = FALSE;
        ?>
        <div class="row alert alert-danger">
            <div class="col-md-12 text-left">Disculpe las molestias. Momentaneamente fuera de servicio, intente m&aacute;s tarde</div>
        </div>
        <?php
    } else if ($r['httpCode'] <> 200) {
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
    } else {
        // Se refresca acá y no solo en metodo_pago.php/pago_procesado.php: si el
        // colegiado entra directo a esta página, el dato en sesión puede estar
        // desactualizado (por ejemplo, si la intención se anuló desde otro lado).
        // El WS devuelve TODAS las intenciones vigentes del colegiado (colegiación
        // y plan de pagos comparten el mismo pool de deuda), así que acá hay que
        // quedarse solo con las de concepto "plan_pago".
        $intencionesRaw = (isset($r['nodo']['intencion_pago']) && is_array($r['nodo']['intencion_pago']))
            ? $r['nodo']['intencion_pago']
            : array();

        $intencionActiva = NULL; // la única iniciada/enviada de plan_pago, si la hay
        $intencionesAprobadas = array();
        $cuotasYaCubiertas = array();

        foreach ($intencionesRaw as $itemIntencion) {
            $conceptoItem = isset($itemIntencion['Concepto']) ? $itemIntencion['Concepto'] : '';
            if ($conceptoItem <> 'plan_pago') { continue; }

            $estadoItem = isset($itemIntencion['Estado']) ? strtolower(trim($itemIntencion['Estado'])) : '';
            $hashItem = isset($itemIntencion['Hash']) ? $itemIntencion['Hash'] : '';
            $fechaInicioItem = isset($itemIntencion['FechaInicio']) ? $itemIntencion['FechaInicio'] : '';
            $cuotasItem = (isset($itemIntencion['Cuotas']) && $itemIntencion['Cuotas'] <> '')
                ? array_map('trim', explode(',', $itemIntencion['Cuotas']))
                : array();

            if ($estadoItem == 'iniciada' || $estadoItem == 'enviada') {
                // Si quedó así hace más de 5 minutos sin resultado de Gire, se anula
                // sola para no dejar la deuda trabada indefinidamente
                if ($fechaInicioItem <> '' && (time() - strtotime($fechaInicioItem)) > 300 && anularIntencionPagoWs($hashItem)) {
                    continue;
                }
                $intencionActiva = array(
                    'hash'        => $hashItem,
                    'total'       => isset($itemIntencion['TotalPago']) ? $itemIntencion['TotalPago'] : 0,
                    'cuotas'      => $cuotasItem,
                    'estado'      => $estadoItem,
                    'fechaInicio' => $fechaInicioItem,
                );
                $cuotasYaCubiertas = array_merge($cuotasYaCubiertas, $cuotasItem);
            } else if ($estadoItem == 'aprobada') {
                $intencionesAprobadas[] = array(
                    'hash'   => $hashItem,
                    'total'  => isset($itemIntencion['TotalPago']) ? $itemIntencion['TotalPago'] : 0,
                    'cuotas' => $cuotasItem,
                );
                $cuotasYaCubiertas = array_merge($cuotasYaCubiertas, $cuotasItem);
            }
        }

        if ($intencionActiva !== NULL) {
            $_SESSION['intencionPagoPendiente'] = $intencionActiva;
        } else {
            unset($_SESSION['intencionPagoPendiente']);
        }

        if ($r['codigo'] == 1) {
            // El WS trae todas las cuotas del plan (pagas e impagas); acá solo
            // interesan las pendientes: estado == 1 y sin fecha de pago cargada
            $cuotas = array();
            foreach ($r['datos'] as $cuota) {
                $tieneFechaPago = isset($cuota['fechaPago']) && $cuota['fechaPago'] <> '' && $cuota['fechaPago'] <> '0000-00-00';
                if ($cuota['estado'] == 1 && !$tieneFechaPago) {
                    $cuotas[] = $cuota;
                }
            }
        } else if ($r['codigo'] == 2) {
            $cuotas = array();
        } else {
            $continuar = FALSE;
            ?>
            <h4 style="color: red;"><b>Error al buscar las cuotas de plan de pagos - <?php echo $r['mensaje']; ?></b></h4>
            <?php
        }
    }
} else {
    $continuar = FALSE;
}

if ($continuar) {
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
        $estadoIntencion = isset($intencionPendiente['estado']) ? $intencionPendiente['estado'] : 'enviada';
        // iniciada: todavía no se generó el checkout en Gire -> puede pagar o anular.
        // enviada: fue a la pantalla de Gire pero no hay resultado todavía (puede
        //          haber vuelto sin completar) -> no se genera un checkout nuevo,
        //          pero sí puede anular para liberar la intención y reintentar.
        $puedePagar = ($estadoIntencion == 'iniciada');
        $puedeAnular = ($estadoIntencion == 'iniciada' || $estadoIntencion == 'enviada');
        // El idPlanPago no viaja en el nodo de la intención: se busca en el listado
        // de cuotas (todavía impaga, por eso sigue apareciendo ahí) de alguna cuota
        // que forme parte de esta intención
        $idPlanPagoIntencion = '';
        foreach ($cuotas as $cuota) {
            if (in_array($cuota['idPlanPagoCuota'], $intencionPendiente['cuotas'])) {
                $idPlanPagoIntencion = $cuota['idPlanPago'];
                break;
            }
        }
        // Detalle de las cuotas que cubre esta intención, para el modal "Ver detalle"
        $cuotasDeLaIntencion = array();
        foreach ($cuotas as $cuota) {
            if (in_array($cuota['idPlanPagoCuota'], $intencionPendiente['cuotas'])) {
                $cuotasDeLaIntencion[] = $cuota;
            }
        }
    ?>
        <div class="alert alert-warning mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <?php if ($estadoIntencion == 'iniciada') { ?>
                        Ya tiene una <b>intenci&oacute;n de pago pendiente</b> por
                        <b>$<?php echo number_format($intencionPendiente['total'], 0, ',', '.'); ?></b>
                        (<?php echo $cantCuotasPendientes; ?> cuota<?php echo $cantCuotasPendientes == 1 ? '' : 's'; ?>).
                        Puede completar el pago o anularla para seleccionar otras cuotas.
                    <?php } else { ?>
                        Tiene un pago de
                        <b>$<?php echo number_format($intencionPendiente['total'], 0, ',', '.'); ?></b>
                        (<?php echo $cantCuotasPendientes; ?> cuota<?php echo $cantCuotasPendientes == 1 ? '' : 's'; ?>)
                        iniciado, sin un resultado todav&iacute;a. Si complet&oacute; el pago, espere unos minutos
                        y vuelva a consultar. Si no lo complet&oacute; (por ejemplo, si volvi&oacute; atr&aacute;s
                        desde la pantalla de pago), puede anularlo para intentarlo de nuevo.
                    <?php } ?>
                </div>
                <div class="mt-2 mt-md-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#detalleIntencionModal">Ver Detalle</button>
                    <?php if ($puedePagar && MOSTRAR_PAGO_EN_LINEA) { ?>
                        <form action="procesar_pago.php?id=<?php echo $hashColegiado; ?>&origen=plan_pago&idPlanPago=<?php echo htmlspecialchars($idPlanPagoIntencion, ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="d-inline">
                            <input type="hidden" name="hashIntencionPago" value="<?php echo htmlspecialchars($intencionPendiente['hash'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="totalActualizado" value="<?php echo htmlspecialchars($intencionPendiente['total'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn btn-success btn-sm">Pagar</button>
                        </form>
                    <?php } ?>
                    <?php if ($puedeAnular) { ?>
                        <form action="anular_intencion_pago.php?id=<?php echo $hashColegiado; ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Confirma que desea anular la intención de pago pendiente?');">
                            <input type="hidden" name="hashIntencionPago" value="<?php echo htmlspecialchars($intencionPendiente['hash'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Anular</button>
                        </form>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="modal fade" id="detalleIntencionModal" tabindex="-1" aria-labelledby="detalleIntencionModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="detalleIntencionModalLabel">Cuotas de la intenci&oacute;n de pago</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Cuota</th>
                            <th style="text-align: right;">Importe original</th>
                            <th style="text-align: right;">Importe actualizado</th>
                            <th style="text-align: center;">Vencimiento Original</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cuotasDeLaIntencion as $cuota) { ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $cuota['cuota']; ?></td>
                            <td style="text-align: right;"><?php echo number_format($cuota['importe'], 0, ',', '.'); ?></td>
                            <td style="text-align: right;"><?php echo number_format($cuota['importeActualizado'], 0, ',', '.'); ?></td>
                            <td style="text-align: center;"><?php echo cambiarFechaFormatoParaMostrar($cuota['vencimiento']); ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
              </div>
            </div>
          </div>
        </div>
    <?php } ?>
    <?php if (sizeof($intencionesAprobadas) > 0) {
        $totalAprobadas = 0;
        $cuotasIdsAprobadas = array();
        foreach ($intencionesAprobadas as $ia) {
            $totalAprobadas += $ia['total'];
            $cuotasIdsAprobadas = array_merge($cuotasIdsAprobadas, $ia['cuotas']);
        }
        $cantIntencionesAprobadas = sizeof($intencionesAprobadas);
        $cuotasDeAprobadas = array();
        foreach ($cuotas as $cuota) {
            if (in_array($cuota['idPlanPagoCuota'], $cuotasIdsAprobadas)) {
                $cuotasDeAprobadas[] = $cuota;
            }
        }
    ?>
        <div class="alert alert-warning mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <?php if ($cantIntencionesAprobadas > 1) { ?>
                        Tiene <?php echo $cantIntencionesAprobadas; ?> pagos por un total de
                        <b>$<?php echo number_format($totalAprobadas, 0, ',', '.'); ?></b>
                        <b>pendientes de acreditaci&oacute;n</b>. La confirmaci&oacute;n se realiza con la
                        rendici&oacute;n diaria de la cobranza, por lo que las cuotas pueden seguir figurando
                        como impagas hasta que se procese. No es necesario que vuelva a abonarlas.
                    <?php } else { ?>
                        Tiene un pago de
                        <b>$<?php echo number_format($totalAprobadas, 0, ',', '.'); ?></b>
                        <b>pendiente de acreditaci&oacute;n</b>. La confirmaci&oacute;n se realiza con la
                        rendici&oacute;n diaria de la cobranza, por lo que las cuotas pueden seguir figurando
                        como impagas hasta que se procese. No es necesario que vuelva a abonarlas.
                    <?php } ?>
                </div>
                <div class="mt-2 mt-md-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#detalleAprobadasModal">Ver Detalle</button>
                </div>
            </div>
        </div>

        <div class="modal fade" id="detalleAprobadasModal" tabindex="-1" aria-labelledby="detalleAprobadasModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="detalleAprobadasModalLabel">Cuotas con pago aprobado</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Cuota</th>
                            <th style="text-align: right;">Importe original</th>
                            <th style="text-align: right;">Importe actualizado</th>
                            <th style="text-align: center;">Vencimiento Original</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cuotasDeAprobadas as $cuota) { ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $cuota['cuota']; ?></td>
                            <td style="text-align: right;"><?php echo number_format($cuota['importe'], 0, ',', '.'); ?></td>
                            <td style="text-align: right;"><?php echo number_format($cuota['importeActualizado'], 0, ',', '.'); ?></td>
                            <td style="text-align: center;"><?php echo cambiarFechaFormatoParaMostrar($cuota['vencimiento']); ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
              </div>
            </div>
          </div>
        </div>
    <?php } ?>
    <div class="card drive-card drive-banner mb-4">
        <div class="card-body">
        <?php
        if (sizeof($cuotas) > 0) {
            // Las cuotas que ya forman parte de alguna intención de pago (activa o ya
            // aprobada) no se listan más acá (se ven en los modales "Ver Detalle" de arriba)
            $cuotasParaMostrar = array();
            foreach ($cuotas as $cuota) {
                if (!in_array($cuota['idPlanPagoCuota'], $cuotasYaCubiertas)) {
                    $cuotasParaMostrar[] = $cuota;
                }
            }

            $totalVencido = 0;
            $totalVencidoActualizado = 0;
            $totalNoVencidoActualizado = 0;
            $hoy = date('Y-m-d');
            foreach ($cuotasParaMostrar as $cuota) {
                if ($cuota['vencimiento'] <> '' && $cuota['vencimiento'] < $hoy) {
                    $totalVencido += $cuota['importe'];
                    $totalVencidoActualizado += $cuota['importeActualizado'];
                } else {
                    $totalNoVencidoActualizado += $cuota['importeActualizado'];
                }
            }
            $hayIntencionPendiente = isset($_SESSION['intencionPagoPendiente']);
            // Mientras la intención esté "iniciada"/"enviada" no se puede seleccionar
            // nada nuevo (evita duplicar el checkout). Si ya está "aprobada" (Gire
            // confirmó, solo falta que se acredite en la rendición diaria) se puede
            // seguir abonando otras cuotas que no formen parte de esa intención.
            $intencionBloqueaSeleccion = $hayIntencionPendiente
                && in_array($_SESSION['intencionPagoPendiente']['estado'], array('iniciada', 'enviada'));
            ?>
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h4 class="mb-0">Plan de pagos</h4>
                <?php if ($intencionBloqueaSeleccion) { ?>
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
            if ($totalVencidoActualizado > 0 || $totalNoVencidoActualizado > 0) {
            ?>
                <form id="formCuotas" action="metodo_pago.php?id=<?php echo $hashColegiado; ?>&origen=plan_pago" method="POST">
                    <input type="hidden" id="totalActualizado" name="totalActualizado" value="<?php echo $totalVencidoActualizado; ?>">
                    <input type="hidden" name="idPlanPago" value="<?php echo htmlspecialchars($cuotasParaMostrar[0]['idPlanPago'], ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="table-responsive">
                    <table id="cuotas" class="table">
                        <thead>
                            <tr>
                                <th style="text-align: center; width: 80px;">
                                    <input type="checkbox" id="seleccionarTodos" onclick="seleccionarTodo(this)" <?php echo $intencionBloqueaSeleccion ? 'disabled' : ''; ?>>
                                    <br><small>Todos</small>
                                </th>
                                <th style="text-align: center;">Cuota</th>
                                <th style="text-align: right;">Importe original</th>
                                <th style="text-align: right;">Importe actualizado</th>
                                <th style="text-align: center;">Vencimiento Original</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($cuotasParaMostrar as $cuota) {
                                $esVencida = ($cuota['vencimiento'] <> '' && $cuota['vencimiento'] < $hoy);
                                $checkboxDisabled = $esVencida || $intencionBloqueaSeleccion;
                                $tituloCheckbox = $esVencida
                                    ? 'Cuota vencida, debe abonarse'
                                    : ($intencionBloqueaSeleccion ? 'Ya tiene una intención de pago pendiente. Debe abonarla o anularla primero.' : '');
                            ?>
                            <tr>
                                <td style="text-align: center;">
                                    <input type="checkbox"
                                           name="cuotas_seleccionadas[]"
                                           value="<?php echo $cuota['idPlanPagoCuota']; ?>"
                                           id="check_<?php echo $cuota['idPlanPagoCuota']; ?>"
                                           data-importe="<?php echo $cuota['importeActualizado']; ?>"
                                           onclick="cambiaTotalCuotas(this.dataset.importe, this.id)"
                                           <?php echo $esVencida ? 'checked' : ''; ?>
                                           <?php echo $checkboxDisabled ? 'disabled' : ''; ?>
                                           <?php echo $tituloCheckbox ? 'title="'.htmlspecialchars($tituloCheckbox, ENT_QUOTES, 'UTF-8').'"' : ''; ?>>
                                    <?php if ($esVencida) { ?>
                                        <input type="hidden" name="cuotas_seleccionadas[]" value="<?php echo $cuota['idPlanPagoCuota']; ?>">
                                    <?php } ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php echo $cuota['cuota']; ?>
                                    <?php if ($esVencida) { ?>
                                        <br><span class="badge badge-danger">Vencida</span>
                                    <?php } ?>
                                </td>
                                <td style="text-align: right;"><?php echo number_format($cuota['importe'], 0, ',', '.'); ?></td>
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
                <?php if ($intencionActiva !== NULL || sizeof($intencionesAprobadas) > 0) { ?>
                    <p class="text-muted">Hasta que no se procese el lote de cobranza con el pago realizado no va a poder solicitar certificados.</p>
                <?php } ?>
            <?php
            }
            ?>
        <?php
        } else {
        ?>
            <h3>No tiene cuotas pendiente de pago</h3>
            <?php if ($intencionActiva !== NULL || sizeof($intencionesAprobadas) > 0) { ?>
                <p class="text-muted">Hasta que no se procese el lote de cobranza con el pago realizado no va a poder solicitar certificados.</p>
            <?php } ?>
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

    $(function() {
        let totalInicial = parseInt($('#totalActualizado').val()) || 0;
        actualizarVistaTotal(totalInicial);
    });

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
        const checkboxes = $('#cuotas tbody input[type="checkbox"]:not([disabled])');
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
