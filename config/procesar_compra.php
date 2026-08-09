<?php
// ==========================================
// ARCHIVO: config/procesar_compra.php
// ==========================================
session_start();
require_once 'conexion.php';

// Cargar librerías de Composer (PHPMailer) y FPDF desde la raíz del proyecto
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
}
if (file_exists('../fpdf/fpdf.php')) {
    require_once '../fpdf/fpdf.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id']) || !isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    header("Location: ../tienda.php");
    exit;
}

$usuario_id = $_SESSION['user_id'];
$direccion_envio = trim($_POST['direccion_envio'] ?? '');
$metodo_pago = trim($_POST['metodo_pago'] ?? 'Efectivo');

if (empty($direccion_envio)) {
    header("Location: ../checkout.php?error=direccion");
    exit;
}

try {
    $pdo->beginTransaction();

    // Calcular el total
    $total = 0;
    foreach ($_SESSION['carrito'] as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }

    $codigo_pedido = 'PED-' . strtoupper(uniqid());

    // 1. Insertar en la tabla pedidos
    $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, codigo_pedido, monto_total, metodo_pago, estado_pedido, fecha_pedido) VALUES (?, ?, ?, ?, 'PENDIENTE', NOW())");
    $stmt->execute([$usuario_id, $codigo_pedido, $total, $metodo_pago]);
    $pedido_id = $pdo->lastInsertId();

    // 2. Insertar los detalles del pedido y descontar stock
    $stmtDetalle = $pdo->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
    $stmtStock = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");

    foreach ($_SESSION['carrito'] as $producto_id => $item) {
        $subtotal = $item['precio'] * $item['cantidad'];
        $stmtDetalle->execute([$pedido_id, $producto_id, $item['cantidad'], $item['precio'], $subtotal]);
        $stmtStock->execute([$item['cantidad'], $producto_id]);
    }

    // Obtener datos del usuario para la factura y correo
    $stmtUser = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmtUser->execute([$usuario_id]);
    $cliente = $stmtUser->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    $pdf_path = '';
    // ==========================================
    // 3. GENERAR FACTURA EN PDF (FPDF)
    // ==========================================
    if (class_exists('FPDF')) {
        class PDF extends FPDF {
            function Header() {
                $this->SetFont('Arial', 'B', 16);
                $this->Cell(0, 10, utf8_decode('MALES MOTORS - COMPROBANTE DE PAGO'), 0, 1, 'C');
                $this->Ln(5);
            }
            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial', 'I', 8);
                $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'C');
            }
        }

        $pdf = new PDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 11);

        $pdf->Cell(0, 8, utf8_decode('Código de Pedido: ') . $codigo_pedido, 0, 1);
        $pdf->Cell(0, 8, utf8_decode('Cliente: ') . ($cliente['nombres'] ?? '') . ' ' . ($cliente['apellidos'] ?? ''), 0, 1);
        $pdf->Cell(0, 8, utf8_decode('Dirección de Envío: ') . $direccion_envio, 0, 1);
        $pdf->Cell(0, 8, 'Fecha: ' . date('Y-m-d H:i:s'), 0, 1);
        $pdf->Ln(8);

        // Tabla de productos
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(85, 10, 'Producto', 1, 0, 'C');
        $pdf->Cell(25, 10, 'Cantidad', 1, 0, 'C');
        $pdf->Cell(40, 10, 'P. Unitario', 1, 0, 'C');
        $pdf->Cell(40, 10, 'Subtotal', 1, 0, 'C');
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 10);
        foreach ($_SESSION['carrito'] as $item) {
            $subtotal_item = $item['precio'] * $item['cantidad'];
            $pdf->Cell(85, 9, utf8_decode($item['descripcion']), 1);
            $pdf->Cell(25, 9, $item['cantidad'], 1, 0, 'C');
            $pdf->Cell(40, 9, '$' . number_format($item['precio'], 2), 1, 0, 'R');
            $pdf->Cell(40, 9, '$' . number_format($subtotal_item, 2), 1, 0, 'R');
            $pdf->Ln();
        }

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(150, 10, 'TOTAL A PAGAR:', 1, 0, 'R');
        $pdf->Cell(40, 10, '$' . number_format($total, 2), 1, 1, 'R');

        $pdf_path = '../uploads/factura_' . $codigo_pedido . '.pdf';
        $pdf->Output('F', $pdf_path);
    }

    // ==========================================
    // 4. ENVÍO DE CORREO CON PHPMailer Y PDF ADJUNTO
    // ==========================================
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // Configurado para servidor SMTP real (ej. Gmail)
            $mail->SMTPAuth   = true;
            $mail->Username   = 'tucorreo@gmail.com'; // Reemplaza con tu correo real
            $mail->Password   = 'tu_contrasena_de_aplicacion'; // Reemplaza con tu contraseña de aplicación
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('tucorreo@gmail.com', 'Males Motors E-Commerce');
            $user_email = $cliente['email'] ?? ($cliente['correo'] ?? '');
            if(!empty($user_email)) {
                $mail->addAddress($user_email, $cliente['nombres'] ?? 'Cliente');
            }

            if (!empty($pdf_path) && file_exists($pdf_path)) {
                $mail->addAttachment($pdf_path);
            }

            $mail->isHTML(true);
            $mail->Subject = 'Comprobante de Compra - ' . $codigo_pedido;
            $mail->Body    = 'Hola <b>' . htmlspecialchars($cliente['nombres'] ?? 'Cliente') . '</b>,<br><br>Gracias por tu compra en Males Motors. Adjunto encontrarás la factura en formato PDF de tu pedido <b>' . $codigo_pedido . '</b>.<br><br>Monto total: <b>$' . number_format($total, 2) . '</b><br><br>Atentamente,<br>Equipo Males Motors.';

            $mail->send();
        } catch (Exception $e) {
            // El proceso de compra no se detiene si el servidor de correo local no responde
        }
    }

    // Limpiar el carrito de compras
    unset($_SESSION['carrito']);

    header("Location: ../checkout.php?exito=1&pedido=" . $codigo_pedido);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: ../checkout.php?error=" . urlencode($e->getMessage()));
    exit;
}
?>