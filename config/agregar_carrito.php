<?php
// config/agregar_carrito.php
session_start();
require_once 'conexion.php';

$esAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['producto_id'])) {
    $producto_id = intval($_POST['producto_id']);
    $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;

    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($producto) {
        $stock_disponible = isset($producto['stock']) ? intval($producto['stock']) : 999;

        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        $cantidad_actual_en_carrito = isset($_SESSION['carrito'][$producto_id]) ? $_SESSION['carrito'][$producto_id]['cantidad'] : 0;

        if ($stock_disponible >= ($cantidad_actual_en_carrito + $cantidad)) {
            if (isset($_SESSION['carrito'][$producto_id])) {
                $_SESSION['carrito'][$producto_id]['cantidad'] += $cantidad;
            } else {
                $_SESSION['carrito'][$producto_id] = [
                    'id' => $producto['id'],
                    'descripcion' => $producto['descripcion'] ?? 'Producto',
                    'precio' => $producto['precio'] ?? 0,
                    'imagen' => $producto['imagen'] ?? '',
                    'cantidad' => $cantidad
                ];
            }

            $total_items = array_sum(array_column($_SESSION['carrito'], 'cantidad'));

            if ($esAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'message' => '¡Producto agregado al carrito!',
                    'total_items' => $total_items
                ]);
                exit();
            } else {
                header("Location: ../tienda.php");
                exit();
            }
        } else {
            if ($esAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No hay suficiente stock disponible.'
                ]);
                exit();
            } else {
                header("Location: ../tienda.php?error=stock");
                exit();
            }
        }
    } else {
        if ($esAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'El producto no es válido.'
            ]);
            exit();
        } else {
            header("Location: ../tienda.php?error=producto");
            exit();
        }
    }
} else {
    if ($esAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Petición no válida.'
        ]);
        exit();
    } else {
        header("Location: ../tienda.php");
        exit();
    }
}
?>