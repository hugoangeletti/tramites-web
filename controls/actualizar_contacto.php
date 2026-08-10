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

    curl_setopt($ch, CURLOPT_URL, URL_WS.'/colegiado/buscar_datos_contacto.php?idColegiado='.$idColegiado);
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
                    $contacto = $respuesta['datos'];
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
    // si venimos de un error en enviar_actualizacion_contacto.php, precargamos el
    // formulario con lo que el colegiado había escrito, en vez de lo que vino del WS
    if (isset($_POST['correoElectronico'])) {
        $contacto['telefonoFijo'] = isset($_POST['telefonoFijo']) ? $_POST['telefonoFijo'] : '';
        $contacto['telefonoMovil'] = isset($_POST['telefonoMovil']) ? $_POST['telefonoMovil'] : '';
        $contacto['email'] = $_POST['correoElectronico'];
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
                <h4 class="mb-3">Actualizar datos de contacto</h4>
                <form method="POST" action="enviar_actualizacion_contacto.php?id=<?php echo $hashColegiado; ?>" onsubmit="return confirm('&iquest;Confirma que desea guardar los cambios en sus datos de contacto?');">
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="telefonoFijo">Tel&eacute;fono fijo</label>
                            <input type="text" class="form-control" id="telefonoFijo" name="telefonoFijo" value="<?php echo htmlspecialchars(isset($contacto['telefonoFijo']) ? $contacto['telefonoFijo'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="telefonoMovil">Tel&eacute;fono m&oacute;vil</label>
                            <input type="text" class="form-control" id="telefonoMovil" name="telefonoMovil" placeholder="Ej: 11 1234-5678" value="<?php echo htmlspecialchars(isset($contacto['telefonoMovil']) ? $contacto['telefonoMovil'] : '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="correoElectronico">Correo electr&oacute;nico</label>
                            <input type="email" class="form-control" id="correoElectronico" name="correoElectronico" value="<?php echo htmlspecialchars(isset($contacto['email']) ? $contacto['email'] : '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    <div class="text-right">
                        <a href="tramites.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-info">Guardar</button>
                    </div>
                </form>
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
