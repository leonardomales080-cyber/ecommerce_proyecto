<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function procesarFacturaYCorreo($pdo, $user_id, $codigo_pedido, $carrito_items) {
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', realpath(__DIR__ . '/..'));
    }

    require_once BASE_PATH . '/fpdf/fpdf.php';
    require_once BASE_PATH . '/vendor/autoload.php';

    date_default_timezone_set('America/Guayaquil');

    $stmtU = $pdo->prepare("SELECT correo, nombres, apellidos FROM usuarios WHERE id = ?");
    $stmtU->execute([$user_id]);
    $cliente = $stmtU->fetch(PDO::FETCH_ASSOC);

    $user_email = $cliente['correo'] ?? 'cliente@ecommerce.com';
    $cliente_nombre = trim(($cliente['nombres'] ?? '') . ' ' . ($cliente['apellidos'] ?? '')) ?: 'Cliente';

    class PDF_Factura extends FPDF {
        
        function Header() {
            $watermark_path = BASE_PATH . '/admin/assets/img/logo_marca_agua.png';
            if (file_exists($watermark_path)) {
                $this->Image($watermark_path, 35, 95, 140); 
            }

            $logo_path = BASE_PATH . '/admin/assets/img/logo_E_Commerce.png';
            if (file_exists($logo_path)) {
                $this->Image($logo_path, 10, 10, 55); 
            }

            $this->SetXY(110, 12);
            $this->SetFont('Arial','B',15);
            $this->Cell(90, 7, utf8_decode('E-Commerce M.A.'), 0, 1, 'R');
            
            $this->SetX(110);
            $this->SetFont('Arial','',8);
            $this->Cell(90, 4, utf8_decode('Tecnología, Ropa, Hogar y Oficina'), 0, 1, 'R');
            
            $this->SetX(110);
            $this->Cell(90, 4, utf8_decode('Comprobante de Venta Oficial'), 0, 1, 'R');
            
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
    
    $pdf->Ln(5);
    $pdf->Cell(0, 6, utf8_decode('Código de Pedido: ') . $codigo_pedido, 0, 1);
    $pdf->Cell(0, 6, utf8_decode('Cliente: ') . utf8_decode($cliente_nombre), 0, 1);
    $pdf->Cell(0, 6, utf8_decode('Fecha: ') . date('Y-m-d H:i:s'), 0, 1);
    $pdf->Ln(8);

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

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(155, 9, utf8_decode('TOTAL A PAGAR:'), 1, 0, 'R', true);
    $pdf->Cell(35, 9, '$' . number_format($total_general, 2), 1, 1, 'R', true);

    // Sección de Contacto en el PDF
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 7, utf8_decode('Información de Contacto:'), 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 5, utf8_decode('Email: contacto@ecommerce.com'), 0, 1);
    $pdf->Cell(0, 5, utf8_decode('Teléfono: (06) 292-XXXX'), 0, 1);
    $pdf->Cell(0, 5, utf8_decode('Dirección: Atuntaqui, Imbabura, Ecuador'), 0, 1);

    $upload_dir = BASE_PATH . '/uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $pdf_filename = 'factura_' . $codigo_pedido . '.pdf';
    $pdf_path = $upload_dir . $pdf_filename;
    $pdf->Output('F', $pdf_path);

    // --- ENVÍO REAL MEDIANTE API FASTAPI ---
    $url_api = "http://127.0.0.1:8000/enviar-factura-correo/";
    
    $datos_post = [
        'correo' => $user_email,
        'nombre_cliente' => $cliente_nombre,
        'codigo_pedido' => $codigo_pedido,
        'pdf_file' => new CURLFile($pdf_path)
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_api);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datos_post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $respuesta = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Validar si la API de Python respondió con éxito (Código 200)
    $mail_enviado = ($http_code === 200) ? 1 : 0;

    return $mail_enviado;
}