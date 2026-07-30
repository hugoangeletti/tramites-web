<?php
require_once '../dataAccess/config.php';
permisoLogueado();
require_once '../html/head.php';
require_once '../html/header.php';
require_once '../dataAccess/funcionesPhp.php';

$continuar = true;
$mensaje = "";
if (isset($_GET['id']) && $_GET['id'] == $_SESSION['hashColegiado']) {
    $idColegiado = $_SESSION['idColegiado'];
    $hashColegiado = $_SESSION['hashColegiado'];
    $matricula = $_SESSION['matricula'];

    if (isset($_POST['cuotas_seleccionadas']) && sizeof($_POST['cuotas_seleccionadas']) > 0) {
        $cuotas_seleccionadas = $_POST['cuotas_seleccionadas'];
    } else {
        $continuar = FALSE;
        $mensaje .= "Debe seleccionar cuotas a abonar";
    }
    if (isset($_POST['totalActualizado'])) {
        // Reemplazamos coma por punto por si acaso
        $valorLimpio = str_replace(',', '.', $_POST['totalActualizado']);
        
        if (filter_var($valorLimpio, FILTER_VALIDATE_FLOAT) !== false) {
            $totalActualizado = $valorLimpio;
        } else {
            $continuar = FALSE;
            $mensaje .= "El total actualizado debe ser un número decimal válido. ";
        }
    } else {
        $continuar = FALSE;
        $mensaje .= "Falta el campo totalActualizado. ";
    }

    if ($continuar) {
        // Datos a enviar en formato array
        $data = array(
            "id" => $hashColegiado,
            'cuotas_seleccionadas' => $cuotas_seleccionadas,
            'total_pago' => $totalActualizado
        );

        // Convertir array a JSON
        $data_string = json_encode($data);

        // URL de la API REST donde se enviarán los datos
        if (ENV == "prod") {
            $url = 'https://webservices.colmed1.com.ar/colegio/ws-colmed/cobranza/generar_intencion_pago.php';
        } else {
            $url = 'https://www.colmed1.com/desarrollo/colegio/ws-colmed/cobranza/generar_intencion_pago.php';
        }

        // Inicializar cURL
        $ch = curl_init($url);

        // Configurar la llamada POST con cURL
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Encabezados HTTP para indicar que se envía JSON
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( 
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ));

        // Ejecutar la solicitud y obtener la respuesta
        $result = curl_exec($ch);

        // Cerrar la sesión cURL
        curl_close($ch);
        $respuesta = (json_decode($result,true));
        if (isset($respuesta)) {
            if ($respuesta['codigo'] == '1') {
                $idIntencionPago = $respuesta['idIntencionPago'];
                $hashIntencionPago = $respuesta['hashIntencionPago'];
            } else {
                $certificadoPdf = NULL;
                $continuar = FALSE;
                $mensaje .= $respuesta['mensaje'];
            }
        } else {
            $certificadoPdf = NULL;
            $continuar = FALSE;
            $mensaje .= "No se pudo generar la intención de pago.";
        }
    }
}
if ($continuar) {
?>
    <body onLoad="document.forms['myForm'].submit()">
        <form name="myForm"  method="POST" action="boton_pago.php?id=<?php echo $hashIntencionPago; ?>">
        </form>
    </body>
<?php
} else {
?>
    <div class="row alert alert-danger">
        <div class="col-md-10">
            <h4 class=""><b><?php echo $mensaje; ?></b></h4>
        </div>
        <div class="col-md-2">
            <a href="cuotas.php?id=<?php echo $hashColegiado; ?>" class="btn btn-danger">Volver</a>
        </div>
    </div>
<?php
}
include("../html/footer.php");
?>
</div>

</body>
