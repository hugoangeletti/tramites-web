<body>
    <style>
        .colmed-titulo {
            font-size: 30px;
            color: #000000;
            font-weight: bolder;
            margin-bottom: -9px;
        }
        .colmed-subtitulo {
            color: #000000;
            font-size: 13px;
            margin-bottom: 2px;
            margin-left: 1px;
        }
        @media (max-width: 767.98px) {
            .colmed-titulo {
                font-size: 18px;
                text-align: center;
            }
            .colmed-subtitulo {
                font-size: 11px;
                text-align: center;
                margin-left: 0;
            }
        }
    </style>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-1 text-center">
                <a href="<?php echo (ENV == "prod") ? "http://www.colmed1.com.ar/tramites-web/controls/tramites.php" : "http://localhost/tramites-web/controls/tramites.php"; ?>">
                <img src="../public/images/logo-transp.png" alt="Imagen Encabezado" height="70px"></a>
            </div>
            <div class="col-md-6">
                <p class="colmed-titulo">COLEGIO DE M&Eacute;DICOS</p>
                <p class="colmed-subtitulo">Distrito I - Provincia de Buenos Aires</p>
            </div>
            <div class="col-md-5 text-left"></div>
        </div>
 