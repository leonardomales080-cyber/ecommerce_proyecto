<?php
// login.php
require_once 'config/conexion.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($correo) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ?");
            $stmt->execute([$correo]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_nombres'] = $user['nombres'];
                    
                    if ((isset($user['rol_id']) && $user['rol_id'] == 1) || (isset($user['rol']) && strtoupper($user['rol']) === 'ADMIN')) {
                        $_SESSION['user_rol'] = 'ADMIN';
                        header("Location: admin/index.php");
                    } else {
                        $_SESSION['user_rol'] = 'CLIENTE';
                        header("Location: tienda.php");
                    }
                    exit;
                } else {
                    $error = "La contraseña es incorrecta.";
                }
            } else {
                $error = "No existe un usuario con ese correo.";
            }
        } catch (\PDOException $e) {
            $error = "Error en la base de datos: " . $e->getMessage();
        }
    } else {
        $error = "Por favor, completa todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <div class="text-center mb-4">
                        <i class="fa-solid fa-lock fa-2x text-dark mb-2"></i>
                        <h2 class="fw-bold">Iniciar Sesión</h2>
                        <p class="text-muted small">Ingresa a tu cuenta de E-Commerce</p>
                    </div>

                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger py-2 small text-center"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form action="login.php" method="POST" autocomplete="off">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Correo Electrónico</label>
                            <input type="email" name="correo" class="form-control" required value="admin@ecommerce.com">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Contraseña</label>
                            <input type="password" name="password" class="form-control" required value="123456">
                        </div>
                        <button type="submit" class="btn btn-dark w-100 fw-semibold py-2 rounded-pill shadow-sm mb-3">Ingresar</button>
                    </form>

                    <div class="text-center">
                        <small class="text-muted">¿No tienes una cuenta? <a href="registro.php" class="text-decoration-none fw-semibold">Regístrate aquí</a></small>
                        <div class="mt-2">
                            <a href="index.php" class="text-decoration-none small text-muted"><i class="fa-solid fa-arrow-left me-1"></i>Volver al inicio</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>