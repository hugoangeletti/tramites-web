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
    $matricula = $_SESSION['matricula'];
    $especialidades = isset($_SESSION['especialidades']) ? $_SESSION['especialidades'] : array();
} else {
    $continuar = FALSE;
}

if ($continuar) {
    ?>
    <div class="card drive-card drive-banner mb-4">
        <div class="card-body">
            <h4 class="mb-3">Mis especialidades</h4>
            <?php if (sizeof($especialidades) > 0) { ?>
                <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Especialidad</th>
                            <th style="text-align: center;">Fecha Especialista</th>
                            <th style="text-align: center;">Fecha Recertificaci&oacute;n</th>
                            <th style="text-align: center;">Fecha Vencimiento</th>
                            <th style="text-align: center;">Fecha Jerarquizado</th>
                            <th style="text-align: center;">Fecha Consultor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $hoy = date('Y-m-d');
                        foreach ($especialidades as $especialidad) {
                            $fechaVencimiento = isset($especialidad['fechaVencimiento']) ? $especialidad['fechaVencimiento'] : null;
                            $debeRecertificar = ($fechaVencimiento <> null && $fechaVencimiento <> '' && $fechaVencimiento < $hoy);
                        ?>
                        <tr>
                            <td><?php echo $especialidad['nombreEspecialidad']; ?></td>
                            <td style="text-align: center;"><?php echo cambiarFechaFormatoParaMostrar($especialidad['fechaEspecialista']); ?></td>
                            <td style="text-align: center;"><?php echo cambiarFechaFormatoParaMostrar($especialidad['fechaRecertificacion']); ?></td>
                            <td style="text-align: center;">
                                <?php echo $fechaVencimiento <> null ? cambiarFechaFormatoParaMostrar($fechaVencimiento) : ''; ?>
                                <?php if ($debeRecertificar) { ?>
                                    <br><span class="badge badge-danger">Debe recertificar</span>
                                <?php } ?>
                            </td>
                            <td style="text-align: center;"><?php echo isset($especialidad['fechaJerarquizado']) ? cambiarFechaFormatoParaMostrar($especialidad['fechaJerarquizado']) : ''; ?></td>
                            <td style="text-align: center;"><?php echo isset($especialidad['fechaConsultor']) ? cambiarFechaFormatoParaMostrar($especialidad['fechaConsultor']) : ''; ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                </div>
            <?php } else { ?>
                <h3>No tiene especialidades registradas.</h3>
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

</body>
