<?php
// admin/reportes.php
require_once '../config/conexion.php';
require_once '../config/auth.php';
verificarAdmin();

// Forzar la descarga del archivo como una hoja de cálculo de Excel (.xls)
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Reporte_Pedidos_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

try {
    $query = "SELECT * FROM pedidos ORDER BY id DESC";
    $stmt = $pdo->query($query);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $pedidos = [];
}
?>
<table border="1">
    <thead>
        <tr style="background-color: #f2f2f2; font-weight: bold;">
            <th>ID Pedido</th>
            <th>Código de Pedido</th>
            <th>Usuario ID</th>
            <th>Monto Total</th>
            <th>Fecha de Pedido</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($pedidos) > 0): ?>
            <?php foreach ($pedidos as $ped): ?>
                <tr>
                    <td><?= $ped['id'] ?? '' ?></td>
                    <td><?= $ped['codigo_pedido'] ?? '' ?></td>
                    <td><?= $ped['usuario_id'] ?? '' ?></td>
                    <td><?= $ped['monto_total'] ?? $ped['total'] ?? 0 ?></td>
                    <td><?= $ped['fecha_pedido'] ?? $ped['fecha'] ?? '' ?></td>
                    <td><?= $ped['estado'] ?? 'Pendiente' ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No hay registros de pedidos disponibles.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>