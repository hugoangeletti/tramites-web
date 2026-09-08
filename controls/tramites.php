<?php
require_once '../dataAccess/config.php';
permisoLogueado();
require_once '../html/head.php';
require_once '../html/header.php';
require_once '../dataAccess/funcionesPhp.php';

$matricula = $_SESSION['matricula'];
$continua = TRUE;
if ($continua) {
    //obtener los datos del colegiDO
    ini_set('xdebug.var_display_max_depth', -1);
    ini_set('xdebug.var_display_max_children', -1);
    ini_set('xdebug.var_display_max_data', -1);
    set_time_limit(0);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, URL_WS.'/colegiado/buscar_colegiado.php?matricula='.$matricula);
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
            <div class="col-md-12 text-left">Disculpe las molestias. Momentaneamente fuera de servicio, intente m&aacute;s tarde - <?php var_dump($err) ?></div>
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
                    $_SESSION['sexo'] = $colegiado['sexo'];
                    $_SESSION['tipoEstado'] = $colegiado['tipoEstado'];
                    $_SESSION['detalleMovimiento'] = isset($colegiado['detalleMovimiento']) ? $colegiado['detalleMovimiento'] : '';
                    $_SESSION['estado_tesoreria_codigo'] = $colegiado['estado_tesoreria']['codigoDeudor'];
                    $_SESSION['estado_tesoreria_leyenda'] = $colegiado['estado_tesoreria']['leyenda'];
                    $idColegiado = $_SESSION['idColegiado'];
                    $hashColegiado = $_SESSION['hashColegiado'];
                    $conSeguro = $colegiado['conSeguro'];
                    $_SESSION['conSeguro'] = $conSeguro;
                    $mailRegistrado = $_SESSION['user_entidad']['mail'];
                    if (isset($conSeguro) && $conSeguro <> '') {
                        $conTramites = TRUE;
                    } else {
                        $conTramites = FALSE;
                    }
                    $tieneCurso = $colegiado['tieneCurso'];
                    $_SESSION['tieneCurso'] = $tieneCurso;

                    $especialidades = isset($respuesta['especialidades']) && is_array($respuesta['especialidades']) ? $respuesta['especialidades'] : array();
                    $_SESSION['especialidades'] = $especialidades;
                    $_SESSION['tieneEspecialidades'] = sizeof($especialidades) > 0;

                    $intencionPagoPendiente = armarIntencionPagoPendiente($respuesta);
                    if ($intencionPagoPendiente !== NULL) {
                        $_SESSION['intencionPagoPendiente'] = $intencionPagoPendiente;
                    } else {
                        unset($_SESSION['intencionPagoPendiente']);
                    }

                    // Todas las intenciones de pago vigentes del colegiado (cualquier
                    // concepto), para armar el resumen de "pago pendiente" de esta pantalla.
                    // Ojo: esta clave es la misma ("intencion_pago") que usan otros endpoints
                    // para un único registro; acá buscar_colegiado.php la devuelve como array.
                    $intencionesPagoTramites = (isset($respuesta['intencion_pago']) && is_array($respuesta['intencion_pago']))
                        ? $respuesta['intencion_pago']
                        : array();
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
                        <h5>Momentaneamente fuera de servicio, vuelva a intentar más tarde..</a>
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
        ?>
        <div class="col-md-12"><hr></div>
        <?php
        if (isset($_POST['mensaje']) && $_POST['mensaje'] <> "OK") {
        ?>
            <div class="col-md-12">
                <div class="<?php echo $_POST['clase'];?> alert-dismissible fade show" role="alert">
                    <?php echo $_POST['mensaje'];?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
        <?php
        }
        ?>
        <div class="col-md-12">
            <?php require_once '../html/menuTramites.php'; ?>
            <?php
            // Se agrupan por concepto (y por curso puntual, si son de distintos cursos)
            // para mostrar un solo aviso aunque haya varias intenciones aprobadas juntas.
            $gruposIntencionesTramites = array();
            foreach ($intencionesPagoTramites as $intencionTramites) {
                $estadoIntencionTramites = isset($intencionTramites['Estado']) ? strtolower(trim($intencionTramites['Estado'])) : '';
                $hashIntencionTramites = isset($intencionTramites['Hash']) ? $intencionTramites['Hash'] : '';
                $fechaInicioTramites = isset($intencionTramites['FechaInicio']) ? $intencionTramites['FechaInicio'] : '';

                // "iniciada"/"enviada" no se muestran acá (el colegiado las ve y las
                // completa/anula desde la pantalla puntual de cada concepto). Si quedaron
                // así hace más de 5 minutos sin resultado de Gire, se anulan solas para
                // no dejar la deuda trabada indefinidamente.
                if ($estadoIntencionTramites == 'iniciada' || $estadoIntencionTramites == 'enviada') {
                    if ($hashIntencionTramites <> '' && $fechaInicioTramites <> '' && (time() - strtotime($fechaInicioTramites)) > 300) {
                        anularIntencionPagoWs($hashIntencionTramites);
                    }
                    continue;
                }
                // Solo interesa avisar sobre las aprobadas: todavía resta que Gire
                // informe el lote de cobranza para que se acredite el pago.
                if ($estadoIntencionTramites <> 'aprobada') { continue; }

                $conceptoTramites = isset($intencionTramites['Concepto']) ? $intencionTramites['Concepto'] : '';
                $totalTramites = isset($intencionTramites['TotalPago']) ? $intencionTramites['TotalPago'] : 0;
                if ($conceptoTramites == 'plan_pago') {
                    $claveGrupoTramites = 'plan_pago';
                    $conceptoTextoTramites = 'de su plan de pagos';
                    $urlVerDetalleTramites = 'planDePagos.php?id=' . $hashColegiado;
                } else if ($conceptoTramites == 'cursos') {
                    $idAsistenteTramites = isset($intencionTramites['IdAsistente']) ? $intencionTramites['IdAsistente'] : '';
                    $claveGrupoTramites = 'cursos_' . $idAsistenteTramites;
                    $conceptoTextoTramites = 'de cuotas de curso';
                    $urlVerDetalleTramites = 'cuotas_curso.php?id=' . $hashColegiado . '&reg=' . $idAsistenteTramites;
                } else if ($conceptoTramites == 'colegiacion') {
                    $claveGrupoTramites = 'colegiacion';
                    $conceptoTextoTramites = 'de sus cuotas de colegiaci&oacute;n';
                    $urlVerDetalleTramites = 'cuotas.php?id=' . $hashColegiado;
                } else {
                    continue;
                }

                if (!isset($gruposIntencionesTramites[$claveGrupoTramites])) {
                    $gruposIntencionesTramites[$claveGrupoTramites] = array(
                        'texto'    => $conceptoTextoTramites,
                        'url'      => $urlVerDetalleTramites,
                        'total'    => 0,
                        'cantidad' => 0,
                    );
                }
                $gruposIntencionesTramites[$claveGrupoTramites]['total'] += $totalTramites;
                $gruposIntencionesTramites[$claveGrupoTramites]['cantidad']++;
            }
            ?>
            <?php foreach ($gruposIntencionesTramites as $grupoTramites) { ?>
                <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap">
                    <?php if ($grupoTramites['cantidad'] > 1) { ?>
                        <span>Tiene <?php echo $grupoTramites['cantidad']; ?> pagos <?php echo $grupoTramites['texto']; ?> por un total de <b>$<?php echo number_format($grupoTramites['total'], 0, ',', '.'); ?></b> pendientes de acreditaci&oacute;n.</span>
                    <?php } else { ?>
                        <span>Tiene un pago <?php echo $grupoTramites['texto']; ?> de <b>$<?php echo number_format($grupoTramites['total'], 0, ',', '.'); ?></b> pendiente de acreditaci&oacute;n.</span>
                    <?php } ?>
                    <a href="<?php echo $grupoTramites['url']; ?>" class="btn btn-warning btn-sm">Ver detalle</a>
                </div>
            <?php } ?>
            <div class="card drive-card drive-banner mb-4">
                <div class="card-body">
                    <h5 class="drive-heading">Tr&aacute;mites disponibles</h5>
                    <p class="text-muted">Desde el men&uacute; de la izquierda puede acceder a:</p>
                    <ul class="mb-0">
                        <?php if ($permiteCertificado) { ?>
                            <li>Solicitar un certificado.</li>
                        <?php } ?>
                        <li>
                            Consultar y pagar sus cuotas de colegiaci&oacute;n<?php if (in_array($estadoTesoreriaCodigo, array(4, 5, 6, 7))) { ?> o adherirse a un plan de pagos<?php } ?>.
                        </li>
                        <li>
                            Inscribirse a cursos ESEM<?php if ($tieneCurso) { ?> o consultar los cursos en los que ya se encuentra inscripto<?php } ?>.
                        </li>
                        <li>Actualizar su domicilio particular y sus datos de contacto.</li>
                        <?php if ($conTramites) { ?>
                            <li>
                                <?php if ($conSeguro == 'ASEGURADO_COLEGIO') { ?>
                                    Imprimir su certificado de cobertura o solicitar una ampliaci&oacute;n del seguro de praxis m&eacute;dica.
                                <?php } else { ?>
                                    Consultar su situaci&oacute;n de seguro de praxis m&eacute;dica.
                                <?php } ?>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
            <?php require_once '../html/menuTramitesClose.php'; ?>
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

</body>
</html>