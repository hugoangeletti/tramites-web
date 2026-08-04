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

    //obtener los datos del colegiDO
    ini_set('xdebug.var_display_max_depth', -1);
    ini_set('xdebug.var_display_max_children', -1);
    ini_set('xdebug.var_display_max_data', -1);
    set_time_limit(0);

    $ch = curl_init();

    if (ENV == "prod") {
        curl_setopt($ch, CURLOPT_URL, 'http://webservices.colmed1.com.ar/colegio/ws-colmed/colegiado/buscar_domicilio_real.php?idColegiado='.$idColegiado);
    } else {
        curl_setopt($ch, CURLOPT_URL, 'http://www.colmed1.com/desarrollo/colegio/ws-colmed/colegiado/buscar_domicilio_real.php?idColegiado='.$idColegiado);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

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
    } else {
      switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
        case 200:
            $rta = (json_decode($respuesta, true));
            if (isset($rta) && isset($rta['respuesta'])) {
                $respuesta = $rta['respuesta'];
                if ($respuesta['codigo'] == 1) {
                    $domicilio = $respuesta['datos'];
                    $partidos = isset($respuesta['partidos']) ? $respuesta['partidos'] : array();
                    // buscar_domicilio_real.php ya trae TODAS las localidades (de todos los
                    // partidos, campo "idPartido" en cada una) junto con el domicilio y los
                    // partidos. No hace falta pedirlas de nuevo al servidor: se filtran en
                    // PHP para el <select> inicial, y en JS cuando el usuario cambia el Partido.
                    $todasLasLocalidades = isset($respuesta['localidades']) ? $respuesta['localidades'] : array();
                    $idZonaActual = isset($domicilio['idZona']) ? $domicilio['idZona'] : '';
                    $localidades = array();
                    foreach ($todasLasLocalidades as $localidad) {
                        if (isset($localidad['idPartido']) && $idZonaActual !== '' && $localidad['idPartido'] == $idZonaActual) {
                            $localidades[] = $localidad;
                        }
                    }
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
            $rta = (json_decode($respuesta, true));
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
    // $idZonaActual, $partidos y $localidades (ya filtradas al partido actual) se
    // calcularon arriba. El resto de las localidades ($todasLasLocalidades) se filtra
    // en JS cuando el usuario cambia el Partido, sin volver a pedirle nada al servidor.

    // si venimos de un error en enviar_actualizacion_domicilio.php, precargamos el
    // formulario con lo que el colegiado había escrito, en vez de lo que vino del WS
    $localidadTextoPost = '';
    if (isset($_POST['calle'])) {
        $domicilio['calle'] = $_POST['calle'];
        $domicilio['numero'] = isset($_POST['numero']) ? $_POST['numero'] : '';
        $domicilio['piso'] = isset($_POST['piso']) ? $_POST['piso'] : '';
        $domicilio['depto'] = isset($_POST['departamento']) ? $_POST['departamento'] : '';
        $domicilio['lateral'] = isset($_POST['laterales']) ? $_POST['laterales'] : '';
        $domicilio['codigoPostal'] = isset($_POST['codigoPostal']) ? $_POST['codigoPostal'] : '';
        $domicilio['idLocalidad'] = isset($_POST['idLocalidad']) ? $_POST['idLocalidad'] : '';
        $localidadTextoPost = isset($_POST['localidadTexto']) ? $_POST['localidadTexto'] : '';

        $idZonaActual = isset($_POST['idZona']) ? $_POST['idZona'] : '';
        $localidades = array();
        foreach ($todasLasLocalidades as $localidad) {
            if (isset($localidad['idPartido']) && $idZonaActual !== '' && $localidad['idPartido'] == $idZonaActual) {
                $localidades[] = $localidad;
            }
        }
    }

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
                <h4 class="mb-3">Actualizar domicilio particular</h4>
                <form method="POST" action="enviar_actualizacion_domicilio.php?id=<?php echo $hashColegiado; ?>" onsubmit="return confirm('&iquest;Confirma que desea guardar los cambios en su domicilio particular?');">
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label for="calle">Calle</label>
                            <input type="text" class="form-control" id="calle" name="calle" value="<?php echo htmlspecialchars(isset($domicilio['calle']) ? $domicilio['calle'] : '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="numero">N&uacute;mero</label>
                            <input type="text" class="form-control" id="numero" name="numero" value="<?php echo htmlspecialchars(isset($domicilio['numero']) ? $domicilio['numero'] : '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="piso">Piso</label>
                            <input type="text" class="form-control" id="piso" name="piso" value="<?php echo htmlspecialchars(isset($domicilio['piso']) ? $domicilio['piso'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="departamento">Departamento</label>
                            <input type="text" class="form-control" id="departamento" name="departamento" value="<?php echo htmlspecialchars(isset($domicilio['depto']) ? $domicilio['depto'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="laterales">Laterales</label>
                            <input type="text" class="form-control" id="laterales" name="laterales" value="<?php echo htmlspecialchars(isset($domicilio['lateral']) ? $domicilio['lateral'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5 form-group">
                            <label for="idZona">Partido</label>
                            <select class="form-control" id="idZona" name="idZona" required>
                                <option value="">Seleccione Partido</option>
                                <?php foreach ($partidos as $partido) { ?>
                                    <option value="<?php echo $partido['id']; ?>" <?php if ($idZonaActual <> '' && $idZonaActual == $partido['id']) { echo 'selected'; } ?>><?php echo $partido['nombre']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-5 form-group">
                            <label for="idLocalidad">Localidad</label>
                            <select class="form-control" id="idLocalidad" name="idLocalidad">
                                <option value="">Seleccione Localidad</option>
                                <?php foreach ($localidades as $localidad) { ?>
                                    <option value="<?php echo $localidad['id']; ?>" <?php if (isset($domicilio['idLocalidad']) && $domicilio['idLocalidad'] == $localidad['id']) { echo 'selected'; } ?>><?php echo $localidad['nombre']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-2 form-group">
                            <label for="codigoPostal">C&oacute;digo Postal</label>
                            <input type="text" class="form-control" id="codigoPostal" name="codigoPostal" value="<?php echo htmlspecialchars(isset($domicilio['codigoPostal']) ? $domicilio['codigoPostal'] : '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    <div class="row" id="filaLocalidadTexto" style="display: <?php echo ($idZonaActual == 8) ? 'flex' : 'none'; ?>;">
                        <div class="col-md-12 form-group">
                            <label for="localidadTexto">Si no encuentra la localidad en el listado, debe ingresarla aqu&iacute; y se le confirmar&aacute; desde el Colegio</label>
                            <input type="text" class="form-control" id="localidadTexto" name="localidadTexto" value="<?php echo htmlspecialchars($localidadTextoPost, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <div class="text-right">
                        <a href="tramites.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-info">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        var todasLasLocalidades = <?php echo json_encode($todasLasLocalidades); ?>;
        $(document).ready(function() {
            $('#idZona').on('change', function() {
                var idZona = $(this).val();
                var $localidad = $('#idLocalidad');

                // "OTRAS LOCALIDADES" (id 8): se habilita el campo de texto libre
                $('#filaLocalidadTexto').css('display', (idZona == '8') ? 'flex' : 'none');
                if (idZona != '8') {
                    $('#localidadTexto').val('');
                }

                if (!idZona) {
                    $localidad.html('<option value="">Seleccione Partido primero</option>');
                    return;
                }
                var opciones = '<option value="">Seleccione Localidad</option>';
                todasLasLocalidades.forEach(function(loc) {
                    if (String(loc.idPartido) === String(idZona)) {
                        opciones += '<option value="' + loc.id + '">' + loc.nombre + '</option>';
                    }
                });
                $localidad.html(opciones);
            });
        });
        </script>
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
