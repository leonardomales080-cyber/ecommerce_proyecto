<?php
// admin/index.php
require_once '../config/auth.php';
verificarAdmin(); // Valida que la sesión sea estrictamente de un Administrador
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración | E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-gauge me-2"></i>Panel Admin</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3 small">Hola, <strong><?= htmlspecialchars($_SESSION['user_nombres']); ?></strong></span>
                <a href="../config/logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="fa-solid fa-right-from-bracket me-1"></i>Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h2 class="fw-bold mb-3">Bienvenido al Panel de Control</h2>
                    <p class="text-muted">Desde aquí puedes gestionar los productos, categorías y supervisar las operaciones del e-commerce.</p>
                    <hr>
                    <div class="d-flex gap-2">
                        <a href="productos.php" class="btn btn-dark btn-sm"><i class="fa-solid fa-box me-1"></i>Gestionar Productos</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>