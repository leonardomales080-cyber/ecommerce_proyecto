<?php
ob_start();
session_start();

// Habilitar errores para desarrollo (puedes cambiar a 0 en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', realpath(__DIR__ . '/..'));

require_once BASE_PATH . '/config/conexion.php';

// 1. Validaciones iniciales
if (!isset($_SESSION['user_id']) || empty($_SESSION['carrito'])) {
    header("Location: ../tienda.php");
    exit;
}

try {
    $pdo->beginTransaction();
    $codigo_pedido = 'PED-' . date('YmdHis') . '-' . rand(100, 999);
    
    // Calcular total general del carrito
    $total_general = 0;
    foreach ($_SESSION['carrito'] as $item) {
        $total_general += $item['precio'] * $item['cantidad'];
    }
    
    // Insertar el pedido en la base de datos
    $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, codigo_pedido, monto_total, fecha_pedido) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $codigo_pedido, $total_general]);
    
    // --- LLAMADA A LA LÓGICA EXTERNA ---
    require_once BASE_PATH . '/config/generar_factura.php';

    // Se ejecuta la función que crea el PDF y envía el correo
    $mail_enviado = procesarFacturaYCorreo($pdo, $_SESSION['user_id'], $codigo_pedido, $_SESSION['carrito']);

    $pdo->commit();
    unset($_SESSION['carrito']);
    
    // Limpiamos el buffer de salida antes de redirigir
    ob_end_clean();
    
    // Redirección exitosa hacia el checkout con los parámetros necesarios
    header("Location: ../checkout.php?exito=1&pedido=" . urlencode($codigo_pedido) . "&mail=" . (int)$mail_enviado);
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    ob_end_clean();
    
    // Redirección en caso de error para mostrarlo amigablemente en el frontend
    header("Location: ../checkout.php?error=" . urlencode("Ocurrió un error al procesar tu compra: " . $e->getMessage()));
    exit;
}
?>