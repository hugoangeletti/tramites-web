<?php
require_once '../dataAccess/config.php';
permisoLogueado();
require_once '../html/head.php';
require_once '../html/header.php';

$matricula = $_SESSION['matricula'];
$continua = TRUE;
if ($continua) {
    //obtener los datos del colegiDO
    ini_set('xdebug.var_display_max_depth', -1);
    ini_set('xdebug.var_display_max_children', -1);
    ini_set('xdebug.var_display_max_data', -1);
    set_time_limit(0);

    $ch = curl_init();
    if (ENV == "prod") {
        curl_setopt($ch, CURLOPT_URL, 'https://webservices.colmed1.com.ar/colegio/ws-colmed/colegiado/buscar_colegiado.php?matricula='.$matricula);
    } else {
        curl_setopt($ch, CURLOPT_URL, 'https://www.colmed1.com/desarrollo/colegio/ws-colmed/colegiado/buscar_colegiado.php?matricula='.$matricula);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    //curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);

    $headers = array();
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $respuesta = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);

    $continuar = true;
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
                    $colegiado = $respuesta['datos'];
                    $_SESSION['apellidoNombre'] = $colegiado['apellido'].' '.$colegiado['nombre'];
                    $_SESSION['idColegiado'] = $colegiado['idColegiado'];
                    $_SESSION['hashColegiado'] = $colegiado['hashColegiado'];
                    $idColegiado = $_SESSION['idColegiado'];
                    $hashColegiado = $_SESSION['hashColegiado'];
                    $conSeguro = $colegiado['conSeguro'];
                    $mailRegistrado = $_SESSION['user_entidad']['mail'];
                    if (isset($conSeguro) && $conSeguro <> '') {
                        $conTramites = TRUE;
                    } else {
                        $conTramites = FALSE;
                    }
                    $tieneCurso = $colegiado['tieneCurso'];
                } else {
                ?>
                    <h4 style="color: red;">Usuario NO V&Aacute;LIDO - <?php echo $respuesta['mensaje']; ?></h4>
                    <?php
                    $continuar = FALSE;
                    if (isset($respuesta['datos'])) {
                        $mailRegistrado = $respuesta['datos']['mailRegistrado'];
                        $mailRegistradoSeparado = explode("@", $mailRegistrado);
                        $longMail = strlen($mailRegistradoSeparado[0]);

                        if ($longMail > 5) {
                            $letras = 2;
                        } else {
                            $letras = 1;
                        }

                        $mailMostrar = substr($mailRegistradoSeparado[0], 0, $letras);
                        $i = $letras;
                        while ($i < $longMail-$letras) {
                            $mailMostrar .= "*";
                            $i++;
                        }
                        $mailMostrar .= substr($mailRegistradoSeparado[0], $longMail-$letras, $letras);
                        $mailMostrar .= "@".$mailRegistradoSeparado[1];
                        ?>
                        <h3>Mail registrado: <?php echo $mailMostrar; ?></h3>
                        <div class="col-md-4">
                            <a href="cambiarMail.php" class="btn btn-info" role="button">Cambiar Mail</a>
                            <a href="login.php" class="btn btn-info" role="button">Volver</a>
                        </div>


                    <?php
                    } else {
                    ?>
                        <div class="col-md-4">
                            <a href="login.php" class="btn btn-info" role="button">Volver</a>
                        </div>
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

    if ($continuar) {
        $permiteCertificado = TRUE;
        switch ($colegiado['tipoEstado']) {
            case 'A':
                $estadoActual = "ACTIVO";
                $colorEstadoActual = "green";
                break;

            case 'I':
                $estadoActual = "INSCRIPTO";
                $colorEstadoActual = "green";
                break;

            case 'J':
                $estadoActual = "JUBILACION";
                $colorEstadoActual = "red";
                break;

            case 'F':
                $estadoActual = "FALLECIDO";
                $colorEstadoActual = "red";
                break;

            case 'C':
                $estadoActual = "BAJA - ".$colegiado['detalleMovimiento'];
                $colorEstadoActual = "red";
                $permiteCertificado = FALSE;
                break;

            default:
                $estadoActual = "SIN DATO";
                $colorEstadoActual = "blue";
                $permiteCertificado = FALSE;
                break;
        }
        
        if ($colegiado['estado_tesoreria']['codigoDeudor'] == 0) {
            $colorEstadoTesoreria = "green";
        } else {
            $colorEstadoTesoreria = "#EE5757";
            $permiteCertificado = FALSE;
        }

        if (isset($_POST['mensaje']) && $_POST['mensaje'] <> "OK") {
        ?>
            <div class="col-md-12"><hr></div>
            <div class="col-md-12"> 
                <p class="<?php echo $_POST['clase'];?>"><?php echo $_POST['mensaje'];?></p>  
            </div>
        <?php
        }
        ?>
        <div class="col-md-12"><hr></div>
        <div class="col-md-12">
            <div class="row">
                <div class="card" style="width: 30rem;">
                    <!--<img class="card-img-top" src="..." alt="Card image cap">-->
                    <div class="card-body">
                      <h5 class="card-title">Bienvenido!</h5>
                      <p class="card-text">
                        <h4><?php if ($colegiado['sexo'] == "M") { echo "Dr. "; } else { echo "Dra. ";} echo $_SESSION['apellidoNombre']; ?></h4>
                        <h4>M.P. <?php echo $colegiado['matricula']; ?></h4>
                        <h5>Estado actual: <b style="color: <?php echo $colorEstadoActual;  ?>;">
                            <?php echo $estadoActual; ?></b>
                        </h5>
                        <h5>Situación con Tesorería: <b style="color: <?php echo $colorEstadoTesoreria;  ?>;">
                            <?php echo $colegiado['estado_tesoreria']['leyenda']; ?></b></h5>
                      </p>
                      <a href="logout.php" class="btn btn-dark">Salir de trámites</a>
                    </div>
                </div>
                <div class="card" style="width: 18rem;">
                    <!--<img class="card-img-top" src="..." alt="Card image cap">-->
                    <div class="card-body">
                      <h5 class="card-title">Certificados</h5>
                      <p class="card-text">
                        <?php 
                        //verificar si tiene inscripciones a cursos para mostrar
                        if (!$permiteCertificado) {
                        ?>
                            <p class="alert alert-warning">Para poder solicitar un certificado debe estar ACTIVO y AL DÍA CON LAS CUOTAS DE COLEGIACIÖN</p>
                        <?php
                        }
                        ?>
                        <a href="solicitar_certificado.php?id=<?php echo $hashColegiado; ?>" class="btn btn-info btn-block <?php if (!$permiteCertificado) { echo 'disabled'; } ?>" role="button" >Solicitar certificado</a>
                      </p>
                      <h5 class="card-title">Tesorería</h5>
                      <p class="card-text">
                        <a href="cuotas.php?id=<?php echo $hashColegiado; ?>" class="btn btn-info btn-block" role="button">Cuotas de colegiación</a>
                        <!--<a href="cuotasColegiacion.php?id=<?php echo $hashColegiado; ?>" class="btn btn-info btn-block" role="button">Cuotas de colegiación</a>-->
                        <br>
                        <?php 
                        /*
                        <a href="pagosRegistrados.php?id=<?php echo $idColegiado; ?>" class="btn btn-info btn-block" role="button">Pagos registrados</a>
                        <br>
                        */
                        if ($colegiado['estado_tesoreria']['codigoDeudor'] == 4) {
                        ?>
                            <a href="planDePagos.php?id=<?php echo $hashColegiado; ?>" class="btn btn-info btn-block" role="button">Plan de Pagos</a>
                            <br>
                        <?php 
                        }
                        /*
                        <a href="debitoAutomatico.php?id=<?php echo $idColegiado; ?>" class="btn btn-info btn-block" role="button">Débito automático</a>
                        */
                        ?>
                      </p>
                    </div> 
                </div>
                <div class="card" style="width: 18rem;">
                    <!--<img class="card-img-top" src="..." alt="Card image cap">-->
                    <div class="card-body">
                      <h5 class="card-title">ESEM</h5>
                      <p class="card-text">
                        <a href="esem_inscripcion_curso.php?id=<?php echo $hashColegiado; ?>" class="btn btn-info btn-block" role="button">Inscripción a cursos</a>
                        <br>
                        <?php 
                        //verificar si tiene inscripciones a cursos para mostrar
                        if ($tieneCurso) {
                        ?>
                            <a href="esem_asistente_curso.php?id=<?php echo $hashColegiado; ?>" class="btn btn-info btn-block" role="button">Asistente a cursos</a>
                        <?php 
                        } 
                        ?>
                      </p>
                    </div> 
                </div>
                <div class="card" style="width: 18rem;">
                    <!--<img class="card-img-top" src="..." alt="Card image cap">-->
                    <div class="card-body">
                      <h5 class="card-title">Actualización de datos</h5>
                      <p class="card-text">
                        <a href="actualizar_domicilio_particular.php?id=<?php echo $hashColegiado; ?>" class="btn btn-info btn-block" role="button">Actualizar domicilio particular</a>
                        <br>
                        <a href="actualizar_telefonos.php?id=<?php echo $hashColegiado; ?>" class="btn btn-info btn-block" role="button">Actualizar telefonos</a>
                        <br>
                      </p>
                    </div> 
                </div>
                <?php 
                if ($conTramites) {
                ?>
                <div class="card" style="width: 18rem;">
                    <!--<img class="card-img-top" src="..." alt="Card image cap">-->
                    <div class="card-body">
                      <h5 class="card-title">Seguro de praxis médica</h5>
                      <p class="card-text">
                        <?php 
                        if ($conSeguro == 'ASEGURADO_COLEGIO') {
                        ?>
                            <a href="imprimirCertificadoCoberturaSeguro.php?id=<?php echo $hashColegiado; ?>" class="btn btn-info btn-block" role="button">Seguro - Certificado Cobertura</a>
                            <br>
                            <button type="button" class="btn btn-info btn-block" data-toggle="modal" data-target="#ampliarSeguroModal">Ampliación de Cobertura</button>                        
                            <br>
                        <?php 
                        } else {
                        ?>
                            <p>Estimado colega, Usted se encuentra Agremiado, debe consultar con AMEPLA por el certificado de cobertura del seguro.</p>
                            <a href="https://www.amepla.org.ar/" class="btn btn-info btn-block" role="button" target="_BLANK" >Ingreso a AMEPLA</a>
                        <?php
                        }
                        /*
                        <a href="bajas.php?id=<?php echo $idColegiado; ?>" class="btn btn-info btn-block" role="button">Baja de matrícula</a>
                        <br>
                        <a href="rehabilitacion.php?id=<?php echo $idColegiado; ?>" class="btn btn-info btn-block" role="button">Rehabilitación de matrícula</a>
                        */
                        ?>
                      </p>
                    </div> 
                </div>
                <?php 
                }
                ?>
                <!--<div class="card" style="width: 18rem;">
                    <div class="card-body">
                      <h5 class="card-title">Tesorería</h5>
                      <p class="card-text">
                        <a href="cuotasColegiacion.php?id=<?php echo $hashColegiado; ?>" class="btn btn-info btn-block" role="button">Cuotas de colegiación</a>
                        <br>
                        <?php 
                        /*
                        <a href="pagosRegistrados.php?id=<?php echo $idColegiado; ?>" class="btn btn-info btn-block" role="button">Pagos registrados</a>
                        <br>
                        */
                        if ($colegiado['estado_tesoreria']['codigoDeudor'] == 4) {
                        ?>
                            <a href="planDePagos.php?id=<?php echo $hashColegiado; ?>" class="btn btn-info btn-block" role="button">Plan de Pagos</a>
                            <br>
                        <?php 
                        }
                        /*
                        <a href="cursos.php?id=<?php echo $idColegiado; ?>" class="btn btn-info btn-block" role="button">Cursos</a>
                        <br>
                        <a href="debitoAutomatico.php?id=<?php echo $idColegiado; ?>" class="btn btn-info btn-block" role="button">Débito automático</a>
                        */
                        ?>
                      </p>
                    </div> 
                </div>-->
            </div>
        </div>
    <?php
    }
} else {
?>
    <div class="row">&nbsp;</div>
    <div class="row">
        <a href="logout.php" class="btn btn-dark">Volver</a>
    </div>
<?php
}
include("../html/footer.php");
?>
  </div>
<div id="ampliarSeguroModal" class="modal fade" role="dialog">
<div class="modal-dialog modal-lg">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header alert-info">
        <h5 class="modal-title" id="exampleModalLabel">Propuesta Adicionales de Seguros</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <p>
            Seguro de vida: (con condiciones especiales)<br>
            Edad de ejemplo: 45 años<br>
            Cobertura por muerte e incapacidad total y permanente: $90.000.000<br>
            Muerte por accidente: $180.000.000<br>
            Cuota mensual: $20.802,01 + IB<br>
            Agregando enfermedades graves: $5.000.000 – Cuota mensual $22.374 + IB<br>
            Ingreso hasta los 69 años – Vigencia hasta los 80 años<br>
            a los 20 años - $90.000.000<br>
            <hr>
        </p>
        <p>
            Lucro cesante – Edad 45 años – cada $100.000 Cuota $3.000
            <hr>
        </p>
        <p>
            Corto punzante – AP con gastos de farmacia<br>
            Cobertura de AP $15.000.000 – Coctel $1.500.000 – Cuota mensual $1.624,16
            <hr>
        </p>
        <p>
            Consultorio:<br>
            Incendio: edificio $54.000.000 – contenido $10.000.000<br>
            Robo contenido $1.000.000 - Cristales $200.000 – Responsabilidad civil $50.000.000 – Costo 12 x 9.090<br>
            Solo RC: cobertura $50.000.000 - $2.242,98 por mes – por todos $1.345,78
            <hr>
        </p>
        <p>
            En caso de estar interesado comunicarse con el Productor Asesor exclusivo:<br>
            Fernando Saenz Santa Maria : fdsantamaria@gmail.com<br>
            Chat Whatsapp : 1132723952
        </p>
        <?php
        /*
        <p>
            <h5>Confirme los datos de mail y teléfono para ser contactado.</h5>
            <hr>
        </p>
        <form id="ampliarSeguro" autocomplete="off" name="ampliarSeguro" method="POST" action="tramites_seguro_praxis_medica.php">
            <div class="row">
                <div class="col-md-8">
                    <label for="mail"><b>Correo electrónico </b></label>
                    <input type="text" name="mail" id="mail" value="<?php echo $mailRegistrado; ?>" readonly />
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <label for="tipoTelefono"><b>Teléfono de contacto </b></label>
                    <select class="form-control" id="tipoTelefono" name="tipoTelefono" required="">
                        <option value="MOVIL">Movil</option>
                        <option value="FIJO">Fijo</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="telefono"><b>Teléfono número *</b></label>
                    <input type="text" name="telefono" id="telefono" placeholder="Ej: 221 4454316" minlength="9" maxlength="12" required />
                </div>
            </div>
            <p>
                <hr>
                <h5>Una vez enviada la solicitud, se pondrá en contacto el productor de seguro</h5>
            </p>
            <div class="text-center">
                <button type="submit" class="btn btn-primary" >Enviar</button>
                <input type="hidden" name="matricula" id="matricula" value="<?php echo $matricula; ?>" />
            </div>
        </form>
        */
        ?>      
      </div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secundary" data-dismiss="modal">Cerrar</button>
      </div>

    </div>
</div>    
</div>        

</body>
</html>

<script>
// ocultar Mensaje
$(document).ready(function() {
    setTimeout(function() {
        $(".ocultarMensaje").fadeOut(1500);
    },3000);
}); 
</script>