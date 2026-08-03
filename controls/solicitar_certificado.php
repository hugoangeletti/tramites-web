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
        curl_setopt($ch, CURLOPT_URL, 'https://webservices.colmed1.com.ar/colegio/ws-colmed/colegiado/buscar_solicitud_certificados.php?idColegiado='.$idColegiado);
    } else {
        curl_setopt($ch, CURLOPT_URL, 'http://www.colmed1.com/desarrollo/colegio/ws-colmed/colegiado/buscar_solicitud_certificados.php?idColegiado='.$idColegiado);
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
        //echo "cURL Error #:" . $err;
    ?>
        <div class="row alert alert-danger">
            <div class="col-md-12 text-left">Disculpe las molestias. Momentaneamente fuera de servicio, intente m&aacute;s tarde</div>
        </div>
    <?php
    } else {
      switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
        case 200:  
            $rta=(json_decode($respuesta,true));
            //var_dump($rta);
            if (isset($rta) && isset($rta['respuesta'])) {
                $respuesta = $rta['respuesta'];
                if ($respuesta['codigo'] == 1) {
                    $datos = $respuesta['datos'];
                } else {
                    $continuar = FALSE;
                    $mensaje = $respuesta['mensaje'];
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
    $apellidoNombre = trim($datos['apellido']).' '.trim($datos['nombre']);
    $matricula = $datos['matricula'];
    $solicitudes = $datos['solicitudes'];
    $cantidadSolicitudes = $datos['cantidadSolicitudes'];
    $entidades = $datos['entidades'];

    if (isset($_POST['mensaje'])) {
    ?>
       <div class="ocultarMensaje"> 
            <p class="<?php echo $_POST['clase'];?>"><?php echo $_POST['mensaje'];?></p>  
       </div>
    <?php    
    }   
    ?>
        <div class="card drive-card drive-banner mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <h4 class="mb-0">Certificados solicitados</h4>
                <?php
                if (sizeof($solicitudes) >= 0) {
                    $solicitadasHoy = 0;
                    foreach ($solicitudes as $solicitud) {
                        if (substr($solicitud['fechaSolicitud'], 0, 10) == date('Y-m-d')) {
                            $solicitadasHoy += 1;
                        }
                    }
                }
                ?>
                <button type="button" class="btn btn-dark" data-toggle="modal" data-target="#agregarSolicitudModal" <?php if ($solicitadasHoy >= CANTIDAD_SOLICITUDES_PERMITIDAS) { echo 'disabled'; } ?> >Solicitar Certificado</button>
                <div class="modal fade" id="agregarSolicitudModal" tabindex="-1" aria-labelledby="agregarSolicitudModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content  alert alert-info">
                            <div class="modal-header alert alert-info">
                                <h5 class="modal-title" id="agregarSolicitudModalLabel">
                                    <h4 class="modal-title">Solicitar Certificado</h4>
                                </h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row"></div>
                                <div class="row">
                                    <form id="formInscripcion" name="formInscripcion" method="POST" onSubmit="" action="enviar_solicitud_certificado.php?id=<?php echo $hashColegiado; ?>">
                                        <div class="col-md-12 text-center">
                                            <h5>
                                                <label>Para ser presentado en: </label>
                                                <select class="form-control" id="idEntidad" name="idEntidad">
                                                    <option value="">Seleccione Entidad </option>
                                                    <?php
                                                    if (sizeof($entidades) > 0) {
                                                        foreach ($entidades as $row) {
                                                        ?>
                                                            <option value="<?php echo $row['id'] ?>" ><?php echo $row['nombre'] ?></option>
                                                        <?php
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </h5>
                                        </div>
                                        <div class="col-md-12 text-center"><br></div>
                                        <div class="col-md-12 text-center">
                                            <p>
                                                <label>Si no encuentra la entidad en el listado, debe ingresarlo en este opción y se le confirmará desde el Colegio la emisión del certificado.</label>
                                                <input class="form-control" type="text" name="presentado" id="presentado">
                                            </p>
                                        </div>
                                        <div class="col-md-12 text-center">
                                            <button type="submit"  class="btn btn-info" >Confirmar </button>
                                            <input class="form-control" type="hidden" name="idTipoCertificado" id="idTipoCertificado" value="6">
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
            </div>
        <?php
        if (sizeof($solicitudes) >= 0) {
        ?>
            <div class="table-responsive">
            <table id="cuotas" class="table">
                <thead>
                      <tr>
                          <th style="display: none;">Id</th>
                          <th style="text-align: center;">Fecha</th>
                          <th class="d-none d-md-table-cell" style="text-align: left;">Tipo</th>
                          <th style="text-align: left;">Para ser presentado</th>
                          <th class="d-none d-md-table-cell" style="text-align: center;">Emitido</th>
                          <th style="text-align: center;">Certificado</th>
                      </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($solicitudes as $solicitud) {
                        $idSolicitudCertificadoWeb = $solicitud['idSolicitudCertificadoWeb'];
                        $fechaSolicitud = $solicitud['fechaSolicitud'];
                        $nombreTipoCertificado = $solicitud['nombreTipoCertificado'];
                        $nombreSolcitudCertificadoWebEntidad = $solicitud['nombreSolcitudCertificadoWebEntidad'];
                        $presentado = $solicitud['presentado'];
                        $fechaEmision = $solicitud['fechaEmision'];
                        $hash = $solicitud['hash'];
                        $vencido = $solicitud['vencido'];
                        $idSolicitudCertificados = $solicitud['idSolicitudCertificados'];
                        $idSolicitudCertificadoWebEstado = $solicitud['idSolicitudCertificadoWebEstado'];
                        $leyendaSolicitudCertificadoWebEstado = $solicitud['leyendaSolicitudCertificadoWebEstado'];
                        ?>
                        <tr>
                            <td style="display: none;"><?php echo $idSolicitudCertificadoWeb; ?></td>
                            <td style="text-align: center;"><?php echo cambiarFechaFormatoParaMostrar(substr($fechaSolicitud, 0, 10)); ?></td>
                            <td class="d-none d-md-table-cell" style="text-align: left;"><?php echo $nombreTipoCertificado; ?></td>
                            <td style="text-align: left;"><?php echo trim($nombreSolcitudCertificadoWebEntidad).' '.trim($presentado); ?></td>
                            <td class="d-none d-md-table-cell" style="text-align: center;"><?php echo cambiarFechaFormatoParaMostrar($fechaEmision); ?></td>
                            <td style="text-align: center;">
                                <?php
                                if ($vencido == 0 && $idSolicitudCertificadoWebEstado == 1) {
                                    //si no esta vencido y esta generado el certificado
                                    ?>                                    
                                    <a href="imprimirCertificado.php?id=<?php echo $hash; ?>" class="btn btn-info">Descargar PDF</a>           
                                <?php
                                } else {
                                    if ($vencido == 1) {
                                        //<h5 class="alert alert-warning">No disponible.</h5>
                                    ?>
                                        <p class="btn btn-secondary">Certificado vencido.</h5>
                                    <?php
                                    } else {
                                    ?>
                                        <a href="#" class="btn btn-warning text-center"><?php echo $leyendaSolicitudCertificadoWebEstado; ?></a>
                                    <?php
                                    }
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
