<?php

/*
 * Función de sanearDatos por posibles ataques XSS
 */

function sanearDatos($tags) {
    $tags = strip_tags($tags);
    $tags = stripslashes($tags);
    //$tags = htmlentities($tags);
    return $tags;
}

/*
 * Función de encriptación aleatoria, cada vez que se ejecuta.
 */

function blow_crypt($input, $rounds = 7) {
    $salt = "";
    $salt_chars = array_merge(range('A', 'Z'), range('a', 'z'), range(0, 9));
    for ($i = 0; $i < 22; $i++) {
        $salt .= $salt_chars[array_rand($salt_chars)];
    }
    return crypt($input, sprintf('$2a$%02d$', $rounds) . $salt);
}

/*
 * Función de triple encriptación para contraseñas y usuarios seguros.
 */

function hashData($string) {
    $string = hash("haval224,4", md5(crypt($string, "$2a$%02d$")));
    return $string;
}

/*
 * Función para validación correcta de fecha, en base al un formato
 * pasado por parámetro o por defecto Y-m-d
 */

function validateDate($date, $format = 'Y-m-d H:i:s') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function urlAmigable($string, $replacement = '-', $map = array()) {
    if (is_array($replacement)) {
        $map = $replacement;
        $replacement = '+';
    }
    $quotedReplacement = preg_quote($replacement, '/');

    $merge = array(
        '/[^\s\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]/mu' => ' ',
        '/\\s+/' => $replacement,
        sprintf('/^[%s]+|[%s]+$/', $quotedReplacement, $quotedReplacement) => '',
    );

    $_transliteration = array(
        '/ä|æ|ǽ/' => 'ae',
        '/ö|œ/' => 'oe',
        '/ü/' => 'ue',
        '/Ä/' => 'Ae',
        '/Ü/' => 'Ue',
        '/Ö/' => 'Oe',
        '/À|Á|Â|Ã|Ä|Å|Ǻ|Ā|Ă|Ą|Ǎ/' => 'A',
        '/à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª/' => 'a',
        '/Ç|Ć|Ĉ|Ċ|Č/' => 'C',
        '/ç|ć|ĉ|ċ|č/' => 'c',
        '/Ð|Ď|Đ/' => 'D',
        '/ð|ď|đ/' => 'd',
        '/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě/' => 'E',
        '/è|é|ê|ë|ē|ĕ|ė|ę|ě/' => 'e',
        '/Ĝ|Ğ|Ġ|Ģ/' => 'G',
        '/ĝ|ğ|ġ|ģ/' => 'g',
        '/Ĥ|Ħ/' => 'H',
        '/ĥ|ħ/' => 'h',
        '/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ/' => 'I',
        '/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı/' => 'i',
        '/Ĵ/' => 'J',
        '/ĵ/' => 'j',
        '/Ķ/' => 'K',
        '/ķ/' => 'k',
        '/Ĺ|Ļ|Ľ|Ŀ|Ł/' => 'L',
        '/ĺ|ļ|ľ|ŀ|ł/' => 'l',
        '/Ñ|Ń|Ņ|Ň/' => 'N',
        '/ñ|ń|ņ|ň|ŉ/' => 'n',
        '/Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ/' => 'O',
        '/ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º/' => 'o',
        '/Ŕ|Ŗ|Ř/' => 'R',
        '/ŕ|ŗ|ř/' => 'r',
        '/Ś|Ŝ|Ş|Š/' => 'S',
        '/ś|ŝ|ş|š|ſ/' => 's',
        '/Ţ|Ť|Ŧ/' => 'T',
        '/ţ|ť|ŧ/' => 't',
        '/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ/' => 'U',
        '/ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ/' => 'u',
        '/Ý|Ÿ|Ŷ/' => 'Y',
        '/ý|ÿ|ŷ/' => 'y',
        '/Ŵ/' => 'W',
        '/ŵ/' => 'w',
        '/Ź|Ż|Ž/' => 'Z',
        '/ź|ż|ž/' => 'z',
        '/Æ|Ǽ/' => 'AE',
        '/ß/' => 'ss',
        '/Ĳ/' => 'IJ',
        '/ĳ/' => 'ij',
        '/Œ/' => 'OE',
        '/ƒ/' => 'f'
    );

    $map = $map + $_transliteration + $merge;
    return preg_replace(array_keys($map), array_values($map), $string);
}

