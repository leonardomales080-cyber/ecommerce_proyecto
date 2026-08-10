<?php
// admin/productos.php
require_once '../config/conexion.php';
require_once '../vendor/autoload.php';
session_start();

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'ADMIN') {
    header("Location: ../login.php");
    exit;
}

$mensaje = "";
$error = "";

// 0.A PROCESAR CREACIÓN DE NUEVA CATEGORÍA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_categoria'])) {
    $nueva_categoria = trim($_POST['nombre_categoria']);
    if (!empty($nueva_categoria)) {
        try {
            $stmtCat = $pdo->prepare("INSERT INTO categorias (nombre) VALUES (?)");
            $stmtCat->execute([$nueva_categoria]);
            $mensaje = "¡Categoría '$nueva_categoria' agregada correctamente!";
        } catch (\PDOException $e) {
            $error = "Error al registrar la categoría (puede que ya exista).";
        }
    } else {
        $error = "El nombre de la categoría no puede estar vacío.";
    }
}

// 0.B PROCESAR EDICIÓN DE CATEGORÍA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_categoria'])) {
    $id_cat = intval($_POST['categoria_id']);
    $nombre_cat = trim($_POST['nombre_categoria']);
    if (!empty($nombre_cat) && $id_cat > 0) {
        try {
            $stmtUpd = $pdo->prepare("UPDATE categorias SET nombre = ? WHERE id = ?");
            $stmtUpd->execute([$nombre_cat, $id_cat]);
            $mensaje = "¡Categoría actualizada correctamente!";
        } catch (\PDOException $e) {
            $error = "Error al actualizar la categoría.";
        }
    }
}

// 0.C PROCESAR ELIMINACIÓN DE CATEGORÍA
if (isset($_GET['eliminar_categoria'])) {
    $id_cat_eliminar = intval($_GET['eliminar_categoria']);
    try {
        // Verificar si tiene productos asociados
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id = ?");
        $stmtCheck->execute([$id_cat_eliminar]);
        $totalProds = $stmtCheck->fetchColumn();

        if ($totalProds > 0) {
            $error = "No se puede eliminar esta categoría porque tiene $totalProds producto(s) asociado(s). Reasigne o elimine esos productos primero.";
        } else {
            $stmtDelCat = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
            $stmtDelCat->execute([$id_cat_eliminar]);
            header("Location: productos.php");
            exit;
        }
    } catch (\PDOException $e) {
        $error = "Error al intentar eliminar la categoría.";
    }
}

// 1. PROCESAR ELIMINACIÓN DE PRODUCTO
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

// 2. PROCESAR CREACIÓN (AGREGAR PRODUCTO INDIVIDUAL)
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
        $error = "Por favor complete todos los campos obligatorios del producto.";
    }
}

// 3. PROCESAR IMPORTACIÓN MASIVA DESDE EXCEL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['importar_excel'])) {
    if (isset($_FILES['archivo_excel']) && $_FILES['archivo_excel']['error'] === UPLOAD_ERR_OK) {
        $archivoTmp = $_FILES['archivo_excel']['tmp_name'];
        $nombreArchivo = $_FILES['archivo_excel']['name'];
        $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));

        if (in_array($ext, ['xlsx', 'xls', 'csv'])) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivoTmp);
                $sheet = $spreadsheet->getActiveSheet();
                $filas = $sheet->toArray();

                unset($filas[0]); // Omitir cabecera

                $importados = 0;
                foreach ($filas as $fila) {
                    $cat_id = intval($fila[0] ?? 1);
                    $cod    = trim($fila[1] ?? '');
                    $desc   = trim($fila[2] ?? '');
                    $prec   = floatval($fila[3] ?? 0);
                    $stk    = intval($fila[4] ?? 10);

                    if (!empty($cod) && !empty($desc) && $prec > 0) {
                        $stmtCheck = $pdo->prepare("SELECT id FROM categorias WHERE id = ?");
                        $stmtCheck->execute([$cat_id]);
                        if ($stmtCheck->fetch()) {
                            $stmtIns = $pdo->prepare("INSERT INTO productos (categoria_id, codigo, descripcion, precio, stock, imagen) VALUES (?, ?, ?, ?, ?, 'default.png')");
                            if ($stmtIns->execute([$cat_id, $cod, $desc, $prec, $stk])) {
                                $importados++;
                            }
                        }
                    }
                }
                $mensaje = "¡Se importaron $importados productos correctamente desde Excel!";
            } catch (Exception $e) {
                $error = "Error al procesar el archivo Excel: " . $e->getMessage();
            }
        } else {
            $error = "Formato de archivo no válido. Sube un archivo .xlsx, .xls o .csv";
        }
    } else {
        $error = "Por favor selecciona un archivo Excel válido para importar.";
    }
}

