<?php
// admin/actualizar_estado.php
require_once '../config/conexion.php';
require_once '../config/auth.php';
verificarAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedido_id = intval($_POST['id'] ?? 0);
    $nuevo_estado = trim($_POST['estado'] ?? '');

    $estadosPermitidos = ['Pendiente', 'Procesando', 'Enviado', 'Completado', 'Cancelado'];

    if ($pedido_id > 0 && in_array($nuevo_estado, $estadosPermitidos)) {
        try {
            // Asegúrate de que tu tabla de pedidos se llame 'pedidos' o ajústala según tu BD
            $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
            if ($stmt->execute([$nuevo_estado, $pedido_id])) {
                echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente.']);
                exit;
            }
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Método no permitido.']);