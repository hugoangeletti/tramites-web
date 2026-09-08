<?php
if (!isset($_SESSION['hashColegiado']) || $_SESSION['hashColegiado'] == '' || !isset($_SESSION['apellidoNombre'])) {
    // Sesión de login válida pero sin los datos del colegiado cargados (p. ej. se
    // accedió directo a esta página sin pasar antes por tramites.php). Recargamos
    // ahí para que se vuelvan a buscar en vez de mostrar la página con datos vacíos.
    ?>
    <body onLoad="document.forms['formRecargaSesion'].submit()">
        <form name="formRecargaSesion" method="POST" action="tramites.php"></form>
    </body>
    <?php
    exit();
}

$hashColegiado = isset($_SESSION['hashColegiado']) ? $_SESSION['hashColegiado'] : '';
$tipoEstado = isset($_SESSION['tipoEstado']) ? $_SESSION['tipoEstado'] : '';
$estadoTesoreriaCodigo = isset($_SESSION['estado_tesoreria_codigo']) ? $_SESSION['estado_tesoreria_codigo'] : null;
$estadoTesoreriaLeyenda = isset($_SESSION['estado_tesoreria_leyenda']) ? $_SESSION['estado_tesoreria_leyenda'] : '';
$conSeguro = isset($_SESSION['conSeguro']) ? $_SESSION['conSeguro'] : '';
$conTramites = isset($conSeguro) && $conSeguro <> '';
$tieneCurso = isset($_SESSION['tieneCurso']) ? $_SESSION['tieneCurso'] : false;
$tieneEspecialidades = isset($_SESSION['tieneEspecialidades']) ? $_SESSION['tieneEspecialidades'] : false;
$sexo = isset($_SESSION['sexo']) ? $_SESSION['sexo'] : '';

$permiteCertificado = TRUE;
switch ($tipoEstado) {
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
        $estadoActual = "BAJA - ".(isset($_SESSION['detalleMovimiento']) ? $_SESSION['detalleMovimiento'] : '');
        $colorEstadoActual = "red";
        $permiteCertificado = FALSE;
        break;

    default:
        $estadoActual = "SIN DATO";
        $colorEstadoActual = "blue";
        $permiteCertificado = FALSE;
        break;
}

if ($estadoTesoreriaCodigo == 0) {
    $colorEstadoTesoreria = "green";
} else {
    $colorEstadoTesoreria = "#EE5757";
    $permiteCertificado = FALSE;
}
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

    #tramites-drive, #tramites-drive .btn, #tramites-drive .alert {
        font-family: 'Roboto', Arial, sans-serif;
    }
    #tramites-drive {
        background-color: #f8f9fa;
        padding: 20px 12px 40px;
        color: #3c4043;
    }
    .drive-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(60,64,67,.3), 0 1px 3px 1px rgba(60,64,67,.15);
    }
    .drive-banner .drive-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background-color: #1a73e8;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: 500;
        flex: 0 0 auto;
    }
    .drive-chip {
        display: inline-block;
        border-radius: 12px;
        padding: 3px 12px;
        font-size: .78rem;
        font-weight: 500;
        color: #fff;
    }
    .btn-drive-outline {
        border-radius: 20px;
        border: 1px solid #dadce0;
        background-color: #fff;
        color: #3c4043;
        padding: 8px 20px;
        font-weight: 500;
    }
    .btn-drive-outline:hover {
        background-color: #f1f3f4;
        color: #3c4043;
        box-shadow: 0 1px 2px rgba(60,64,67,.3);
    }
    .drive-heading {
        font-weight: 400;
        font-size: 1.4rem;
        color: #3c4043;
    }
    .tramites-sidebar .list-group {
        background: transparent;
        border: none;
    }
    .tramites-sidebar .list-group-item-heading {
        background: transparent;
        border: none;
        font-weight: 500;
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #5f6368;
        padding: 18px 12px 6px;
        margin: 0;
    }
    .tramites-sidebar .list-group-item-action {
        border: none;
        border-radius: 0 20px 20px 0;
        margin: 1px 12px 1px 0;
        padding: 9px 16px;
        color: #3c4043;
        font-size: .92rem;
        display: flex;
        align-items: center;
        text-align: left;
        width: calc(100% - 12px);
        background-color: transparent;
    }
    .tramites-sidebar .list-group-item-action:hover {
        background-color: #f1f3f4;
        color: #3c4043;
    }
    .tramites-sidebar .list-group-item-action.disabled {
        color: #bdc1c6;
        pointer-events: none;
        background: transparent;
    }
    .tramites-sidebar .list-group-item-action.active-link {
        background-color: #e8f0fe;
        color: #1967d2;
        font-weight: 500;
    }
    .tramites-sidebar .item-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 3px;
        margin-right: 14px;
        flex: 0 0 auto;
    }
    .tramites-hamburger {
        display: none;
        background: none;
        border: none;
        padding: 6px;
        margin-right: 12px;
        cursor: pointer;
        flex: 0 0 auto;
    }
    .tramites-hamburger span {
        display: block;
        width: 22px;
        height: 2px;
        background-color: #3c4043;
        margin: 4px 0;
        border-radius: 2px;
    }
    @media (max-width: 767.98px) {
        .tramites-hamburger {
            display: inline-block;
        }
        .tramites-sidebar {
            display: none;
        }
        .tramites-sidebar.tramites-sidebar-open {
            display: block;
        }
        .table {
            font-size: .78rem;
        }
        .table th,
        .table td {
            padding: .5rem;
        }
        .table .btn {
            font-size: .72rem;
            padding: .25rem .5rem;
        }
    }
</style>

<div id="tramites-drive">

