<?php
// index.php
set_time_limit(30); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido | E-Commerce M.A.</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos personalizados para marca de agua y tarjetas -->
    <link rel="stylesheet" href="admin/assets/img/logo.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="card border-0 p-5 shadow-sm rounded-4 mx-auto bg-white text-center" style="max-width: 500px; width: 100%;">
        <div class="mb-4">
            <!-- Logotipo principal visible y con excelente presencia -->
            <div class="mb-3">
                <img src="admin/assets/img/logo_E_Commerce.png" alt="Logo E-Commerce M.A." class="img-fluid" style="max-height: 80px; width: auto; object-fit: contain;">
            </div>
            <h1 class="fw-bold text-dark fs-3">Sistema E-Commerce M.A.</h1>
            <p class="text-muted small">Tecnología, Ropa, Hogar y Oficina</p>
        </div>

        <div class="d-grid gap-3 col-12 mx-auto">
            <a href="tienda.php" class="btn btn-dark btn-lg py-3 fw-semibold rounded-pill shadow-sm">
                <i class="fa-solid fa-bag-shopping me-2"></i>Ir a la Tienda (Catálogo)
            </a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (isset($_SESSION['user_rol']) && strtoupper($_SESSION['user_rol']) === 'ADMIN'): ?>
                    <a href="admin/index.php" class="btn btn-outline-dark btn-lg py-3 fw-semibold rounded-pill">
                        <i class="fa-solid fa-gauge me-2"></i>Ir al Panel Administrativo
                    </a>
                <?php endif; ?>
                <a href="config/logout.php" class="btn btn-outline-danger btn-sm mt-2">Cerrar Sesión</a>
            <?php else: ?>
                <div class="row g-2 mt-1">
                    <div class="col-6">
                        <a href="login.php" class="btn btn-outline-dark w-100 py-2 fw-semibold rounded-pill">Iniciar Sesión</a>
                    </div>
                    <div class="col-6">
                        <a href="registro.php" class="btn btn-primary w-100 py-2 fw-semibold rounded-pill">Registrarse</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-4 text-muted small">
            <span>Proyecto Integrador - Programación Web</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>