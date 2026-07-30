<?php
require_once '../dataAccess/config.php';
require_once '../html/head.php';
require_once '../html/header.php';
require_once '../dataAccess/funcionesPhp.php';

function verificarToken($token, $claveSecreta)
{
    # La API en donde verificamos el token
    $url = "https://www.google.com/recaptcha/api/siteverify";
    # Los datos que enviamos a Google
    $datos = [
        "secret" => $claveSecreta,
        "response" => $token,
    ];
    // Crear opciones de la petición HTTP
    $opciones = array(
        "http" => array(
            "header" => "Content-type: application/x-www-form-urlencoded\r\n",
            "method" => "POST",
            "content" => http_build_query($datos), # Agregar el contenido definido antes
        ),
    );
    # Preparar petición
    $contexto = stream_context_create($opciones);
    # Hacerla
    $resultado = file_get_contents($url, false, $contexto);
    # Si hay problemas con la petición (por ejemplo, que no hay internet o algo así)
    # entonces se regresa false. Este NO es un problema con el captcha, sino con la conexión
    # al servidor de Google
    if ($resultado === false) {
        # Error haciendo petición
        return false;
    }

    # En caso de que no haya regresado false, decodificamos con JSON
    # https://parzibyte.me/blog/2018/12/26/codificar-decodificar-json-php/

    $resultado = json_decode($resultado);
    # La variable que nos interesa para saber si el usuario pasó o no la prueba
    # está en success
    $pruebaPasada = $resultado->success;
    # Regresamos ese valor, y listo (sí, ya sé que se podría regresar $resultado->success)
    return $pruebaPasada;
}

$continua = TRUE;

if (!isset($_POST["g-recaptcha-response"]) || empty($_POST["g-recaptcha-response"])) {
    echo "Debes completar el captcha";
    $continua = FALSE;
} else {
    $token = $_POST["g-recaptcha-response"];
    $verificado = verificarToken($token, CLAVE_SECRETA);
    # Si no ha pasado la prueba
    if (!$verificado) {
        echo "Captcha incorrecto";
        $continua = FALSE;
    }
}
?>

<div class="row">&nbsp;</div> 
<div class="col-md-12"><hr></div>

<?php
if ($continua && isset($_POST['matricula']) && isset($_POST['dni']) && isset($_POST['mail'])) {
    $matricula = $_POST['matricula'];
    $dni = $_POST['dni'];
    $mail = $_POST['mail'];

    ini_set('xdebug.var_display_max_depth', -1);
    ini_set('xdebug.var_display_max_children', -1);
    ini_set('xdebug.var_display_max_data', -1);
    set_time_limit(0);

    $ch = curl_init();
    
    if (ENV == "prod") {
        curl_setopt($ch, CURLOPT_URL, 'https://webservices.colmed1.com.ar/colegio/ws-colmed/usuario/validar-usuario.php?matricula='.urlencode($matricula).'&dni='.urlencode($dni).'&mail='.urlencode($mail));
    } else {
        curl_setopt($ch, CURLOPT_URL, 'https://www.colmed1.com/desarrollo/colegio/ws-colmed/usuario/validar-usuario.php?matricula='.urlencode($matricula).'&dni='.urlencode($dni).'&mail='.urlencode($mail));
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
    ?>
        <div class="row alert alert-danger">
            <div class="col-md-12 text-left">Disculpe las molestias. Momentaneamente fuera de servicio, intente m&aacute;s tarde</div>
        </div>
    <?php
        //echo "cURL Error #:" . $err;
    } else {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //var_dump($http_code);
        //echo 'prueba';
      switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
        case 200:  
            $rta=(json_decode($respuesta,true));
            //var_dump($rta);
            $respuesta = $rta['respuesta'];
            if ($respuesta['codigo'] == 1) {
                $_SESSION['matricula'] = $matricula;
                $_SESSION['user_id'] = $matricula;
                $_SESSION['user_mac'] = $_SERVER['HTTP_USER_AGENT'];
                $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['private'] = blow_crypt("C9l1n3s9s39m9", 4);
                $_SESSION['private_alternative'] = $_SESSION['private'];
                $_SESSION['user_entidad'] = array("matricula" => $matricula, "dni" => $dni, 'mail' => $mail);
                $_SESSION['user_last_activity'] = time();

                //se redirige a tramites
                ?>
                <body onLoad="document.forms['myForm'].submit()">
                    <form name="myForm"  method="POST" action="tramites.php">
                        <input type="hidden"  name="matricula" id="matricula" value="<?php echo htmlspecialchars($matricula, ENT_QUOTES, 'UTF-8'); ?>">
                    </form>
                </body>
            <?php  
            } else {
            ?>
                <h4 style="color: red;">Usuario NO V&Aacute;LIDO - <?php echo htmlspecialchars($respuesta['mensaje'], ENT_QUOTES, 'UTF-8'); ?></h4>
            <?php
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
                    <h3>Mail registrado: <?php echo htmlspecialchars($mailMostrar, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <div class="col-md-4">
                        <h4 class="alert alert-info">Si el mail registrado no coincide con su mail actual, debe acercarse a la Mesa de Entradas del Colegio de Médicos para realizar el trámite de actualización de datos.</h4>
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
            break;

        case 400:  
            $rta=(json_decode($respuesta,true));
            var_dump($rta);
            break;

        default:
            echo 'Codigo HTTP inesperado: '.$http_code."<br>";
            break;
        }

    } 
    exit;
} else {
?>
     <h3>INGRESO INCORRECTO</h3>
    <div class="col-md-4">
        <a href="login.php" class="btn btn-info" role="button">Volver</a>
    </div>
<?php
}
include("../html/footer.php");
?>
  </div>

</body>
</html>