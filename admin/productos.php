<?php
// admin/productos.php
require_once '../config/conexion.php';
session_start();

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'ADMIN') {
    header("Location: ../login.php");
    exit;
}

$mensaje = "";
$error = "";

// 1. PROCESAR ELIMINACIÓN
if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    try {
        $stmtDel = $pdo->prepare("DELETE FROM productos WHERE id = ?");
        $stmtDel->execute([$id_eliminar]);
        header("Location: productos.php");
        exit;
    } catch (\PDOException $e) {
        $error = "No se puede eliminar el producto porque está asociado a pedidos.";
    }
}

// 2. PROCESAR CREACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_producto'])) {
    $categoria_id = intval($_POST['categoria_id']);
    $codigo = trim($_POST['codigo']);
    $descripcion = trim($_POST['descripcion']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    
    $nombre_imagen = 'default.png';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['imagen']['tmp_name'];
        $fileName = $_FILES['imagen']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $nuevoNombreImagen = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = '../uploads/';
            
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            if(move_uploaded_file($fileTmpPath, $uploadFileDir . $nuevoNombreImagen)) {
                $nombre_imagen = $nuevoNombreImagen;
            }
        }
    }

    if (!empty($codigo) && !empty($descripcion) && $precio > 0) {
        try {
            $sql = "INSERT INTO productos (categoria_id, codigo, descripcion, precio, stock, imagen) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$categoria_id, $codigo, $descripcion, $precio, $stock, $nombre_imagen]);
            header("Location: productos.php");
            exit;
        } catch (\PDOException $e) {
            $error = "Error al guardar en la base de datos: " . $e->getMessage();
        }
    } else {
        $error = "Por favor complete todos los campos obligatorios.";
    }
}

$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos ordenados por categoría y por código
$productos = $pdo->query("SELECT p.*, c.nombre AS categoria FROM productos p JOIN categorias c ON p.categoria_id = c.id ORDER BY c.nombre ASC, p.codigo ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .img-thumbnail-zoom {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .img-thumbnail-zoom:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-gauge me-2"></i>Panel Admin</a>
            <div class="d-flex align-items-center">
                <a href="../index.php" class="btn btn-outline-light btn-sm me-2">Ver Tienda</a>
                <a href="../logout.php" class="btn btn-outline-danger btn-sm">Salir</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Botón de Retroceso y Título -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Gestión de Inventario (Productos)</h2>
            <a href="index.php" class="btn btn-secondary shadow-sm">
                <i class="fa-solid fa-arrow-left me-2"></i>Regresar al Panel
            </a>
        </div>

        <?php if(!empty($mensaje)): ?>
            <div class="alert alert-success"><?= $mensaje ?></div>
        <?php endif; ?>
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <!-- Formulario para Agregar -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-5">
            <h5 class="fw-bold mb-3">Agregar Nuevo Producto</h5>
            <form action="productos.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="agregar_producto" value="1">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Código</label>
                        <input type="text" name="codigo" class="form-control" required placeholder="Ej. TEC-011">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Categoría</label>
                        <select name="categoria_id" class="form-select" required>
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Descripción del Producto</label>
                        <input type="text" name="descripcion" class="form-control" required placeholder="Nombre o detalles">
                    </div>
                </div>
                <div class="row align-items-end">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Precio ($)</label>
                        <input type="number" step="0.01" name="precio" class="form-control" required placeholder="0.00">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Stock</label>
                        <input type="number" name="stock" class="form-control" required value="10">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Imagen del Producto</label>
                        <input type="file" name="imagen" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-2 mb-3">
                        <button type="submit" class="btn btn-dark w-100 fw-semibold">Guardar Producto</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Listado Separado por Categorías -->
        <?php 
        $productosPorCategoria = [];
        foreach($productos as $prod) {
            $productosPorCategoria[$prod['categoria']][] = $prod;
        }
        ?>

        <h4 class="fw-bold mb-3">Lista de Productos Registrados (<?= count($productos) ?>)</h4>

        <?php if(count($productosPorCategoria) > 0): ?>
            <?php foreach($productosPorCategoria as $nombreCat => $listaProds): ?>
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-folder-open me-2"></i><?= htmlspecialchars($nombreCat) ?> <span class="badge bg-secondary fs-6"><?= count($listaProds) ?> productos</span></h5>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Imagen</th>
                                    <th>Código</th>
                                    <th>Descripción</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($listaProds as $prod): ?>
                                <tr>
                                    <td>
                                        <?php 
                                            $imgDB = $prod['imagen'] ?? 'default.png';
                                            if (strpos($imgDB, '/') !== false) {
                                                $rutaFinal = '../uploads/' . $imgDB;
                                            } else {
                                                $rutaFinal = '../uploads/' . $imgDB;
                                            }
                                        ?>
                                        <!-- Miniatura con efecto hover y evento para abrir en modal -->
                                        <img src="<?= htmlspecialchars($rutaFinal) ?>" alt="Img" class="rounded img-thumbnail-zoom" width="45" height="45" style="object-fit: cover;" 
                                             onclick="abrirModalZoom('<?= htmlspecialchars($rutaFinal) ?>', '<?= htmlspecialchars($prod['descripcion']) ?>')"
                                             onerror="this.onerror=null; this.src='https://via.placeholder.com/45';">
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($prod['codigo']) ?></td>
                                    <td><?= htmlspecialchars($prod['descripcion']) ?></td>
                                    <td class="text-success fw-bold">$<?= number_format($prod['precio'], 2) ?></td>
                                    <td><?= $prod['stock'] ?></td>
                                    <td>
                                        <a href="productos.php?eliminar=<?= $prod['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este producto?');">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <p class="text-muted mb-0">No hay productos registrados.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal para Zoom de Imagen -->
    <div class="modal fade" id="modalZoomImagen" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-3">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="tituloModalZoom">Vista Previa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <img id="imagenModalAmpliada" src="" alt="Imagen Ampliada" class="img-fluid rounded shadow" style="max-height: 400px; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function abrirModalZoom(urlImagen, descripcion) {
            document.getElementById('imagenModalAmpliada').src = urlImagen;
            document.getElementById('tituloModalZoom').innerText = descripcion;
            var myModal = new bootstrap.Modal(document.getElementById('modalZoomImagen'));
            myModal.show();
        }
    </script>
</body>
</html>