<?php
// admin/index.php
require_once '../config/conexion.php';
require_once '../config/auth.php';

// Validar acceso exclusivo para administradores
verificarAdmin();

// Consultas para las métricas del panel
$totalVentas = $pdo->query("SELECT SUM(monto_total) AS total FROM pedidos")->fetch()['total'] ?? 0;
$pedidosPendientes = $pdo->query("SELECT COUNT(*) AS total FROM pedidos WHERE estado_pedido = 'PENDIENTE'")->fetch()['total'];
$productosActivos = $pdo->query("SELECT COUNT(*) AS total FROM productos WHERE estado = 1")->fetch()['total'];
$clientesRegistrados = $pdo->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol_id = 2")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrador | E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <!-- Navbar Superior -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-gauge-high me-2 text-warning"></i>Panel Admin</a>
            <div class="d-flex">
                <span class="navbar-text text-white me-3">Hola, <?= htmlspecialchars($_SESSION['user_nombre']) ?></span>
                <a href="../logout.php" class="btn btn-outline-danger btn-sm">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Menú Lateral -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-white sidebar py-4 shadow-sm" style="min-height: 90vh;">
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a href="index.php" class="nav-link text-dark fw-bold active"><i class="fa-solid fa-chart-pie me-2"></i> Dashboard</a></li>
                    <li class="nav-item mb-2"><a href="productos.php" class="nav-link text-secondary"><i class="fa-solid fa-box-open me-2"></i> Gestión Productos</a></li>
                    <li class="nav-item mb-2"><a href="../index.php" class="nav-link text-secondary"><i class="fa-solid fa-store me-2"></i> Ver Tienda</a></li>
                </ul>
            </nav>

            <!-- Contenido Principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2 class="fw-bold mb-4">Resumen General del Sistema</h2>
                
                <!-- Tarjetas de Indicadores (KPIs) -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-primary border-4">
                            <small class="text-muted fw-semibold">Total Ventas</small>
                            <h3 class="fw-bold mt-2 text-primary">$<?= number_format($totalVentas, 2) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-warning border-4">
                            <small class="text-muted fw-semibold">Pedidos Pendientes</small>
                            <h3 class="fw-bold mt-2"><?= $pedidosPendientes ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-success border-4">
                            <small class="text-muted fw-semibold">Productos Activos</small>
                            <h3 class="fw-bold mt-2"><?= $productosActivos ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-info border-4">
                            <small class="text-muted fw-semibold">Clientes Registrados</small>
                            <h3 class="fw-bold mt-2"><?= $clientesRegistrados ?></h3>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>