$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
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
                <a href="../config/logout.php" class="btn btn-outline-danger btn-sm">Salir</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Gestión de Inventario (Productos y Categorías)</h2>
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

        <!-- Sección de Administración de Categorías -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold m-0"><i class="fa-solid fa-tags text-primary me-2"></i>Administrar Categorías Existentes</h5>
                <button type="button" class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria">
                    <i class="fa-solid fa-plus me-1"></i> Nueva Categoría
                </button>
            </div>
            <div class="table-responsive">
                <table class="table align-middle table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre de Categoría</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($categorias as $cat): ?>
                        <tr>
                            <td><?= $cat['id'] ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($cat['nombre']) ?></td>
                            <td>
                                <!-- Botón Editar Categoría -->
                                <button class="btn btn-outline-warning btn-sm" onclick="abrirModalEditarCat(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['nombre'], ENT_QUOTES) ?>')">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <!-- Botón Eliminar Categoría -->
                                <a href="productos.php?eliminar_categoria=<?= $cat['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar esta categoría?');">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Herramientas de Excel -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-file-excel text-success me-2"></i>Herramientas Excel (PhpSpreadsheet)</h5>
            <div class="row align-items-center g-3">
                <div class="col-md-6">
                    <p class="text-muted mb-2 small">Descarga un reporte general con todas las ventas completadas en formato Excel.</p>
                    <a href="exportar_excel.php" class="btn btn-success fw-semibold">
                        <i class="fas fa-download me-2"></i>Exportar Reporte de Ventas
                    </a>
                </div>
                <div class="col-md-6 border-start">
                    <p class="text-muted mb-2 small">Importa productos de manera masiva cargando un archivo `.xlsx` (Columnas: ID Categoría, Código, Descripción, Precio, Stock).</p>
                    <form action="productos.php" method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                        <input type="hidden" name="importar_excel" value="1">
                        <input type="file" name="archivo_excel" accept=".xlsx, .xls, .csv" class="form-control form-control-sm" required>
                        <button type="submit" class="btn btn-info text-white fw-semibold text-nowrap">
                            <i class="fas fa-upload me-1"></i> Importar
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Formulario para Agregar Producto -->
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
                                            $rutaFinal = '../uploads/' . $imgDB;
                                        ?>
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

    <!-- Modal para Agregar Nueva Categoría -->
    <div class="modal fade" id="modalNuevaCategoria" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow p-3">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Registrar Nueva Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="productos.php" method="POST">
                    <input type="hidden" name="agregar_categoria" value="1">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la Categoría</label>
                            <input type="text" name="nombre_categoria" class="form-control" required placeholder="Ej. Deportes, Herramientas, etc.">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-semibold">Guardar Categoría</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Categoría -->
    <div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow p-3">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Editar Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="productos.php" method="POST">
                    <input type="hidden" name="editar_categoria" value="1">
                    <input type="hidden" name="categoria_id" id="edit_categoria_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nuevo Nombre</label>
                            <input type="text" name="nombre_categoria" id="edit_nombre_categoria" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning btn-sm fw-semibold text-dark">Actualizar Categoría</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Zoom de Imagen -->
    <div class="modal fade" id="modalZoomImagen" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-3 rounded-4 shadow">
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

        function abrirModalEditarCat(id, nombre) {
            document.getElementById('edit_categoria_id').value = id;
            document.getElementById('edit_nombre_categoria').value = nombre;
            var editModal = new bootstrap.Modal(document.getElementById('modalEditarCategoria'));
            editModal.show();
        }
    </script>
</body>
</html>