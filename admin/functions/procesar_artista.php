<?php
// Control de buffer y errores
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../config/config.php';
require_once '../config/paths.php';
require_once 'functions.php';

// Configuración de constantes
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Configurar headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Función para crear directorios de forma segura
function createDirectory($path) {
    if (!file_exists($path)) {
        if (!mkdir($path, 0755, true)) {
            throw new Exception("No se pudo crear el directorio: " . $path);
        }
    }
    
    if (!is_writable($path)) {
        throw new Exception("El directorio no tiene permisos de escritura: " . $path);
    }
    
    return true;
}

// Función para procesar imagen
function procesarImagen($file, $artista_folder, $prefix) {
    if (empty($file['tmp_name'])) {
        return '';
    }

    // Validar archivo
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error al subir el archivo: " . $file['error']);
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception("El archivo excede el tamaño máximo permitido");
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, ALLOWED_TYPES)) {
        throw new Exception("Tipo de archivo no permitido");
    }

    // Crear nombre de archivo único
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $prefix . '_' . uniqid() . '.' . $extension;
    
    // Ruta completa del archivo
    $filepath = $artista_folder . '/' . $filename;
    
    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception("Error al guardar el archivo");
    }

    // Retornar ruta relativa para la base de datos
    return ARTISTS_UPLOAD_DIR . '/' . basename($artista_folder) . '/' . $filename;
}

try {
    // Verificar método y CSRF
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
        $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Token CSRF inválido');
    }

    // Validar campos requeridos
    $required_fields = ['nombre', 'genero_musical', 'descripcion', 'presentacion'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo {$field} es requerido");
        }
    }

    // Crear directorio base si no existe
    createDirectory(ARTISTS_UPLOAD_PATH);

    // Crear carpeta específica para el artista
    $artista_slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $_POST['nombre'])) . '_' . time();
    $artista_folder = ARTISTS_UPLOAD_PATH . '/' . $artista_slug;
    createDirectory($artista_folder);

    // Procesar imágenes
    $imagen_presentacion = procesarImagen($_FILES['imagen_presentacion'], $artista_folder, 'presentacion');
    $logo_artista = procesarImagen($_FILES['logo_artista'], $artista_folder, 'logo');

    // Insertar en la base de datos
    $stmt = $conn->prepare("INSERT INTO artistas (nombre, genero_musical, descripcion, presentacion, imagen_presentacion, logo_artista) VALUES (?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }

    $stmt->bind_param("ssssss", 
        $_POST['nombre'],
        $_POST['genero_musical'],
        $_POST['descripcion'],
        $_POST['presentacion'],
        $imagen_presentacion,
        $logo_artista
    );

    if (!$stmt->execute()) {
        throw new Exception("Error al guardar el artista: " . $stmt->error);
    }

    $artista_id = $stmt->insert_id;
    $stmt->close();

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Artista guardado exitosamente',
        'data' => [
            'id' => $artista_id,
            'nombre' => $_POST['nombre'],
            'imagen_presentacion' => $imagen_presentacion,
            'logo_artista' => $logo_artista
        ]
    ]);

} catch (Exception $e) {
    // Limpiar directorio en caso de error
    if (isset($artista_folder) && is_dir($artista_folder)) {
        array_map('unlink', glob("$artista_folder/*.*"));
        rmdir($artista_folder);
    }

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}