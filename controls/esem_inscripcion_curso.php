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
        curl_setopt($ch, CURLOPT_URL, 'https://webservices.colmed1.com.ar/colegio/ws-colmed/cursos/buscar_cursos_activos.php?idColegiado='.$idColegiado);
    } else {
        curl_setopt($ch, CURLOPT_URL, 'http://www.colmed1.com/desarrollo/colegio/ws-colmed/cursos/buscar_cursos_activos.php?idColegiado='.$idColegiado);
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
                    $cursos = $respuesta['datos'];
                } else {
                    if ($respuesta['codigo'] <> 2) {
                        $continuar = FALSE;
                    ?>
                        <h4 style="color: red;"><b>Error al buscar las cursos - <?php echo $respuesta['mensaje']; ?></b></h4>
                    <?php
                    } else {
                        $mensaje = $respuesta['mensaje'];
                        $cursos = array();                        
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
    ?>
    <div class="card drive-card drive-banner mb-4">
        <div class="card-body">
            <h6>ESEM -> Cursos</h6>
        <?php
        if (sizeof($cursos) >= 0) {
        ?>
            <div class="table-responsive">
            <table id="cuotas" class="table">
                <thead>
                      <tr>
                          <th style="display: none;">IdCurso</th>
                          <th style="text-align: center;">Curso</th>
                          <th style="text-align: center;">Inscripción</th>
                      </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($cursos as $curso) {
                        $idCurso = $curso['idCurso'];
                        $titulo = $curso['titulo'];
                        $inscripto = $curso['inscripto'];
                        $fechaInicio = $curso['fechaInicio'];
                        $cupoInscriptos = $curso['cupoInscriptos'];
                        $inscripcionDesde = $curso['inscripcionDesde'];
                        $inscripcionHasta = $curso['inscripcionHasta'];

                        //si el inicio del curso es del año actual o superior, entonces lo muestro
                        if (substr($fechaInicio, 0, 4) < date('Y')) { continue; }
                        if (isset($inscripcionHasta) && $inscripcionHasta < date('Y-m-d')) { continue; }
                        ?>
                        <tr>
                            <td style="display: none;"><?php echo $idCurso; ?></td>
                            <td style="text-align: center;"><?php echo $titulo; ?></td>
                            <td style="text-align: right;">
                                <?php
                                if ($inscripto) {
                                ?>                                    
                                    <a href="esem_imprimir_chequera.php?id=<?php echo $hashColegiado; ?>&reg=<?php echo $idCurso; ?>" class="btn btn-info">Chequera</a>
                                    <a href="esem_imprimir_planilla.php?id=<?php echo $hashColegiado; ?>&reg=<?php echo $idCurso; ?>" class="btn btn-info">Planilla</a>
                                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#anular_<?php echo $idCurso; ?>Modal">Anular inscripción</button>
                                    <div class="modal fade" id="anular_<?php echo $idCurso; ?>Modal" tabindex="-1" aria-labelledby="anular_<?php echo $idCurso; ?>ModalLabel" aria-hidden="true">
                                      <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                          <div class="modal-header">
                                            <h5 class="modal-title" id="anular_<?php echo $idCurso; ?>ModalLabel"><h4 class="modal-title">Anular incripción a cursos</h4></h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                              <span aria-hidden="true">&times;</span>
                                            </button>
                                          </div>
                                          <div class="modal-body">
                                            <div class="row">
                                                <div class="col-xs-12 text-center"><h2><b><?php echo $titulo; ?></b></h2></div>
                                            </div>
                                            <div class="row"><hr></div>
                                            <div class="row">
                                                <form id="formInscripcion" name="formInscripcion" method="POST" onSubmit="" action="esem_inscripcion_anular.php?id=<?php echo $hashColegiado; ?>&reg=<?php echo $idCurso; ?>">
                                                    <div class="col-xs-12 text-center">
                                                        <h4 class="alert alert-warning">
                                                            <p>Si está de acuerdo con ANULAR la inscripción a este curso, por favor presione el botón Confirmar.</p>

                                                            <p>Presione el botón Cancelar si no desea ANULAR la inscripción al curso.</p>
                                                        </h4>
                                                    </div>
                                                    <div class="col-xs-12 text-center">
                                                        <button type="submit"  class="btn btn-success" >Confirmar </button>
                                                    </div>
                                                </form>
                                            </div>
                                          </div>
                                          <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                          </div>
                                        </div>
                                      </div>
                                    </div>                    
                                <?php
                                } else {
                                ?>
                                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#curso_<?php echo $idCurso; ?>Modal">Inscribir</button>
                                    <!-- Button trigger modal -->
                                    <!-- Modal -->
                                    <div class="modal fade" id="curso_<?php echo $idCurso; ?>Modal" tabindex="-1" aria-labelledby="curso_<?php echo $idCurso; ?>ModalLabel" aria-hidden="true">
                                      <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                          <div class="modal-header">
                                            <h5 class="modal-title" id="curso_<?php echo $idCurso; ?>ModalLabel"><h4 class="modal-title">Incripción a cursos</h4></h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                              <span aria-hidden="true">&times;</span>
                                            </button>
                                          </div>
                                          <div class="modal-body">
                                            <div class="row">
                                                <div class="col-xs-12 text-center"><h2><b><?php echo $titulo; ?></b></h2></div>
                                            </div>
                                            <div class="row"><hr></div>
                                            <div class="row">
                                                <form id="formInscripcion" name="formInscripcion" method="POST" onSubmit="" action="esem_inscripcion_curso_confirmar.php?id=<?php echo $hashColegiado; ?>">
                                                    <div class="col-xs-12 text-center">
                                                        <h4 class="alert alert-warning">
                                                            <p>Si está de acuerdo con inscribirse a este curso, por favor presione el botón Confirmar.</p>

                                                            <p>Presione el botón Cancelar si no desea inscribirse al curso.</p>
                                                        </h4>
                                                    </div>
                                                    <div class="col-xs-12 text-center">
                                                        <button type="submit"  class="btn btn-success" >Confirmar </button>
                                                        <input type="hidden" name="reg" id="reg" value="<?php echo $idCurso; ?>">
                                                    </div>
                                                </form>
                                            </div>
                                          </div>
                                          <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                          </div>
                                        </div>
                                      </div>
                                  </div>
                                <?php
                                }
                                ?>
                            </td>
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
            <h3>No existen cursos activos para que se pueda inscribir.</h3>
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