<div class="card drive-card drive-banner mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center flex-wrap">
            <button type="button" class="tramites-hamburger" id="tramitesHamburgerBtn" aria-label="Abrir men&uacute;" aria-expanded="false" aria-controls="tramitesSidebar">
                <span></span><span></span><span></span>
            </button>
            <div class="drive-avatar mr-3">
                <?php echo mb_strtoupper(mb_substr($_SESSION['apellidoNombre'], 0, 1), 'UTF-8'); ?>
            </div>
            <div class="flex-grow-1">
                <h4 class="mb-1">
                    <?php echo ($sexo == "M") ? "Dr. " : "Dra. "; echo htmlspecialchars($_SESSION['apellidoNombre'], ENT_QUOTES, 'UTF-8'); ?>
                </h4>
                <div class="text-muted small">M.P. <?php echo htmlspecialchars($_SESSION['matricula'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <span class="drive-chip mr-2" style="background-color: <?php echo $colorEstadoActual; ?>;">Estado Matricular: <?php echo $estadoActual; ?></span>
                <span class="drive-chip" style="background-color: <?php echo $colorEstadoTesoreria; ?>;">Tesorer&iacute;a: <?php echo htmlspecialchars($estadoTesoreriaLeyenda, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div>
                <a href="logout.php" class="btn btn-drive-outline">Salir de tr&aacute;mites</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-4 tramites-sidebar" id="tramitesSidebar">
        <div class="list-group">
            <div class="list-group-item list-group-item-heading">Certificados</div>
            <a href="solicitar_certificado.php?id=<?php echo $hashColegiado; ?>" class="list-group-item list-group-item-action <?php if (!$permiteCertificado) { echo 'disabled'; } ?>"><span class="item-dot" style="background-color:#EA4335;"></span>Solicitar certificado</a>

            <?php if ($tieneEspecialidades && MOSTRAR_ESPECIALIDADES) { ?>
            <div class="list-group-item list-group-item-heading">Especialidades</div>
            <a href="especialidades.php?id=<?php echo $hashColegiado; ?>" class="list-group-item list-group-item-action"><span class="item-dot" style="background-color:#EA4335;"></span>Mis especialidades</a>
            <?php } ?>

            <div class="list-group-item list-group-item-heading">Tesorer&iacute;a</div>
            <a href="cuotas.php?id=<?php echo $hashColegiado; ?>" class="list-group-item list-group-item-action"><span class="item-dot" style="background-color:#34A853;"></span>Cuotas de colegiaci&oacute;n</a>
            <?php if (in_array($estadoTesoreriaCodigo, array(4, 5, 6, 7))) { ?>
                <a href="planDePagos.php?id=<?php echo $hashColegiado; ?>" class="list-group-item list-group-item-action"><span class="item-dot" style="background-color:#34A853;"></span>Plan de Pagos</a>
            <?php } ?>
            <?php if ($tieneCurso) { ?>
                <a href="cuotas_cursos.php?id=<?php echo $hashColegiado; ?>" class="list-group-item list-group-item-action"><span class="item-dot" style="background-color:#34A853;"></span>Cuotas de cursos</a>
            <?php } ?>

            <div class="list-group-item list-group-item-heading">ESEM</div>
            <a href="esem_inscripcion_curso.php?id=<?php echo $hashColegiado; ?>" class="list-group-item list-group-item-action"><span class="item-dot" style="background-color:#4285F4;"></span>Inscripci&oacute;n a cursos</a>
            <?php if ($tieneCurso) { ?>
                <a href="esem_asistente_curso.php?id=<?php echo $hashColegiado; ?>" class="list-group-item list-group-item-action"><span class="item-dot" style="background-color:#4285F4;"></span>Asistente a cursos</a>
            <?php } ?>

            <div class="list-group-item list-group-item-heading">Actualizaci&oacute;n de datos</div>
            <a href="actualizar_domicilio_particular.php?id=<?php echo $hashColegiado; ?>" class="list-group-item list-group-item-action"><span class="item-dot" style="background-color:#FBBC05;"></span>Actualizar domicilio particular</a>
            <a href="actualizar_contacto.php?id=<?php echo $hashColegiado; ?>" class="list-group-item list-group-item-action"><span class="item-dot" style="background-color:#FBBC05;"></span>Actualizar datos de contacto</a>

            <?php if ($conTramites) { ?>
            <div class="list-group-item list-group-item-heading">Seguro de praxis m&eacute;dica</div>
            <?php if ($conSeguro == 'ASEGURADO_COLEGIO') { ?>
            <a href="imprimirCertificadoCoberturaSeguro.php?id=<?php echo $hashColegiado; ?>" class="list-group-item list-group-item-action"><span class="item-dot" style="background-color:#A142F4;"></span>Certificado Cobertura</a>
            <button type="button" class="list-group-item list-group-item-action" data-toggle="modal" data-target="#ampliarSeguroModal"><span class="item-dot" style="background-color:#A142F4;"></span>Ampliaci&oacute;n de Cobertura</button>
            <?php } else { ?>
            <button type="button" class="list-group-item list-group-item-action" data-toggle="modal" data-target="#seguroAgremiadoModal"><span class="item-dot" style="background-color:#A142F4;"></span>Seguro Praxis M&eacute;dica</button>
            <?php } ?>
            <?php } ?>
        </div>
    </div>

    <div class="col-md-9">
        <?php if (!$permiteCertificado) { ?>
            <div class="alert alert-warning">Para poder solicitar un certificado debe estar ACTIVO y AL D&Iacute;A CON LAS CUOTAS DE COLEGIACI&Oacute;N.</div>
        <?php } ?>
