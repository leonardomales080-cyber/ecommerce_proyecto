<?php
// registro.php
require_once 'config/conexion.php';
session_start();

$error = "";
$exito = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = trim($_POST['cedula']);
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $telefono = trim($_POST['telefono']);
    $correo = trim($_POST['correo']);
    $password = $_POST['password'];
    $direccion = trim($_POST['direccion']);
    
    if (!empty($cedula) && !empty($nombres) && !empty($apellidos) && !empty($correo) && !empty($password)) {
        try {
            // Verificar si el correo o cédula ya existen
            $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ? OR cedula = ?");
            $stmtCheck->execute([$correo, $cedula]);
            
            if ($stmtCheck->rowCount() > 0) {
                $error = "El correo electrónico o la cédula ya se encuentran registrados.";
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $rol = 'CLIENTE';
                
                $sql = "INSERT INTO usuarios (cedula, nombres, apellidos, telefono, correo, password, direccion, rol) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$cedula, $nombres, $apellidos, $telefono, $correo, $passwordHash, $direccion, $rol]);
                
                $exito = "¡Registro exitoso! Ya puedes iniciar sesión.";
            }
        } catch (\PDOException $e) {
            $error = "Error en la base de datos: " . $e->getMessage();
        }
    } else {
        $error = "Por favor, completa todos los campos obligatorios.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Clientes | E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <h2 class="fw-bold text-center mb-4">Registro de Cliente</h2>

                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if(!empty($exito)): ?>
                        <div class="alert alert-success">
                            <?= $exito ?> <a href="login.php" class="fw-bold">Inicia sesión aquí</a>
                        </div>
                    <?php endif; ?>

                    <form action="registro.php" method="POST" autocomplete="off">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cédula o RUC</label>
                                <input type="text" name="cedula" class="form-control" required autocomplete="off">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control" autocomplete="off">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombres</label>
                                <input type="text" name="nombres" class="form-control" required autocomplete="off">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Apellidos</label>
                                <input type="text" name="apellidos" class="form-control" required autocomplete="off">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Correo Electrónico</label>
                            <input type="email" name="correo" class="form-control" required autocomplete="new-email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="password" class="form-control" required autocomplete="new-password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dirección</label>
                            <textarea name="direccion" class="form-control" rows="2" autocomplete="off"></textarea>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 fw-semibold py-2">Registrarse</button>
                    </form>

                    <div class="text-center mt-3">
                        <small class="text-muted">¿Ya tienes una cuenta? <a href="login.php" class="text-decoration-none fw-semibold">Inicia sesión aquí</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>