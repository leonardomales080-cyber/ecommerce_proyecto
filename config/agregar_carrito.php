<?php
session_start();
require_once 'conexion.php';

// Asegurar que se reciba una petición POST y el ID del producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['producto_id'])) {
    $producto_id = intval($_POST['producto_id']);
    $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;

    // Verificar que el producto exista y tenga stock
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ? AND estado = 1");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($producto) {
        if ($producto['stock'] >= $cantidad) {
            // Inicializar el carrito en sesión si no existe
            if (!isset($_SESSION['carrito'])) {
                $_SESSION['carrito'] = [];
            }

            // Si el producto ya está en el carrito, sumar la cantidad; si no, agregarlo
            if (isset($_SESSION['carrito'][$producto_id])) {
                $_SESSION['carrito'][$producto_id]['cantidad'] += $cantidad;
            } else {
                $_SESSION['carrito'][$producto_id] = [
                    'id' => $producto['id'],
                    'descripcion' => $producto['descripcion'],
                    'precio' => $producto['precio'],
                    'imagen' => $producto['imagen'],
                    'cantidad' => $cantidad
                ];
            }

            // Contar el total de items en el carrito para la respuesta
            $total_items = array_sum(array_column($_SESSION['carrito'], 'cantidad'));

            echo json_encode([
                'status' => 'success',
                'message' => '¡Producto añadido al carrito correctamente!',
                'total_items' => $total_items
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No hay suficiente stock disponible.'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'El producto no es válido.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Petición no válida.'
    ]);
}