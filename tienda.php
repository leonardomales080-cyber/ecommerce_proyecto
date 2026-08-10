<?php
// tienda.php
require_once 'config/conexion.php';
session_start();

// Obtener productos de la base de datos ordenados por categoría
try {
    $stmt = $pdo->query("SELECT p.*, c.nombre AS categoria FROM productos p JOIN categorias c ON p.categoria_id = c.id ORDER BY c.nombre ASC, p.id DESC");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $productos = [];
}

// Agrupar productos por categoría
$productosPorCategoria = [];
foreach ($productos as $prod) {
    $productosPorCategoria[$prod['categoria']][] = $prod;
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
    <style>
        .product-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border: 1px solid #eaeaea;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);
        }
        .product-img {
            height: 200px;
            object-fit: cover;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .product-img:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="tienda.php"><i class="fa-solid fa-store me-2"></i>Tienda Online</a>
            <div class="d-flex align-items-center gap-3">
                <a href="carrito.php" class="btn btn-outline-light btn-sm position-relative">
                    <i class="fa-solid fa-cart-shopping me-1"></i> Carrito
                    <?php if (isset($_SESSION['carrito']) && count($_SESSION['carrito']) > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= count($_SESSION['carrito']) ?>
                        </span>
                    <?php endif; ?>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="config/logout.php" class="btn btn-outline-danger btn-sm">Salir</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary btn-sm">Ingresar</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <div class="container my-5">
        <h2 class="fw-bold mb-4 text-center">Catálogo de Productos</h2>

        <?php if (count($productosPorCategoria) > 0): ?>
            <?php foreach ($productosPorCategoria as $nombreCat => $listaProds): ?>
                <!-- Sección por Categoría -->
                <div class="mb-5">
                    <div class="d-flex align-items-center mb-3 border-bottom pb-2">
                        <h4 class="fw-bold text-dark m-0"><i class="fa-solid fa-folder-open me-2 text-primary"></i><?= htmlspecialchars($nombreCat) ?></h4>
                        <span class="badge bg-secondary ms-2 fs-6"><?= count($listaProds) ?> items</span>
                    </div>

                    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                        <?php foreach ($listaProds as $prod): ?>
                            <?php 
                                $imgDB = $prod['imagen'] ?? 'default.png';
                                $rutaFinal = 'uploads/' . $imgDB;
                            ?>
                            <div class="col">
                                <div class="card product-card h-100 rounded-4 overflow-hidden bg-white">
                                    <!-- Imagen con evento para abrir el modal de zoom -->
                                    <img src="<?= htmlspecialchars($rutaFinal) ?>" 
                                         class="card-img-top product-img" 
                                         alt="<?= htmlspecialchars($prod['descripcion']) ?>"
                                         onclick="abrirModalZoom('<?= htmlspecialchars($rutaFinal) ?>', '<?= htmlspecialchars($prod['descripcion']) ?>', '<?= number_format($prod['precio'], 2) ?>')"
                                         onerror="this.onerror=null; this.src='https://via.placeholder.com/200';">
                                    
                                    <div class="card-body d-flex flex-column justify-content-between p-3">
                                        <div>
                                            <h6 class="card-title fw-semibold text-dark mb-2"><?= htmlspecialchars($prod['descripcion']) ?></h6>
                                        </div>
                                        <div>
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <span class="text-success fw-bold fs-5">$<?= number_format($prod['precio'], 2) ?></span>
                                            </div>
                                            <form action="config/agregar_carrito.php" method="POST">
                                                <input type="hidden" name="producto_id" value="<?= $prod['id'] ?>">
                                                <button type="submit" class="btn btn-dark w-100 btn-sm fw-semibold">
                                                    <i class="fa-solid fa-cart-plus me-1"></i> Agregar al carrito
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <p class="text-muted fs-5">No hay productos disponibles por el momento.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para Zoom de Imagen en la Tienda -->
    <div class="modal fade" id="modalZoomTienda" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content text-center p-3 rounded-4 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-start" id="tituloModalZoom"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="imagenModalAmpliada" src="" alt="Imagen Ampliada" class="img-fluid rounded shadow-sm mb-3" style="max-height: 450px; object-fit: contain;">
                    <h4 id="precioModalZoom" class="text-success fw-bold"></h4>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function abrirModalZoom(urlImagen, descripcion, precio) {
            document.getElementById('imagenModalAmpliada').src = urlImagen;
            document.getElementById('tituloModalZoom').innerText = descripcion;
            document.getElementById('precioModalZoom').innerText = '$' + precio;
            var myModal = new bootstrap.Modal(document.getElementById('modalZoomTienda'));
            myModal.show();
        }
    </script>
</body>
</html>