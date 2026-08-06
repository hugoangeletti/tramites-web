<?php

function permisoLogueado() {
    if (!isset($_SESSION['user_id']) ||
            (!isset($_SESSION['user_entidad'])) ||
            (!isset($_SESSION['user_ip'])) ||
            (!isset($_SESSION['matricula'])) ||
            ($_SESSION['user_ip'] != $_SERVER['REMOTE_ADDR']) ||
            ($_SESSION['private'] != $_SESSION['private_alternative'])) {

        header("Location: " . PATH_HOME . "index.php?error=ok3");
        exit();
    }
}

function permisoNoLogueado() {
    $ok = false;
    if (!isset($_SESSION['user_id']) || (!isset($_SESSION['user_entidad'])) || (!isset($_SESSION['user_ip'])) || (!isset($_SESSION['private'])) || (!isset($_SESSION['private_alternative']))) {
        $ok = true;
    }

    return $ok;
}

function logueado() {
    $ok = false;
    if (isset($_SESSION['user_id']) && (isset($_SESSION['user_entidad'])) && (isset($_SESSION['user_ip'])) && (isset($_SESSION['private'])) && (isset($_SESSION['private_alternative']))) {
        $ok = true;
    }

    return $ok;
}

function yaEstaLogueado() {
    if (isset($_SESSION['user_id']) && (isset($_SESSION['user_entidad'])) && ($_SESSION['user_ip'] == $_SERVER['REMOTE_ADDR']) && ($_SESSION['private'] == $_SESSION['private_alternative'])) {
            header("Location: " . PATH_HOME . "index.php?error=ok2");
        
        exit();
    }
}
