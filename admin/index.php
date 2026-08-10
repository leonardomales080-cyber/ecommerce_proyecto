<?php
// admin/index.php
session_start();

// Validar si el usuario es administrador
if (!isset($_SESSION['user_rol']) || strtoupper($_SESSION['user_rol']) !== 'ADMIN') {
    header("Location: ../login.php");
    exit;
}

// Incluir la conexión a la base de datos para obtener las estadísticas reales
require_once __DIR__ . '/../config/conexion.php';

try {
    // 1. Obtener el total de ventas (número de pedidos)
    $stmt_ventas = $pdo->query("SELECT COUNT(*) AS total_ventas FROM pedidos");
    $resultado_ventas = $stmt_ventas->fetch(PDO::FETCH_ASSOC);
    $ventas_totales = $resultado_ventas['total_ventas'] ?? 0;

    // 2. Obtener los ingresos acumulados (suma de monto_total)
    $stmt_ingresos = $pdo->query("SELECT SUM(monto_total) AS ingresos_totales FROM pedidos");
    $resultado_ingresos = $stmt_ingresos->fetch(PDO::FETCH_ASSOC);
    $ingresos_acumulados = $resultado_ingresos['ingresos_totales'] ?? 0;

    // 3. Obtener el total real de productos registrados
    $stmt_productos = $pdo->query("SELECT COUNT(*) AS total_prod FROM productos");
    $resultado_productos = $stmt_productos->fetch(PDO::FETCH_ASSOC);
    $total_productos = $resultado_productos['total_prod'] ?? 36;

    // 4. Obtener el total real de categorías registradas
    $stmt_categorias = $pdo->query("SELECT COUNT(*) AS total_cat FROM categorias");
    $resultado_categorias = $stmt_categorias->fetch(PDO::FETCH_ASSOC);
    $total_categorias = $resultado_categorias['total_cat'] ?? 3;

} catch (Exception $e) {
    // Valores por defecto en caso de error en la consulta
    $ventas_totales = 0;
    $ingresos_acumulados = 0;
    $total_productos = 36;
    $total_categorias = 3;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración | E-Commerce M.A.</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Hoja de estilos con la marca de agua global -->
    <link rel="stylesheet" href="assets/img/logo.css">
</head>
<body class="bg-light">

    <!-- Navbar del Administrador -->
    <nav class="navbar navbar-dark bg-dark shadow-sm py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
                <img src="assets/img/logo_E_Commerce.png" alt="Logo M.A." class="me-2" style="height: 45px; width: auto; object-fit: contain;">
                <span class="fs-5 text-white">Panel Admin</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white small">Hola, <strong><?= htmlspecialchars($_SESSION['user_nombres'] ?? 'Administrador') ?></strong></span>
                <a href="../config/logout.php" class="btn btn-danger btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal del Panel -->
    <div class="container my-5">
        
        <!-- Tarjeta de Bienvenida -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <div class="row align-items-center">
                <div class="col-md-2 text-center mb-3 mb-md-0">
                    <img src="assets/img/logo_marca_agua.png" alt="Logo" class="img-fluid" style="max-height: 80px; object-fit: contain;">
                </div>
                <div class="col-md-10">
                    <h2 class="fw-bold text-dark">Bienvenido al Panel de Control</h2>
                    <p class="text-muted mb-0">Desde aquí puedes gestionar los productos, inventario, reportes de Excel, pedidos y supervisar las operaciones del e-commerce.</p>
                </div>
            </div>
        </div>

        <!-- Tarjetas de Estadísticas Rápidas -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 text-white bg-primary p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small fw-semibold mb-1">Productos</h6>
                            <h3 class="fw-bold mb-0"><?= number_format($total_productos) ?></h3>
                        </div>
                        <i class="fa-solid fa-box-open fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 text-white bg-success p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small fw-semibold mb-1">Categorías</h6>
                            <h3 class="fw-bold mb-0"><?= number_format($total_categorias) ?></h3>
                        </div>
                        <i class="fa-solid fa-tags fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 text-dark bg-warning p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small fw-semibold mb-1">Ventas Totales</h6>
                            <h3 class="fw-bold mb-0"><?= number_format($ventas_totales) ?></h3>
                        </div>
                        <i class="fa-solid fa-cart-shopping fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 text-white bg-dark p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small fw-semibold mb-1">Ingresos Acumulados</h6>
                            <h3 class="fw-bold mb-0">$<?= number_format($ingresos_acumulados, 2, '.', ',') ?></h3>
                        </div>
                        <i class="fa-solid fa-dollar-sign fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accesos Rápidos -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-bolt text-warning me-2"></i>Accesos Rápidos</h5>
            <div class="d-flex flex-wrap gap-2">
                <a href="productos.php" class="btn btn-dark rounded-pill px-4 py-2">
                    <i class="fa-solid fa-boxes-stacked me-1"></i> Gestionar Inventario y Excel
                </a>
                <a href="pedidos.php" class="btn btn-primary rounded-pill px-4 py-2">
                    <i class="fa-solid fa-list-check me-1"></i> Gestionar Pedidos (AJAX)
                </a>
                <a href="reportes.php" class="btn btn-success rounded-pill px-4 py-2">
                    <i class="fa-solid fa-file-excel me-1"></i> Exportar Reporte Excel
                </a>
                <a href="../tienda.php" class="btn btn-outline-dark rounded-pill px-4 py-2">
                    <i class="fa-solid fa-store me-1"></i> Ver Tienda Pública
                </a>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>