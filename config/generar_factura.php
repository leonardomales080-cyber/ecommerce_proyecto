<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function procesarFacturaYCorreo($pdo, $user_id, $codigo_pedido, $carrito_items) {
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', realpath(__DIR__ . '/..'));
    }

    require_once BASE_PATH . '/fpdf/fpdf.php';
    require_once BASE_PATH . '/vendor/autoload.php';

    // Establecer la zona horaria correcta para Ecuador
    date_default_timezone_set('America/Guayaquil');

    $stmtU = $pdo->prepare("SELECT correo, nombres, apellidos FROM usuarios WHERE id = ?");
    $stmtU->execute([$user_id]);
    $cliente = $stmtU->fetch(PDO::FETCH_ASSOC);

    $user_email = $cliente['correo'] ?? 'cliente@ecommerce.com';
    $cliente_nombre = trim(($cliente['nombres'] ?? '') . ' ' . ($cliente['apellidos'] ?? '')) ?: 'Cliente';

    class PDF_Factura extends FPDF {
        
        function Header() {
            // 1. Marca de agua centrada en el fondo (Asegúrate de que el archivo 'logo_marca_agua.png' 
            // tenga transparencia aplicada previamente en un editor de imágenes para que luzca translúcida).
            $watermark_path = BASE_PATH . '/admin/assets/img/logo_marca_agua.png';
            if (file_exists($watermark_path)) {
                $this->Image($watermark_path, 35, 95, 140); 
            }

            // 2. Logo corporativo principal grande a la izquierda
            $logo_path = BASE_PATH . '/admin/assets/img/logo_E_Commerce.png';
            if (file_exists($logo_path)) {
                $this->Image($logo_path, 10, 10, 55); 
            }

            // 3. Información de la empresa a la derecha con el texto completo restaurado
            $this->SetXY(110, 12);
            $this->SetFont('Arial','B',15);
            $this->Cell(90, 7, utf8_decode('E-Commerce M.A.'), 0, 1, 'R');
            
            $this->SetX(110);
            $this->SetFont('Arial','',8);
            $this->Cell(90, 4, utf8_decode('Tecnología, Ropa, Hogar y Oficina'), 0, 1, 'R');
            
            $this->SetX(110);
            $this->Cell(90, 4, utf8_decode('Comprobante de Venta Oficial'), 0, 1, 'R');
            
            // Línea divisoria de cabecera
            $this->SetY(28);
            $this->SetDrawColor(200, 200, 200);
            $this->Line(10, 28, 200, 28);
        }

        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
        }
    }

    $pdf = new PDF_Factura();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 11);
    
    // Espaciado adecuado tras la cabecera
    $pdf->Ln(5);

    // Información general del pedido con hora local de Ecuador
    $pdf->Cell(0, 6, utf8_decode('Código de Pedido: ') . $codigo_pedido, 0, 1);
    $pdf->Cell(0, 6, utf8_decode('Cliente: ') . utf8_decode($cliente_nombre), 0, 1);
    $pdf->Cell(0, 6, utf8_decode('Fecha: ') . date('Y-m-d H:i:s'), 0, 1);
    $pdf->Ln(8);

    // Cabecera de la tabla de productos
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(235, 235, 235);
    $pdf->Cell(100, 9, 'Producto', 1, 0, 'L', true);
    $pdf->Cell(25, 9, 'Cant', 1, 0, 'C', true);
    $pdf->Cell(30, 9, 'Precio', 1, 0, 'R', true);
    $pdf->Cell(35, 9, 'Subtotal', 1, 1, 'R', true);

    $pdf->SetFont('Arial', '', 10);
    $total_general = 0;

    foreach ($carrito_items as $item) {
        $subtotal = $item['precio'] * $item['cantidad'];
        $total_general += $subtotal;

        $pdf->Cell(100, 8, utf8_decode(substr($item['descripcion'], 0, 45)), 1);
        $pdf->Cell(25, 8, $item['cantidad'], 1, 0, 'C');
        $pdf->Cell(30, 8, '$' . number_format($item['precio'], 2), 1, 0, 'R');
        $pdf->Cell(35, 8, '$' . number_format($subtotal, 2), 1, 1, 'R');
    }

    // Fila del Total General al final de la tabla
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(155, 9, utf8_decode('TOTAL A PAGAR:'), 1, 0, 'R', true);
    $pdf->Cell(35, 9, '$' . number_format($total_general, 2), 1, 1, 'R', true);

    $upload_dir = BASE_PATH . '/uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $pdf_filename = 'factura_' . $codigo_pedido . '.pdf';
    $pdf_path = $upload_dir . $pdf_filename;
    $pdf->Output('F', $pdf_path);

    $mail_enviado = 0;
    if (file_exists($pdf_path)) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'leonardomales080@gmail.com';
            $mail->Password   = 'xloe nldr ttxw krvu';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
            $mail->Port       = 465;

            $mail->setFrom('leonardomales080@gmail.com', 'E-Commerce M.A.');
            $mail->addAddress($user_email, $cliente_nombre);
            $mail->isHTML(true);
            $mail->Subject = 'Comprobante de Compra - ' . $codigo_pedido;
            $mail->Body    = 'Hola <b>' . htmlspecialchars($cliente_nombre) . '</b>, gracias por tu compra. Adjuntamos tu factura oficial.';
            $mail->addAttachment($pdf_path, $pdf_filename);

            if ($mail->send()) {
                $mail_enviado = 1;
            }
        } catch (Exception $e) {
            $mail_enviado = 0;
        }
    }

    return $mail_enviado;
}