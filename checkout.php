<?php
session_start();
require_once 'config/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=checkout.php");
    exit;
}

$exito_param = $_GET['exito'] ?? '';

if ((!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) && empty($exito_param)) {
    header("Location: tienda.php");
    exit;
}

$total = 0;
if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }
}

$error = $_GET['error'] ?? '';
$pedido_codigo = $_GET['pedido'] ?? '';
$mail_enviado = $_GET['mail'] ?? '0';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - E-Commerce M.A.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin/assets/img/logo.css">
</head>

<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="tienda.php">
                <img src="admin/assets/img/logo_E_Commerce.png" alt="Logo M.A." height="100" class="d-inline-block align-text-top me-2" style="width: auto; object-fit: contain;">
                <span class="text-white">E-Commerce M.A.</span>
            </a>
            <a href="carrito.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="fa-solid fa-arrow-left me-1"></i> Volver al Carrito</a>
        </div>
    </nav>

    <div class="container my-5">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger shadow-sm rounded-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($exito_param)): ?>
            <div class="card border-0 shadow-lg rounded-4 p-5 text-center bg-white">
                <div class="mb-3 text-success"><i class="fa-solid fa-circle-check fa-4x"></i></div>
                <h2 class="fw-bold mb-2">¡Compra Finalizada con Éxito!</h2>
                <p class="text-muted mb-4">Tu pedido ha sido registrado correctamente en el sistema.</p>
                <hr class="text-muted mb-4">
                
                <div class="row justify-content-center mb-4">
                    <div class="col-md-8">
                        <div class="p-3 bg-light rounded-3 border">
                            <p class="mb-2"><b>Código de Pedido:</b> <span class="text-dark font-monospace"><?php echo htmlspecialchars($pedido_codigo); ?></span></p>
                            
                            <p class="mb-2">
                                <b>Estado del Correo:</b> 
                                <?php if ($mail_enviado === '1'): ?>
                                    <span class="badge bg-success py-2 px-3 rounded-pill"><i class="fa-solid fa-paper-plane me-1"></i> Factura enviada al correo</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark py-2 px-3 rounded-pill"><i class="fa-solid fa-triangle-exclamation me-1"></i> Generada localmente</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="uploads/factura_<?php echo htmlspecialchars($pedido_codigo); ?>.pdf" target="_blank" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm">
                        <i class="fa-solid fa-eye me-2"></i>Ver / Imprimir Factura
                    </a>
                    
                    <a href="uploads/factura_<?php echo htmlspecialchars($pedido_codigo); ?>.pdf" download="factura_<?php echo htmlspecialchars($pedido_codigo); ?>.pdf" class="btn btn-danger rounded-pill px-4 py-2 shadow-sm">
                        <i class="fa-solid fa-file-pdf me-2"></i>Descargar Factura PDF
                    </a>
                    
                    <a href="tienda.php" class="btn btn-dark rounded-pill px-4 py-2 shadow-sm">
                        <i class="fa-solid fa-arrow-left me-2"></i>Seguir Comprando
                    </a>
                </div>
            </div>
        <?php else: ?>
            <h2 class="mb-4 fw-bold text-center">Finalizar Compra</h2>
            <div class="row">
                <div class="col-md-7">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                        <h4 class="fw-bold mb-3">Datos de Envío</h4>
                        <form action="config/procesar_compra.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Dirección de Entrega</label>
                                <textarea name="direccion_envio" class="form-control rounded-3" rows="3" required placeholder="Calle principal, número y referencia"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Método de Pago</label>
                                <select name="metodo_pago" class="form-select rounded-3">
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
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h4 class="fw-bold mb-3">Resumen del Pedido</h4>
                        <ul class="list-group list-group-flush mb-3">
                            <?php foreach ($_SESSION['carrito'] as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <div>
                                        <h6 class="my-0"><?php echo htmlspecialchars($item['descripcion']); ?></h6>
                                        <small class="text-muted">Cant: <?php echo $item['cantidad']; ?></small>
                                    </div>
                                    <span class="text-muted">$<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></span>
                                </li>
                            <?php endforeach; ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 fw-bold fs-5 mt-2 bg-transparent border-top">
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