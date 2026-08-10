<?php
// Punto de entrada: se redirige al login conservando el motivo de la salida
// (por ejemplo ?error=ok3 cuando permisoLogueado() detecta la sesión vencida)
$destino = "controls/login.php";
if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] <> "") {
    $destino .= "?" . $_SERVER['QUERY_STRING'];
}
header("Location: " . $destino);
exit;
