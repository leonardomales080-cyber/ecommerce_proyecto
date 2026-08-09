<?php
// index.php (Raíz del proyecto)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido | E-Commerce</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .welcome-card {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            max-width: 520px;
            width: 100%;
            padding: 2.5rem;
        }
    </style>
</head>
<body>

    <div class="card welcome-card text-center border-0">
        <div class="mb-4">
            <i class="fa-solid fa-store fa-3x text-dark mb-3"></i>
            <h1 class="fw-bold text-dark">Sistema E-Commerce</h1>
            <p class="text-muted small">Autenticación por Roles, Gestión de Inventario y Pedidos</p>
        </div>

        <div class="d-grid gap-3 col-10 mx-auto">
            <a href="tienda.php" class="btn btn-dark btn-lg py-3 fw-semibold rounded-pill shadow-sm">
                <i class="fa-solid fa-bag-shopping me-2"></i>Ir a la Tienda (Catálogo)
            </a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (isset($_SESSION['user_rol']) && strtoupper($_SESSION['user_rol']) === 'ADMIN'): ?>
                    <a href="admin/index.php" class="btn btn-outline-dark btn-lg py-3 fw-semibold rounded-pill">
                        <i class="fa-solid fa-gauge me-2"></i>Ir al Panel Administrativo
                    </a>
                <?php endif; ?>
                <a href="config/logout.php" class="btn btn-outline-danger btn-sm mt-2">Cerrar Sesión actual</a>
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