function invertirFecha($fecha) {
    // Invierte las que vienen con el formato dd-mm-aaaa
    $fechaInvertir = explode("-", $fecha);
    $fechaInvertida = $fechaInvertir[2] . "-" . $fechaInvertir[1] . "-" . $fechaInvertir[0];
    return $fechaInvertida;
}

function dateadd($date, $dd = 0, $mm = 0, $yy = 0, $hh = 0, $mn = 0, $ss = 0) {
    $date_r = getdate(strtotime($date));

    $date_result = date("Y-m-d h:i:s", mktime(($date_r["hours"] + $hh), ($date_r["minutes"] + $mn), ($date_r["seconds"] + $ss), ($date_r["mon"] + $mm), ($date_r["mday"] + $dd), ($date_r["year"] + $yy)));

    return $date_result;
}

function sumarRestarSobreFecha($fecha, $cant, $tipo = 'day', $accion = '+') {
    $nuevafecha = strtotime($accion . $cant . ' ' . $tipo, strtotime($fecha));
    $nuevafecha = date('Y-m-d', $nuevafecha);

    return $nuevafecha;
}

function encriptarPass($pass) 
{

    $pass_encriptada1 = md5($pass); //Encriptacion nivel 1
    $pass_encriptada2 = crc32($pass_encriptada1); //Encriptacion nivel 1
    $pass_encriptada3 = crypt($pass_encriptada2, "xtemp"); //Encriptacion nivel 2
    $pass_encriptada4 = sha1("xtemp" . $pass_encriptada3); //Encriptacion nivel 3
    return $pass_encriptada4;
}

function validar_clave($clave, &$error_clave) {
    if (strlen($clave) < 8) {
        $error_clave = "La clave debe tener al menos 8 caracteres.";
        return false;
    }
    if (strlen($clave) > 16) {
        $error_clave = "La clave no puede tener más de 16 caracteres.";
        return false;
    }
    if (preg_match('`[\?\¿\¡\'\"\!\ª\º\%\|\@\#\·\$\~\€\¬\&\/\(\)\=\[\]\}\{\.\:\,\;\<\>\^\`\+\*\¨\ç\Ç]`', $clave)) {
        $error_clave = "La clave que está queriendo ingresar contiene caracteres que no son permitidos.";
        return false;
    }
    if (!preg_match('`[a-z]`', $clave)) {
        $error_clave = "La clave debe tener al menos una letra minúscula.";
        return false;
    }
    if (!preg_match('`[A-Z]`', $clave)) {
        $error_clave = "La clave debe tener al menos una letra mayúscula.";
        return false;
    }
    if (!preg_match('`[0-9]`', $clave)) {
        $error_clave = "La clave debe tener al menos un caracter numérico.";
        return false;
    }
    if (!preg_match('`[\-\_]`', $clave)) {
        $error_clave = "La clave debe tener al menos un caracter especial.";
        return false;
    }

    $error_clave = "";
    return true;
}

function validarCelularArgentino($telefono) {
    // se queda solo con los dígitos
    $digitos = preg_replace('/\D/', '', $telefono);

    // saca el código de país (54) si vino incluido
    if (substr($digitos, 0, 2) === '54') {
        $digitos = substr($digitos, 2);
    }
    // saca el 9 de celular si vino incluido
    if (substr($digitos, 0, 1) === '9') {
        $digitos = substr($digitos, 1);
    }
    // saca el 0 de larga distancia si vino incluido
    if (substr($digitos, 0, 1) === '0') {
        $digitos = substr($digitos, 1);
    }

    // código de área (2 a 4 dígitos) + número (6 a 8 dígitos) = 10 dígitos en total
    return preg_match('/^\d{10}$/', $digitos) === 1;
}

