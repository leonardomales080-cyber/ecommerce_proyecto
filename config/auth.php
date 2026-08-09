<?php
// config/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si el usuario ha iniciado sesión
function verificarSesion() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit;
    }
}

// Función para verificar si el usuario es Administrador
function verificarAdmin() {
    verificarSesion();
    if (!isset($_SESSION['user_rol']) || strtoupper($_SESSION['user_rol']) !== 'ADMIN') {
        header("Location: ../tienda.php?error=acceso_denegado");
        exit;
    }
}
?>