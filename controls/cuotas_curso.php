<?php
require_once '../dataAccess/config.php';
permisoLogueado();
require_once '../html/head.php';
require_once '../html/header.php';
require_once '../dataAccess/funcionesPhp.php';
require_once '../html/menuTramites.php';

$continuar = true;
if (isset($_GET['id']) && $_GET['id'] == $_SESSION['hashColegiado'] && isset($_GET['reg']) && $_GET['reg'] <> "") {
    $hashColegiado = $_SESSION['hashColegiado'];
    $matricula = $_SESSION['matricula'];
    $idCursosAsistente = $_GET['reg'];

    $r = llamarWs(URL_WS.'/cursos/buscar_cuotas_curso.php?hashColegiado='.$hashColegiado.'&idCursosAsistente='.$idCursosAsistente);

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
        $titulo = isset($r['nodo']['titulo']) ? $r['nodo']['titulo'] : '';

        // Se refresca acá y no solo en metodo_pago.php/pago_procesado.php: si el
        // colegiado entra directo a esta página, el dato en sesión puede estar
        // desactualizado (por ejemplo, si la intención se anuló desde otro lado)
        $intencionPagoPendiente = armarIntencionPagoPendiente($r['nodo']);

        // Si quedó "iniciada" o "enviada" (sin resultado de Gire) hace más de 5
        // minutos, se anula sola para no dejar la deuda trabada indefinidamente
        // y liberar esas cuotas para que se pueda generar una intención nueva.
        if ($intencionPagoPendiente !== NULL
            && in_array($intencionPagoPendiente['estado'], array('iniciada', 'enviada'))
            && $intencionPagoPendiente['fechaInicio'] <> ''
            && (time() - strtotime($intencionPagoPendiente['fechaInicio'])) > 300
        ) {
            if (anularIntencionPagoWs($intencionPagoPendiente['hash'])) {
                $intencionPagoPendiente = NULL;
            }
        }

        if ($intencionPagoPendiente !== NULL) {
            $_SESSION['intencionesPagoPendientesCurso'][$idCursosAsistente] = $intencionPagoPendiente;
        } else {
            unset($_SESSION['intencionesPagoPendientesCurso'][$idCursosAsistente]);
        }

        if ($r['codigo'] == 1) {
            // El WS trae todas las cuotas (pagas e impagas); acá solo interesan
            // las impagas, que son las que se pueden seleccionar para pagar
            $cuotas = array();
            foreach ($r['datos'] as $cuota) {
                if (!$cuota['abonada']) {
                    $cuotas[] = $cuota;
                }
            }
        } else if ($r['codigo'] == 2) {
            $cuotas = array();
        } else {
            $continuar = FALSE;
            ?>
            <h4 style="color: red;"><b>Error al buscar las cuotas del curso - <?php echo $r['mensaje']; ?></b></h4>
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
    <?php if (isset($_SESSION['intencionesPagoPendientesCurso'][$idCursosAsistente])) {
        $intencionPendiente = $_SESSION['intencionesPagoPendientesCurso'][$idCursosAsistente];
        $cantCuotasPendientes = sizeof($intencionPendiente['cuotas']);
        $estadoIntencion = isset($intencionPendiente['estado']) ? $intencionPendiente['estado'] : 'enviada';
        $puedePagar = ($estadoIntencion == 'iniciada');
        $puedeAnular = ($estadoIntencion == 'iniciada' || $estadoIntencion == 'enviada');
    ?>
        <div class="alert alert-warning mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <?php if ($estadoIntencion == 'iniciada') { ?>
                        Ya tiene una <b>intenci&oacute;n de pago pendiente</b> por
                        <b>$<?php echo number_format($intencionPendiente['total'], 0, ',', '.'); ?></b>
                        (<?php echo $cantCuotasPendientes; ?> cuota<?php echo $cantCuotasPendientes == 1 ? '' : 's'; ?>).
                        Puede completar el pago o anularla para seleccionar otras cuotas.
                    <?php } else if ($estadoIntencion == 'enviada') { ?>
                        Tiene un pago de
                        <b>$<?php echo number_format($intencionPendiente['total'], 0, ',', '.'); ?></b>
                        (<?php echo $cantCuotasPendientes; ?> cuota<?php echo $cantCuotasPendientes == 1 ? '' : 's'; ?>)
                        iniciado, sin un resultado todav&iacute;a. Si complet&oacute; el pago, espere unos minutos
                        y vuelva a consultar. Si no lo complet&oacute; (por ejemplo, si volvi&oacute; atr&aacute;s
                        desde la pantalla de pago), puede anularlo para intentarlo de nuevo.
                    <?php } else { ?>
                        Tiene un pago de
                        <b>$<?php echo number_format($intencionPendiente['total'], 0, ',', '.'); ?></b>
                        (<?php echo $cantCuotasPendientes; ?> cuota<?php echo $cantCuotasPendientes == 1 ? '' : 's'; ?>)
                        <b>pendiente de acreditaci&oacute;n</b>. La confirmaci&oacute;n se realiza con la
                        rendici&oacute;n diaria de la cobranza, por lo que las cuotas pueden seguir figurando
                        como impagas hasta que se procese. No es necesario que vuelva a abonarlas.
                    <?php } ?>
                </div>
                <?php if ($puedePagar || $puedeAnular) { ?>
                    <div class="mt-2 mt-md-0">
                        <?php if ($puedePagar && MOSTRAR_PAGO_EN_LINEA) { ?>
                            <form action="procesar_pago.php?id=<?php echo $hashColegiado; ?>&origen=curso&idCurso=<?php echo $idCursosAsistente; ?>" method="POST" class="d-inline">
                                <input type="hidden" name="hashIntencionPago" value="<?php echo htmlspecialchars($intencionPendiente['hash'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="totalActualizado" value="<?php echo htmlspecialchars($intencionPendiente['total'], ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="btn btn-success btn-sm">Pagar</button>
                            </form>
                        <?php } ?>
                        <?php if ($puedeAnular) { ?>
                            <form action="anular_intencion_pago.php?id=<?php echo $hashColegiado; ?>&origen=curso&idCurso=<?php echo $idCursosAsistente; ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Confirma que desea anular la intención de pago pendiente?');">
                                <input type="hidden" name="hashIntencionPago" value="<?php echo htmlspecialchars($intencionPendiente['hash'], ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm">Anular</button>
                            </form>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
    <div class="card drive-card drive-banner mb-4">
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-12"><h5><?php echo $titulo; ?></h5></div>
            </div>
            <?php
            if (sizeof($cuotas) > 0) {
                $totalVencidoActualizado = 0;
                $totalNoVencidoActualizado = 0;
                $hoy = date('Y-m-d');
                foreach ($cuotas as $cuota) {
                    if ($cuota['fechaVencimiento'] <> '' && $cuota['fechaVencimiento'] < $hoy) {
                        $totalVencidoActualizado += $cuota['importe'];
                    } else {
                        $totalNoVencidoActualizado += $cuota['importe'];
                    }
                }
                $hayIntencionPendiente = isset($_SESSION['intencionesPagoPendientesCurso'][$idCursosAsistente]);
                // Mientras la intención esté "iniciada"/"enviada" no se puede seleccionar
                // nada nuevo (evita duplicar el checkout). Si ya está "aprobada" (Gire
                // confirmó, solo falta que se acredite en la rendición diaria) se puede
                // seguir abonando otras cuotas que no formen parte de esa intención.
                $intencionBloqueaSeleccion = $hayIntencionPendiente
                    && in_array($_SESSION['intencionesPagoPendientesCurso'][$idCursosAsistente]['estado'], array('iniciada', 'enviada'));
                ?>
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <h4 class="mb-0">Cuotas del curso</h4>
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
                <?php if ($totalVencidoActualizado > 0 || $totalNoVencidoActualizado > 0) { ?>
                    <form id="formCuotas" action="metodo_pago.php?id=<?php echo $hashColegiado; ?>&origen=curso&idCursosAsistente=<?php echo $idCursosAsistente; ?>" method="POST">
                        <input type="hidden" id="totalActualizado" name="totalActualizado" value="<?php echo $totalVencidoActualizado; ?>">

                        <div class="table-responsive">
                        <table id="cuotas" class="table">
                            <thead>
                                <tr>
                                    <th style="text-align: center; width: 80px;">
                                        <input type="checkbox" id="seleccionarTodos" onclick="seleccionarTodo(this)" <?php echo $intencionBloqueaSeleccion ? 'disabled' : ''; ?>>
                                        <br><small>Todos</small>
                                    </th>
                                    <th style="text-align: center;">Cuota</th>
                                    <th style="text-align: right;">Importe</th>
                                    <th style="text-align: center;">Vencimiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $cuotasEnIntencionPago = isset($_SESSION['intencionesPagoPendientesCurso'][$idCursosAsistente]['cuotas']) ? $_SESSION['intencionesPagoPendientesCurso'][$idCursosAsistente]['cuotas'] : array();
                                foreach ($cuotas as $cuota) {
                                    $esVencida = ($cuota['fechaVencimiento'] <> '' && $cuota['fechaVencimiento'] < $hoy);
                                    $enIntencionPago = in_array($cuota['idCursosAsistenteCuota'], $cuotasEnIntencionPago);
                                    $checkboxDisabled = $esVencida || $enIntencionPago || $intencionBloqueaSeleccion;
                                    $tituloCheckbox = $esVencida
                                        ? 'Cuota vencida, debe abonarse'
                                        : ($enIntencionPago
                                            ? 'Esta cuota ya forma parte de una intención de pago'
                                            : ($intencionBloqueaSeleccion ? 'Ya tiene una intención de pago pendiente. Debe abonarla o anularla primero.' : ''));
                                ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <input type="checkbox"
                                               name="cuotas_seleccionadas[]"
                                               value="<?php echo $cuota['idCursosAsistenteCuota']; ?>"
                                               id="check_<?php echo $cuota['idCursosAsistenteCuota']; ?>"
                                               data-importe="<?php echo $cuota['importe']; ?>"
                                               onclick="cambiaTotalCuotas(this.dataset.importe, this.id)"
                                               <?php echo $esVencida ? 'checked' : ''; ?>
                                               <?php echo $checkboxDisabled ? 'disabled' : ''; ?>
                                               <?php echo $tituloCheckbox ? 'title="'.htmlspecialchars($tituloCheckbox, ENT_QUOTES, 'UTF-8').'"' : ''; ?>>
                                        <?php if ($esVencida) { ?>
                                            <input type="hidden" name="cuotas_seleccionadas[]" value="<?php echo $cuota['idCursosAsistenteCuota']; ?>">
                                        <?php } ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php echo $cuota['cuota']; ?>
                                        <?php if ($esVencida) { ?>
                                            <br><span class="badge badge-danger">Vencida</span>
                                        <?php } ?>
                                        <?php if ($enIntencionPago) { ?>
                                            <br><span class="badge badge-warning" title="Ya tiene una intención de pago pendiente por esta cuota">Pago pendiente de confirmación</span>
                                        <?php } ?>
                                    </td>
                                    <td style="text-align: right;"><?php echo number_format($cuota['importe'], 0, ',', '.'); ?></td>
                                    <td style="text-align: center;"><?php echo cambiarFechaFormatoParaMostrar($cuota['fechaVencimiento']); ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        </div>
                    </form>
                <?php } else { ?>
                    <h3>No tiene cuotas pendientes de pago</h3>
                <?php } ?>
            <?php } else { ?>
                <h3>No tiene cuotas pendientes de pago</h3>
            <?php } ?>
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
        const checkboxes = $('#cuotas tbody input[type="checkbox"]:not(:disabled)');
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
