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

    $r = llamarWs(URL_WS.'/cursos/buscar_cursos_inscripto.php?idColegiado='.$idColegiado);

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
    } else if ($r['codigo'] == 1) {
        $cursos = $r['datos'];
    } else if ($r['codigo'] == 2) {
        $cursos = array();
    } else {
        $continuar = FALSE;
        ?>
        <h4 style="color: red;"><b>Error al buscar los cursos - <?php echo $r['mensaje']; ?></b></h4>
        <?php
    }
} else {
    $continuar = FALSE;
}

if ($continuar) {
    // Solo interesan los cursos vigentes (en curso): los finalizados ya no
    // admiten pago de cuotas nuevas por esta vía
    $cursosVigentes = array();
    foreach ($cursos as $curso) {
        if ($curso['estado'] == 'A') {
            $cursosVigentes[] = $curso;
        }
    }
    ?>
    <div class="card drive-card drive-banner mb-4">
        <div class="card-body">
            <h4 class="mb-3">Cuotas de cursos</h4>
            <?php if (sizeof($cursosVigentes) > 0) { ?>
                <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Curso</th>
                            <th style="text-align: center;">Fecha de Inicio</th>
                            <th style="text-align: right;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cursosVigentes as $curso) { ?>
                            <tr>
                                <td><?php echo $curso['titulo']; ?></td>
                                <td style="text-align: center;"><?php echo cambiarFechaFormatoParaMostrar($curso['fechaInicio']); ?></td>
                                <td style="text-align: right;">
                                    <a href="cuotas_curso.php?id=<?php echo $hashColegiado; ?>&reg=<?php echo $curso['idCursosAsistente']; ?>" class="btn btn-success">Ver cuotas</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                </div>
            <?php } else { ?>
                <h3>No tiene cursos vigentes.</h3>
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
