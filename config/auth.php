<?php
// config/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Restringe el acceso solo a usuarios logueados
function verificarAutenticado() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// Restringe el acceso exclusivo al Administrador
function verificarAdmin() {
    verificarAutenticado();
    if ($_SESSION['user_rol'] !== 'ADMIN') {
        header("Location: acceso_denegado.php");
        exit;
    }
}
?>