function rellenarCeros($entero, $largo) {
    // Limpiamos por si se encontraran errores de tipo en las variables
    $entero = (int) $entero;
    $largo = (int) $largo;

    $relleno = '';

    /**
     * Determinamos la cantidad de caracteres utilizados por $entero
     * Si este valor es mayor o igual que $largo, devolvemos el $entero
     * De lo contrario, rellenamos con ceros a la izquierda del número
     * */
    if (strlen($entero) < $largo) {
        $relleno = str_pad((int) $entero, $largo, "0", STR_PAD_LEFT);
        return $relleno;
    }
    return $relleno . $entero;
}

function cambiarFechaaformatoBD($fecha)
{ // pasa de dd/mm/AAAA a AAAA-mm-dd

    if ($fecha!="")
    {
        $fechaaux=explode('/',$fecha);
        $fecha=$fechaaux[2]."-".$fechaaux[1]."-".$fechaaux[0];

    }
    else
    {
        $fecha='0000-00-00';
    }
    return $fecha;
}

function cambiarFechaFormatoParaMostrar($fecha)
{ // pasa del AAAA-mm-dd a dd/mm/AAAA

if ($fecha!="" && $fecha !="0000-00-00")
{
$fechaaux=explode('-',$fecha);
$fecha=$fechaaux[2]."/".$fechaaux[1]."/".$fechaaux[0];

}
else
{
    $fecha="";
}
return $fecha;
}

/*
function rand_str($length =10, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')
{
    // Length of character list
    $chars_length = (strlen($chars) - 1);

    // Start our string
    $string = $chars{rand(0, $chars_length)};
    
    // Generate random string
    for ($i = 1; $i < $length; $i = strlen($string))
    {
        // Grab a random character from our list
        $r = $chars{rand(0, $chars_length)};
        
        // Make sure the same two characters don't appear next to each other
        if ($r != $string{$i - 1}) $string .=  $r;
    }
    
    // Return the string
    return $string;
}
*/

function NombreDeLaSemana($diaSemana){
    switch ($diaSemana) {
        case 0:
            $diaSemanaName = 'Domingo';
            break;
        case 1:
            $diaSemanaName = 'Lunes';
            break;
        case 2:
            $diaSemanaName = 'Martes';
            break;
        case 3:
            $diaSemanaName = 'Miércoles';
            break;
        case 4:
            $diaSemanaName = 'Jueves';
            break;
        case 5:
            $diaSemanaName = 'Viernes';
            break;
        case 6:
            $diaSemanaName = 'Sábado';
            break;

        default:
            $diaSemanaName = $diaSemana;
            break;
        
    }
    
    return $diaSemanaName;
}


function calcular_edad($fecha_nac)
{
    if ($fecha_nac!="")
    {    
    
	$dia=date("j");
	$mes=date("n");
	$anno=date("Y");

	//descomponer fecha de nacimiento
	$dia_nac=substr($fecha_nac, 8, 2);
	$mes_nac=substr($fecha_nac, 5, 2);
	$anno_nac=substr($fecha_nac, 0, 4);

	if($mes_nac>$mes){
		$calc_edad= $anno-$anno_nac-1;
	}else{
		if($mes==$mes_nac AND $dia_nac>$dia){
			$calc_edad= $anno-$anno_nac-1;  
		}else{
			$calc_edad= $anno-$anno_nac;
		}
	}
	return $calc_edad. " A&ntilde;os";
    }
    else {
     return "";   
    }
}

