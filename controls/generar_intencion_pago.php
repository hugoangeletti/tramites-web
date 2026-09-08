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
    $matricula = $_SESSION['matricula'];

    if (isset($_GET['origen']) && $_GET['origen'] == 'curso') {
        $origen = 'curso';
    } else if (isset($_GET['origen']) && $_GET['origen'] == 'plan_pago') {
        $origen = 'plan_pago';
    } else {
        $origen = 'colegiacion';
    }
    if ($origen == 'curso') {
        if (isset($_GET['idCursosAsistente']) && $_GET['idCursosAsistente'] <> '') {
            $idCursosAsistente = $_GET['idCursosAsistente'];
        } else {
            $continuar = FALSE;
            $mensaje .= "Falta el curso.";
        }
    } else if ($origen == 'plan_pago') {
        if (isset($_GET['idPlanPago']) && $_GET['idPlanPago'] <> '') {
            $idPlanPago = $_GET['idPlanPago'];
        } else {
            $continuar = FALSE;
            $mensaje .= "Falta el plan de pagos.";
        }
    }

    // Solo bloquea generar una intención nueva si hay una "iniciada"/"enviada" en
    // curso (evita duplicar el checkout). Si la última que tiene es "aprobada" (Gire
    // ya confirmó, falta que se acredite en la rendición diaria) no impide seguir
    // pagando otras cuotas de ese mismo concepto.
    $estadoIntencionActual = ($origen == 'curso')
        ? (isset($_SESSION['intencionesPagoPendientesCurso'][$idCursosAsistente]['estado']) ? $_SESSION['intencionesPagoPendientesCurso'][$idCursosAsistente]['estado'] : null)
        : (isset($_SESSION['intencionPagoPendiente']['estado']) ? $_SESSION['intencionPagoPendiente']['estado'] : null);
    $hayIntencionPendiente = in_array($estadoIntencionActual, array('iniciada', 'enviada'));

    if ($hayIntencionPendiente) {
        $continuar = FALSE;
        $mensaje .= "Ya tiene una intención de pago pendiente. Debe abonarla o anularla antes de generar una nueva.";
    } else if (isset($_POST['cuotas_seleccionadas']) && sizeof($_POST['cuotas_seleccionadas']) > 0) {
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
            'id' => $hashColegiado,
            'cuotas_seleccionadas' => $cuotas_seleccionadas,
            'total_pago' => $totalActualizado
        );
        if ($origen == 'curso') {
            $data['concepto'] = 'cursos';
            $data['idCursosAsistente'] = $idCursosAsistente;
        } else if ($origen == 'plan_pago') {
            $data['concepto'] = 'plan_pago';
            $data['idPlanPago'] = $idPlanPago;
        }

        // Convertir array a JSON
        $data_string = json_encode($data);

        // URL de la API REST donde se enviarán los datos
        $url = URL_WS.'/cobranza/generar_intencion_pago.php';

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
    <?php
    $urlProcesarPago = 'procesar_pago.php?id=' . $hashColegiado;
    if ($origen == 'curso') {
        $urlProcesarPago .= '&origen=curso&idCurso=' . $idCursosAsistente;
    } else if ($origen == 'plan_pago') {
        $urlProcesarPago .= '&origen=plan_pago&idPlanPago=' . $idPlanPago;
    }
    ?>
    <body onLoad="document.forms['myForm'].submit()">
        <form name="myForm" method="POST" action="<?php echo $urlProcesarPago; ?>">
            <input type="hidden" name="hashIntencionPago" value="<?php echo htmlspecialchars($hashIntencionPago, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="totalActualizado" value="<?php echo htmlspecialchars($totalActualizado, ENT_QUOTES, 'UTF-8'); ?>">
        </form>
    </body>
<?php
} else {
    if (isset($origen) && $origen == 'curso' && isset($hashColegiado)) {
        $urlVolver = 'cuotas_curso.php?id=' . $hashColegiado . '&reg=' . (isset($idCursosAsistente) ? $idCursosAsistente : '');
    } else if (isset($origen) && $origen == 'plan_pago' && isset($hashColegiado)) {
        $urlVolver = 'planDePagos.php?id=' . $hashColegiado;
    } else if (isset($hashColegiado)) {
        $urlVolver = 'cuotas.php?id=' . $hashColegiado;
    } else {
        $urlVolver = 'tramites.php';
    }
?>
    <div class="row alert alert-danger">
        <div class="col-md-10">
            <h4 class=""><b><?php echo $mensaje; ?></b></h4>
        </div>
        <div class="col-md-2">
            <a href="<?php echo $urlVolver; ?>" class="btn btn-danger">Volver</a>
        </div>
    </div>
<?php
}
require_once "../html/menuTramitesClose.php";
include("../html/footer.php");
?>
</div>

</body>
