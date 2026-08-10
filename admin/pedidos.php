<?php
// admin/pedidos.php
require_once '../config/conexion.php';
require_once '../config/auth.php';
verificarAdmin();

$error_bd = "";
$pedidos = [];

try {
    $query = "SELECT * FROM pedidos ORDER BY id DESC";
    $stmt = $pdo->query($query);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $error_bd = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos | Panel Admin- E-Commerce M.A.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-gauge me-2"></i>Panel Admin</a>
            <div class="d-flex align-items-center">
                <a href="index.php" class="btn btn-outline-light btn-sm me-2">Regresar al Panel</a>
                <a href="../config/logout.php" class="btn btn-outline-danger btn-sm">Salir</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0"><i class="fa-solid fa-clipboard-list me-2"></i>Gestión de Pedidos</h2>
            <span class="badge bg-primary fs-6">Total: <?= count($pedidos) ?> pedidos</span>
        </div>

        <?php if (!empty($error_bd)): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Error en la Base de Datos:</strong> <?= htmlspecialchars($error_bd) ?>
            </div>
        <?php endif; ?>

        <!-- Alerta flotante para feedback de AJAX -->
        <div id="alertaAjax" class="alert d-none" role="alert"></div>

        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID Pedido</th>
                            <th>Usuario ID</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th>Estado Actual</th>
                            <th>Acción (Cambiar Estado)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($pedidos) > 0): ?>
                            <?php foreach($pedidos as $ped): ?>
                            <tr id="fila-pedido-<?= $ped['id'] ?? 0 ?>">
                                <td class="fw-bold">
                                    #<?= $ped['id'] ?? 'N/D' ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($ped['codigo_pedido'] ?? '') ?></small>
                                </td>
                                <td>
                                    <div>Usuario ID: <?= htmlspecialchars($ped['usuario_id'] ?? 'N/D') ?></div>
                                </td>
                                <td class="text-success fw-bold">$<?= number_format($ped['monto_total'] ?? $ped['total'] ?? 0, 2) ?></td>
                                <td><?= htmlspecialchars($ped['fecha_pedido'] ?? $ped['fecha'] ?? 'N/D') ?></td>
                                <td>
                                    <?php 
                                        $estadoActual = !empty($ped['estado']) ? trim($ped['estado']) : 'Pendiente';
                                    ?>
                                    <span id="badge-estado-<?= $ped['id'] ?? 0 ?>" class="badge 
                                        <?php 
                                            echo match($estadoActual) {
                                                'Completado' => 'bg-success',
                                                'Enviado' => 'bg-info text-dark',
                                                'Procesando' => 'bg-warning text-dark',
                                                'Cancelado' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        ?>">
                                        <?= htmlspecialchars($estadoActual) ?>
                                    </span>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm w-auto d-inline-block" onchange="cambiarEstado(<?= $ped['id'] ?? 0 ?>, this.value)">
                                        <?php 
                                        $opciones = ['Pendiente', 'Procesando', 'Enviado', 'Completado', 'Cancelado'];
                                        foreach($opciones as $opt):
                                        ?>
                                            <option value="<?= $opt ?>" <?= $estadoActual === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No hay registros encontrados en la tabla 'pedidos'.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Script JavaScript para peticiones asíncronas AJAX (Fetch API) -->
    <script>
        function cambiarEstado(pedidoId, nuevoEstado) {
            const alerta = document.getElementById('alertaAjax');
            
            const formData = new FormData();
            formData.append('id', pedidoId);
            formData.append('estado', nuevoEstado);

            fetch('actualizar_estado.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alerta.classList.remove('d-none', 'alert-success', 'alert-danger');
                if (data.success) {
                    alerta.classList.add('alert-success');
                    alerta.textContent = data.message;

                    const badge = document.getElementById(`badge-estado-${pedidoId}`);
                    badge.textContent = nuevoEstado;
                    badge.className = "badge ";
                    
                    switch(nuevoEstado) {
                        case 'Completado': badge.classList.add('bg-success'); break;
                        case 'Enviado': badge.classList.add('bg-info', 'text-dark'); break;
                        case 'Procesando': badge.classList.add('bg-warning', 'text-dark'); break;
                        case 'Cancelado': badge.classList.add('bg-danger'); break;
                        default: badge.classList.add('bg-secondary');
                    }
                } else {
                    alerta.classList.add('alert-danger');
                    alerta.textContent = data.message || 'Error al actualizar el estado.';
                }

                setTimeout(() => {
                    alerta.classList.add('d-none');
                }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                alerta.classList.remove('d-none', 'alert-success');
                alerta.classList.add('alert-danger');
                alerta.textContent = 'Error de conexión con el servidor.';
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>