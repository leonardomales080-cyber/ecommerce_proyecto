<?php
// tienda.php
require_once 'config/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$stmt = $pdo->prepare("SELECT p.*, c.nombre as categoria_nombre FROM productos p INNER JOIN categorias c ON p.categoria_id = c.id WHERE p.estado = 1");
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_carrito = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $total_carrito += $item['cantidad'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda Online - E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="tienda.php"><i class="fa-solid fa-store me-2"></i>E-Commerce</a>
            <div class="d-flex align-items-center">
                <a href="carrito.php" class="btn btn-outline-light btn-sm me-3 position-relative">
                    <i class="fa-solid fa-cart-shopping me-1"></i> Carrito
                    <span id="cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?php echo $total_carrito; ?>
                    </span>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="text-white me-3 small">Hola, <?php echo htmlspecialchars($_SESSION['user_nombres'] ?? 'Cliente'); ?></span>
                    <a href="config/logout.php" class="btn btn-danger btn-sm">Salir</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light btn-sm me-2">Iniciar Sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4 text-center fw-bold">Catálogo de Productos</h2>

        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php if (count($productos) > 0): ?>
                <?php foreach ($productos as $prod): ?>
                    <?php 
                        // Normalizamos la ruta de la imagen proveniente de la base de datos
                        $imagen_db = trim($prod['imagen']);
                        $url_imagen = 'uploads/' . $imagen_db;
                    ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden">
                            
                            <!-- Imagen con respaldo onerror para evitar espacios en blanco por diferencias de extensión o mayúsculas -->
                            <img src="<?php echo htmlspecialchars($url_imagen); ?>" 
                                 class="card-img-top" 
                                 alt="Producto" 
                                 style="height: 220px; object-fit: cover;"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/300x220?text=Imagen+No+Disponible';">
                            
                            <div class="card-body d-flex flex-column">
                                <span class="badge bg-secondary mb-2 align-self-start"><?php echo htmlspecialchars($prod['categoria_nombre']); ?></span>
                                <h5 class="card-title fw-bold"><?php echo htmlspecialchars($prod['descripcion']); ?></h5>
                                <p class="card-text text-success fw-bold fs-5">$<?php echo number_format($prod['precio'], 2); ?></p>
                                <button class="btn btn-dark mt-auto w-100 agregar-carrito rounded-pill" data-id="<?php echo $prod['id']; ?>">
                                    <i class="fa-solid fa-cart-plus me-1"></i> Añadir al Carrito
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted fs-5">No hay productos disponibles por el momento.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.agregar-carrito').forEach(boton => {
                boton.addEventListener('click', function() {
                    const productoId = this.getAttribute('data-id');
                    fetch('config/agregar_carrito.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: 'producto_id=' + encodeURIComponent(productoId) + '&cantidad=1'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                document.getElementById('cart-badge').innerText = data.total_items;
                                Swal.fire({ icon: 'success', text: data.message, timer: 1500, toast: true, position: 'top-end' });
                            }
                        });
                });
            });
        });
    </script>
</body>
</html>