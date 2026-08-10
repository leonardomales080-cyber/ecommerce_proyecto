<?php
// carrito.php
session_start();
require_once 'config/conexion.php';

// Manejar acciones del carrito (actualizar cantidad o eliminar producto)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $producto_id = intval($_POST['producto_id'] ?? 0);
    
    if (isset($_SESSION['carrito'][$producto_id])) {
        if ($_POST['accion'] === 'eliminar') {
            unset($_SESSION['carrito'][$producto_id]);
        } elseif ($_POST['accion'] === 'actualizar') {
            $nueva_cantidad = intval($_POST['cantidad'] ?? 1);
            if ($nueva_cantidad > 0) {
                // Verificar stock antes de actualizar de forma segura
                if (isset($pdo)) {
                    $stmt = $pdo->prepare("SELECT stock FROM productos WHERE id = ?");
                    $stmt->execute([$producto_id]);
                    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($prod && intval($prod['stock']) >= $nueva_cantidad) {
                        $_SESSION['carrito'][$producto_id]['cantidad'] = $nueva_cantidad;
                    } else {
                        // Si no hay stock suficiente, opcionalmente asigna el máximo disponible o déjalo igual
                        $_SESSION['carrito'][$producto_id]['cantidad'] = $nueva_cantidad;
                    }
                } else {
                    $_SESSION['carrito'][$producto_id]['cantidad'] = $nueva_cantidad;
                }
            } else {
                unset($_SESSION['carrito'][$producto_id]);
            }
        }
    }
    header("Location: carrito.php");
    exit;
}

$carrito = $_SESSION['carrito'] ?? [];
$subtotal_general = 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras | E-Commerce M.A.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="tienda.php">E-Commerce M.A.</a>
            <a href="tienda.php" class="btn btn-outline-light btn-sm"><i class="fa-solid fa-arrow-left me-2"></i>Seguir Comprando</a>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4 fw-bold">Tu Carrito de Compras</h2>

        <?php if (!empty($carrito)): ?>
            <div class="row">
                <!-- Listado de Productos en el Carrito -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Precio</th>
                                        <th>Cantidad</th>
                                        <th>Subtotal</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($carrito as $item): 
                                        $subtotal = $item['precio'] * $item['cantidad'];
                                        $subtotal_general += $subtotal;
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="uploads/<?php echo htmlspecialchars($item['imagen'] ?? ''); ?>" class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/50';">
                                                    <span class="fw-semibold"><?php echo htmlspecialchars($item['descripcion'] ?? ''); ?></span>
                                                </div>
                                            </td>
                                            <td>$<?php echo number_format($item['precio'], 2); ?></td>
                                            <td>
                                                <form action="carrito.php" method="POST" class="d-flex align-items-center">
                                                    <input type="hidden" name="accion" value="actualizar">
                                                    <input type="hidden" name="producto_id" value="<?php echo $item['id']; ?>">
                                                    <input type="number" name="cantidad" value="<?php echo $item['cantidad']; ?>" min="1" class="form-control form-control-sm text-center me-2" style="width: 70px;" onchange="this.form.submit()">
                                                </form>
                                            </td>
                                            <td class="fw-bold text-success">$<?php echo number_format($subtotal, 2); ?></td>
                                            <td>
                                                <form action="carrito.php" method="POST">
                                                    <input type="hidden" name="accion" value="eliminar">
                                                    <input type="hidden" name="producto_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar ítem"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Resumen y Checkout Condicionado -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h4 class="fw-bold mb-3">Resumen del Pedido</h4>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-bold">$<?php echo number_format($subtotal_general, 2); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fs-5 fw-bold">Total General</span>
                            <span class="fs-5 fw-bold text-success">$<?php echo number_format($subtotal_general, 2); ?></span>
                        </div>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="checkout.php" class="btn btn-dark w-100 fw-bold py-2 rounded-pill shadow-sm">Proceder al Pago</a>
                        <?php else: ?>
                            <div class="alert alert-warning small text-center mb-3">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> Debes iniciar sesión o registrarte para finalizar tu compra.
                            </div>
                            <a href="login.php?redirect=checkout.php" class="btn btn-dark w-100 fw-bold py-2 rounded-pill mb-2">Iniciar Sesión</a>
                            <a href="registro.php" class="btn btn-outline-dark w-100 fw-bold py-2 rounded-pill">Registrarse</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm rounded-4 text-center py-5">
                <div class="card-body">
                    <i class="fa-solid fa-cart-shopping fa-3x text-muted mb-3"></i>
                    <p class="text-muted fs-5">Tu carrito de compras está vacío.</p>
                    <a href="tienda.php" class="btn btn-dark fw-semibold mt-2 px-4 rounded-pill">Ver Catálogo</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>