function getRealIP()
{
   
   if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '' )
   {
      $client_ip =
         ( !empty($_SERVER['REMOTE_ADDR']) ) ?
            $_SERVER['REMOTE_ADDR']
            :
            ( ( !empty($_ENV['REMOTE_ADDR']) ) ?
               $_ENV['REMOTE_ADDR']
               :
               "unknown" );
   
      // los proxys van a�adiendo al final de esta cabecera
      // las direcciones ip que van "ocultando". Para localizar la ip real
      // del usuario se comienza a mirar por el principio hasta encontrar
      // una direcci�n ip que no sea del rango privado. En caso de no
      // encontrarse ninguna se toma como valor el REMOTE_ADDR
   
      $entries = split('[, ]', $_SERVER['HTTP_X_FORWARDED_FOR']);
   
      reset($entries);
      while (list(, $entry) = each($entries))
      {
         $entry = trim($entry);
         if ( preg_match("/^([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/", $entry, $ip_list) )
         {
            // http://www.faqs.org/rfcs/rfc1918.html
            $private_ip = array(
                  '/^0\./',
                  '/^127\.0\.0\.1/',
                  '/^192\.168\..*/',
                  '/^172\.((1[6-9])|(2[0-9])|(3[0-1]))\..*/',
                  '/^10\..*/');
   
            $found_ip = preg_replace($private_ip, $client_ip, $ip_list[1]);
   
            if ($client_ip != $found_ip)
            {
               $client_ip = $found_ip;
               break;
            }
         }
      }
   }
   else
   {
      $client_ip =
         ( !empty($_SERVER['REMOTE_ADDR']) ) ?
            $_SERVER['REMOTE_ADDR']
            :
            ( ( !empty($_ENV['REMOTE_ADDR']) ) ?
               $_ENV['REMOTE_ADDR']
               :
               "unknown" );
   }
   
   return $client_ip;

}

/**
 * Normaliza el nodo "intencion_pago" que devuelve el WS y arma el arreglo que se
 * guarda en sesión. Contempla las dos formas en que puede llegar: plana
 * (Hash, TotalPago, Cuotas, Enviada) o envuelta en estado/datos.
 * Devuelve NULL cuando no hay intención de pago pendiente.
 */
function armarIntencionPagoPendiente($respuestaWs)
{
    if (!isset($respuestaWs['intencion_pago']) || !is_array($respuestaWs['intencion_pago'])) {
        return NULL;
    }

    $nodo = $respuestaWs['intencion_pago'];

    // Forma con envoltorio: {estado, mensaje, datos:{...}}
    if (isset($nodo['datos'])) {
        if (isset($nodo['estado']) && !$nodo['estado']) {
            return NULL;
        }
        $nodo = $nodo['datos'];
    }

    if (!is_array($nodo) || !isset($nodo['Hash']) || $nodo['Hash'] == '') {
        return NULL;
    }

    $cuotas = (isset($nodo['Cuotas']) && $nodo['Cuotas'] <> '')
        ? array_map('trim', explode(',', $nodo['Cuotas']))
        : array();

    return array(
        'hash'    => $nodo['Hash'],
        'total'   => isset($nodo['TotalPago']) ? $nodo['TotalPago'] : 0,
        'cuotas'  => $cuotas,
        // Si ya se envió a Gire, el pago espera la rendición diaria
        'enviada' => isset($nodo['Enviada']) ? filter_var($nodo['Enviada'], FILTER_VALIDATE_BOOLEAN) : FALSE,
    );
}

/**
 * Llama a un endpoint del WS (GET o POST con body JSON) y normaliza la respuesta,
 * evitando repetir el bloque de curl_init/curl_setopt/json_decode en cada controlador.
 *
 * El WS responde de dos formas según la familia de endpoints: envuelta en un nodo
 * "respuesta" (los de colegiado/*) o plana (los de cobranza/*). Esta función detecta
 * cuál es y devuelve siempre el mismo shape, para que el llamador no tenga que
 * importarle cuál de las dos formas usa el endpoint que está consumiendo.
 *
 * Devuelve un array asociativo:
 *   'ok'       bool   true solo si no hubo error de conexión/decodificación y codigo == 1
 *   'codigo'   mixed  el código que devolvió el WS (o NULL si no se pudo interpretar)
 *   'mensaje'  string el mensaje del WS, o uno genérico si falló la conexión/decodificación
 *   'datos'    mixed  el nodo "datos" de la respuesta, si existe
 *   'nodo'     array  la respuesta ya desenvuelta (útil para leer campos extra, como
 *                      "intencion_pago", que viajan junto a "datos" en vez de adentro)
 *   'cruda'    mixed  la respuesta decodificada tal cual llegó, sin desenvolver
 *   'error'    string el error de curl, o NULL si no hubo
 *   'httpCode' int    código HTTP de la respuesta
 *
 * @param string $url URL completa del endpoint (armar con URL_WS.'/...')
 * @param string $metodo 'GET' o 'POST'
 * @param array|string|null $datos para POST: array (se codifica a JSON) o string ya codificado
 * @param int $timeoutSegundos tiempo máximo de espera
 */
