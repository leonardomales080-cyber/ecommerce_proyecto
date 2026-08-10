<?php
// admin/exportar_excel.php
require_once '../config/conexion.php';
require_once '../config/auth.php';
require_once '../vendor/autoload.php';
verificarAdmin();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    $query = "SELECT * FROM pedidos WHERE estado = 'Completado' ORDER BY id DESC";
    $stmt = $pdo->query($query);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $ventas = [];
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Ventas Completadas');

// Encabezados
$sheet->setCellValue('A1', 'ID Venta');
$sheet->setCellValue('B1', 'Código de Pedido');
$sheet->setCellValue('C1', 'Cliente (Usuario ID)');
$sheet->setCellValue('D1', 'Total ($)');
$sheet->setCellValue('E1', 'Fecha');
$sheet->setCellValue('F1', 'Estado');

// Estilo para la cabecera
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['argb' => '198754']
    ]
];
$sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

// Llenar datos
$rowNum = 2;
if (count($ventas) > 0) {
    foreach ($ventas as $v) {
        $sheet->setCellValue('A' . $rowNum, $v['id'] ?? '');
        $sheet->setCellValue('B' . $rowNum, $v['codigo_pedido'] ?? '');
        $sheet->setCellValue('C' . $rowNum, $v['usuario_id'] ?? '');
        $sheet->setCellValue('D' . $rowNum, $v['monto_total'] ?? $v['total'] ?? 0);
        $sheet->setCellValue('E' . $rowNum, $v['fecha_pedido'] ?? $v['fecha'] ?? '');
        $sheet->setCellValue('F' . $rowNum, $v['estado'] ?? 'Completado');
        
        // Dar formato de moneda a la columna D
        $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode('$#,##0.00');
        $rowNum++;
    }
} else {
    $sheet->setCellValue('A2', 'No hay ventas completadas registradas.');
    $sheet->mergeCells('A2:F2');
}

// Autoajustar el ancho de las columnas
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Limpiar buffer de salida para evitar corrupción del archivo descargado
if (ob_get_length()) {
    ob_end_clean();
}

// Configurar los encabezados para la descarga del archivo Excel real
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Reporte_Ventas_Completadas_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;