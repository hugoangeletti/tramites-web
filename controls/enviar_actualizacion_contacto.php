<?php
require_once '../dataAccess/config.php';
permisoLogueado();
require_once '../html/head.php';
require_once '../html/header.php';
require_once '../dataAccess/funcionesPhp.php';
require_once '../html/menuTramites.php';

$continuar = true;
$mensaje = "";
if (isset($_GET['id']) && $_GET['id'] == $_SESSION['hashColegiado']) {
    $idColegiado = $_SESSION['idColegiado'];
    $hashColegiado = $_SESSION['hashColegiado'];

    $telefonoFijo = isset($_POST['telefonoFijo']) ? trim($_POST['telefonoFijo']) : '';
    $telefonoMovil = isset($_POST['telefonoMovil']) ? trim($_POST['telefonoMovil']) : '';
    $correoElectronico = isset($_POST['correoElectronico']) ? trim($_POST['correoElectronico']) : '';

    if ($correoElectronico == '') {
        $continuar = FALSE;
        $mensaje .= 'Debe ingresar el correo electr&oacute;nico - ';
    } else if (!filter_var($correoElectronico, FILTER_VALIDATE_EMAIL)) {
        $continuar = FALSE;
        $mensaje .= 'El correo electr&oacute;nico ingresado no es v&aacute;lido - ';
    }
    if ($telefonoMovil == '') {
        $continuar = FALSE;
        $mensaje .= 'Debe ingresar el tel&eacute;fono m&oacute;vil - ';
    } else if (!validarCelularArgentino($telefonoMovil)) {
        $continuar = FALSE;
        $mensaje .= 'El tel&eacute;fono m&oacute;vil ingresado no es un n&uacute;mero argentino v&aacute;lido - ';
    }

    if ($continuar) {
        $r = llamarWs(URL_WS.'/colegiado/actualizar_datos_contacto.php', 'POST', array(
            "idColegiado" => $idColegiado,
            "telefonoFijo" => $telefonoFijo,
            "telefonoMovil" => $telefonoMovil,
            "correoElectronico" => $correoElectronico
        ));

        if (!$r['ok']) {
            $continuar = FALSE;
            $mensaje .= ($r['codigo'] === null) ? "No se pudieron actualizar los datos de contacto" : $r['mensaje'];
        }
    }
} else {
    $continuar = FALSE;
    $mensaje = "ERROR AL INGRESAR";
}
if ($continuar) {
    redirigirConMensaje('tramites.php', 'Los datos de contacto se actualizaron correctamente.', 'alert alert-success');
} else if (isset($hashColegiado) && $hashColegiado <> "") {
    // volvemos a actualizar_contacto.php reenviando por POST lo que el colegiado
    // había escrito, para que no tenga que volver a cargar todo el formulario
    redirigirConMensaje('actualizar_contacto.php?id='.$hashColegiado, $mensaje, 'alert alert-danger', array(
        'telefonoFijo' => $telefonoFijo,
        'telefonoMovil' => $telefonoMovil,
        'correoElectronico' => $correoElectronico,
    ));
} else {
?>
    <div class="row alert alert-danger">
        <div class="col-md-10">
            <h4 class=""><b><?php echo $mensaje; ?></b></h4>
        </div>
        <div class="col-md-2">
            <a href="tramites.php" class="btn btn-primary">Volver</a>
        </div>
    </div>
<?php
    require_once "../html/menuTramitesClose.php";
    include("../html/footer.php");
    ?>
</div>

</body>
<?php
}