function llamarWs($url, $metodo = 'GET', $datos = null, $timeoutSegundos = 30) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSegundos);

    if (strtoupper($metodo) === 'POST') {
        $cuerpo = is_string($datos) ? $datos : json_encode($datos);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $cuerpo);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($cuerpo)
        ));
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    }

    $resultado = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $respuesta = array(
        'ok'       => false,
        'codigo'   => null,
        'mensaje'  => '',
        'datos'    => null,
        'nodo'     => array(),
        'cruda'    => null,
        'error'    => $error ?: null,
        'httpCode' => $httpCode,
    );

    if ($error) {
        $respuesta['mensaje'] = 'Disculpe las molestias. Momentáneamente fuera de servicio, intente más tarde.';
        return $respuesta;
    }

    $decodificado = json_decode($resultado, true);
    $respuesta['cruda'] = $decodificado;

    if (!is_array($decodificado)) {
        $respuesta['mensaje'] = 'Momentáneamente fuera de servicio, vuelva a intentar más tarde.';
        return $respuesta;
    }

    // Se desenvuelve el nodo "respuesta" cuando existe; si no, el endpoint ya responde plano
    $nodo = isset($decodificado['respuesta']) ? $decodificado['respuesta'] : $decodificado;
    if (!is_array($nodo)) {
        $respuesta['mensaje'] = 'Momentáneamente fuera de servicio, vuelva a intentar más tarde.';
        return $respuesta;
    }

    $respuesta['nodo']    = $nodo;
    $respuesta['codigo']  = isset($nodo['codigo']) ? $nodo['codigo'] : null;
    $respuesta['mensaje'] = isset($nodo['mensaje']) ? $nodo['mensaje'] : '';
    $respuesta['datos']   = isset($nodo['datos']) ? $nodo['datos'] : null;
    $respuesta['ok']      = ($respuesta['codigo'] == 1);

    return $respuesta;
}

/**
 * Arma el formulario auto-submit que se usa en todo el proyecto para redirigir
 * llevando un mensaje flash (y, opcionalmente, otros campos — por ejemplo para
 * reenviar los datos que el colegiado había cargado si falló la validación) y
 * corta la ejecución. Reemplaza el bloque <body onLoad="...">...</body> que
 * se repetía copiado en cada controlador.
 *
 * @param string $accion URL a la que se redirige (relativa, ej. "tramites.php" o "cuotas.php?id=...")
 * @param string $mensaje texto del mensaje flash
 * @param string $clase clase Bootstrap del alert (ej. "alert alert-success")
 * @param array $camposExtra pares nombre => valor para inputs hidden adicionales
 */
function redirigirConMensaje($accion, $mensaje, $clase = 'alert alert-danger', $camposExtra = array()) {
    ?>
    <body onLoad="document.forms['formRedirigir'].submit()">
        <form name="formRedirigir" method="POST" action="<?php echo htmlspecialchars($accion, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="mensaje" value="<?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="clase" value="<?php echo htmlspecialchars($clase, ENT_QUOTES, 'UTF-8'); ?>">
            <?php foreach ($camposExtra as $nombre => $valor) { ?>
                <input type="hidden" name="<?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'); ?>">
            <?php } ?>
        </form>
    </body>
    <?php
    exit;
}