<?php
// ==========================================
// ARCHIVO: checkout.php
// ==========================================

session_start();
require_once 'config/conexion.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=checkout.php");
    exit;
}

// Verificar si hay productos en el carrito
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    header("Location: tienda.php");
    exit;
}

$total = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

$error = $_GET['error'] ?? '';
$exito_param = $_GET['exito'] ?? '';
$pedido_codigo = $_GET['pedido'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - Males Motors</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="tienda.php"><i class="fa-solid fa-store me-2"></i>Males Motors E-Commerce</a>
            <a href="carrito.php" class="btn btn-outline-light btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Volver al Carrito</a>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4 fw-bold text-center">Finalizar Compra</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($exito_param)): ?>
            <div class="alert alert-success text-center py-4 rounded-4 shadow-sm">
                <h4 class="fw-bold mb-3">¡Compra realizada con éxito!</h4>
                <p class="mb-3">Tu código de pedido es: <b>#<?php echo htmlspecialchars($pedido_codigo); ?></b>. Se ha registrado la orden y generado tu comprobante PDF.</p>
                <a href="tienda.php" class="btn btn-dark mt-2 rounded-pill px-4">Seguir Comprando</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-7">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                        <h4 class="fw-bold mb-3">Datos de Envío</h4>
                        <form action="config/procesar_compra.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Dirección de Entrega</label>
                                <textarea name="direccion_envio" class="form-control" rows="3" required placeholder="Calle principal, número y referencia"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Método de Pago</label>
                                <select name="metodo_pago" class="form-select">
                                    <option value="Efectivo">Efectivo contra entrega</option>
                                    <option value="Transferencia">Transferencia Bancaria</option>
                                    <option value="Tarjeta">Tarjeta de Crédito / Débito</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success w-100 fw-bold py-2 rounded-pill shadow-sm">Confirmar y Pagar</button>
                        </form>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h4 class="fw-bold mb-3">Resumen del Pedido</h4>
                        <ul class="list-group list-group-flush mb-3">
                            <?php foreach ($_SESSION['carrito'] as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <h6 class="my-0"><?php echo htmlspecialchars($item['descripcion']); ?></h6>
                                        <small class="text-muted">Cant: <?php echo $item['cantidad']; ?></small>
                                    </div>
                                    <span class="text-muted">$<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></span>
                                </li>
                            <?php endforeach; ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 fw-bold fs-5 mt-2">
                                <span>Total:</span>
                                <span class="text-success">$<?php echo number_format($total, 2); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>