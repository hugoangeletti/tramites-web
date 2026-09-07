<?php
require_once '../dataAccess/config.php';
permisoLogueado();
require_once '../html/head.php';
require_once '../html/header.php';
require_once '../dataAccess/funcionesPhp.php';
require_once '../html/menuTramites.php';

$continuar = true;
if (isset($_GET['id']) && $_GET['id'] == $_SESSION['hashColegiado']) {
    $hashColegiado = $_SESSION['hashColegiado'];

    if (isset($_POST['cuotas_seleccionadas']) && sizeof($_POST['cuotas_seleccionadas']) > 0) {
        $cuotasSeleccionadas = $_POST['cuotas_seleccionadas'];
    } else {
        $continuar = FALSE;
    }

    if (isset($_POST['totalActualizado'])) {
        $valorLimpio = str_replace(',', '.', $_POST['totalActualizado']);
        if (filter_var($valorLimpio, FILTER_VALIDATE_FLOAT) !== false) {
            $totalActualizado = $valorLimpio;
        } else {
            $continuar = FALSE;
        }
    } else {
        $continuar = FALSE;
    }

    $tieneDeudaAnterior = isset($_POST['tieneDeudaAnterior']) && $_POST['tieneDeudaAnterior'] == '1';
    if (isset($_GET['origen']) && $_GET['origen'] == 'plan_pago') {
        $origen = 'plan_pago';
        if (isset($_POST['idPlanPago']) && $_POST['idPlanPago'] <> '') {
            $idPlanPago = $_POST['idPlanPago'];
        } else {
            $continuar = FALSE;
        }
    } else if (isset($_GET['origen']) && $_GET['origen'] == 'curso') {
        $origen = 'curso';
        if (isset($_GET['idCursosAsistente']) && $_GET['idCursosAsistente'] <> '') {
            $idCursosAsistente = $_GET['idCursosAsistente'];
        } else {
            $continuar = FALSE;
        }
    } else {
        $origen = 'colegiacion';
    }
} else {
    $continuar = FALSE;
}

if ($continuar) {
?>
    <div class="card drive-card drive-banner mb-4">
        <div class="card-body">
            <h4 class="mb-3">Método de pago</h4>
            <p>
                Cuotas seleccionadas: <b><?php echo sizeof($cuotasSeleccionadas); ?></b>
                <br>
                Total a pagar: <b>$<?php echo number_format($totalActualizado, 0, ',', '.'); ?></b>
            </p>
            <div class="d-flex flex-wrap">
                <?php
                if ($origen == 'plan_pago') {
                    $urlImprimir = 'imprimirChequeraPlanPago.php?id=' . $hashColegiado;
                    $urlVolver = 'planDePagos.php?id=' . $hashColegiado;
                    $urlGenerarIntencion = 'generar_intencion_pago.php?id=' . $hashColegiado . '&origen=plan_pago&idPlanPago=' . $idPlanPago;
                } else if ($origen == 'curso') {
                    $urlImprimir = 'esem_imprimir_chequera.php?id=' . $hashColegiado . '&reg=' . $idCursosAsistente . '&asistente';
                    $urlVolver = 'esem_asistente_curso.php?id=' . $hashColegiado;
                    $urlGenerarIntencion = 'generar_intencion_pago.php?id=' . $hashColegiado . '&origen=curso&idCursosAsistente=' . $idCursosAsistente;
                } else {
                    $urlImprimir = $tieneDeudaAnterior
                        ? 'imprimirNotaDeuda.php?id=' . $hashColegiado
                        : 'imprimirChequera.php?id=' . $hashColegiado;
                    $urlVolver = 'cuotas.php?id=' . $hashColegiado;
                    $urlGenerarIntencion = 'generar_intencion_pago.php?id=' . $hashColegiado;
                }
                ?>
                <a href="<?php echo $urlImprimir; ?>" class="btn btn-dark mr-2 mb-2">Imprimir cuotas seleccionadas</a>
                <?php if (MOSTRAR_PAGO_EN_LINEA) { ?>
                    <form action="<?php echo $urlGenerarIntencion; ?>" method="POST" class="d-inline">
                        <?php foreach ($cuotasSeleccionadas as $idCuota) { ?>
                            <input type="hidden" name="cuotas_seleccionadas[]" value="<?php echo htmlspecialchars($idCuota, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php } ?>
                        <input type="hidden" name="totalActualizado" value="<?php echo htmlspecialchars($totalActualizado, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-success mb-2">Pagar en línea</button>
                    </form>
                <?php } ?>
            </div>
            <a href="<?php echo $urlVolver; ?>" class="btn btn-secondary btn-sm">Volver</a>
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
