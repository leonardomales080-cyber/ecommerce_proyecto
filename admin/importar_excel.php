<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 1) {
    exit("Acceso no autorizado");
}

require_once '../config/conexion.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_excel'])) {
    $archivoTmp = $_FILES['archivo_excel']['tmp_name'];
    $nombreArchivo = $_FILES['archivo_excel']['name'];
    $ext = pathinfo($nombreArchivo, PATHINFO_EXTENSION);

    if (in_array($ext, ['xlsx', 'xls', 'csv'])) {
        try {
            $spreadsheet = IOFactory::load($archivoTmp);
            $sheet = $spreadsheet->getActiveSheet();
            $filas = $sheet->toArray();

            // Omitir la primera fila (cabeceras)
            unset($filas[0]);

            $importados = 0;
            foreach ($filas as $fila) {
                // Suponiendo columnas: [1] => Nombre, [2] => Precio, [3] => Stock, [4] => Descripcion
                $nombre = trim($fila[0] ?? '');
                $precio = floatval($fila[1] ?? 0);
                $stock  = intval($fila[2] ?? 0);
                $desc   = trim($fila[3] ?? '');

                if (!empty($nombre) && $precio > 0) {
                    $stmt = $conexion->prepare("INSERT INTO productos (nombre, precio, stock, descripcion) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("sdis", $nombre, $precio, $stock, $desc);
                    if ($stmt->execute()) {
                        $importados++;
                    }
                    $stmt->close();
                }
            }

            header("Location: productos.php?exito=importado&total=" . $importados);
            exit();

        } catch (Exception $e) {
            die("Error al procesar el archivo Excel: " . $e->getMessage());
        }
    } else {
        die("Formato de archivo no válido. Sube un archivo .xlsx o .csv");
    }
}