<?php
// Control de buffer y errores
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once '../config/config.php';
require_once 'functions.php';

// Asegurar que la respuesta sea JSON
header('Content-Type: application/json');

// Función para registrar errores
function logError($error) {
    $logFile = "../logs/error.log";
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    error_log(date('[Y-m-d H:i:s] ') . "Error: " . $error . "\n", 3, $logFile);
}

// Función helper para enviar respuestas JSON
function sendJsonResponse($success, $data = null, $error = null) {
    ob_clean(); // Limpiar cualquier salida anterior
    header('Content-Type: application/json'); // Asegurar header JSON
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'message' => $success ? 'Artista guardado exitosamente' : 'Error al guardar el artista'
    ]);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Método no permitido');
}

// Verificar CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    sendJsonResponse(false, null, 'Token CSRF inválido');
}

// Configuración de límites y tipos de archivo permitidos
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
const UPLOAD_BASE_DIR = '../assets/img/artistas/';

// Función para validar un archivo subido
function validarArchivo($file) {
    if (empty($file['tmp_name'])) {
        return true; // Archivo opcional
    }

    // Verificar errores de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errores = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo fue subido parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el servidor',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo'
        ];
        throw new Exception($errores[$file['error']] ?? 'Error desconocido al subir el archivo');
    }

    // Validar tamaño
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('El archivo excede el tamaño máximo permitido de 10MB');
    }

    // Validar tipo MIME real del archivo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, ALLOWED_TYPES)) {
        throw new Exception('Tipo de archivo no permitido. Solo se aceptan JPG, PNG y GIF');
    }

    return true;
}

// Función para procesar y guardar una imagen
function procesarImagen($file, $ruta_carpeta, $prefijo, $nombre_carpeta) {
    if (empty($file['tmp_name'])) {
        return '';
    }

    // Generar nombre único para el archivo
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $nombre_archivo = $prefijo . '_' . uniqid() . '.' . $extension;
    $ruta_archivo = $ruta_carpeta . '/' . $nombre_archivo;

    // Intentar mover el archivo
    if (!move_uploaded_file($file['tmp_name'], $ruta_archivo)) {
        throw new Exception("Error al guardar el archivo {$prefijo}");
    }

    // Devolver ruta relativa para la base de datos
    return 'artistas/' . $nombre_carpeta . '/' . $nombre_archivo;
}

try {
    // Verificar conexión a la base de datos
    if (!$conn || $conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos: " . ($conn ? $conn->connect_error : "No hay conexión"));
    }

    // Validar campos requeridos
    $campos_requeridos = ['nombre', 'genero_musical', 'descripcion', 'presentacion'];
    $errores = [];
    
    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo]) || trim($_POST[$campo]) === '') {
            $errores[] = "El campo {$campo} es requerido";
        }
    }
    
    if (!empty($errores)) {
        throw new Exception(implode(", ", $errores));
    }

    // Sanitizar datos
    $nombre = filter_var(trim($_POST['nombre']), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $genero_musical = filter_var(trim($_POST['genero_musical']), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $descripcion = filter_var(trim($_POST['descripcion']), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $presentacion = filter_var(trim($_POST['presentacion']), FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Validar archivos
    validarArchivo($_FILES['imagen_presentacion']);
    validarArchivo($_FILES['logo_artista']);

    // Crear carpeta para el artista
    $nombre_carpeta = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $nombre)) . '_' . time();
    $ruta_completa = UPLOAD_BASE_DIR . $nombre_carpeta;
    
    if (!file_exists(UPLOAD_BASE_DIR)) {
        if (!mkdir(UPLOAD_BASE_DIR, 0755, true)) {
            throw new Exception("Error al crear el directorio base de artistas");
        }
    }
    
    if (!mkdir($ruta_completa, 0755, true)) {
        throw new Exception("Error al crear el directorio del artista");
    }

    // Procesar imágenes
    $imagen_presentacion = procesarImagen(
        $_FILES['imagen_presentacion'],
        $ruta_completa,
        'presentacion',
        $nombre_carpeta
    );

    $logo_artista = procesarImagen(
        $_FILES['logo_artista'],
        $ruta_completa,
        'logo',
        $nombre_carpeta
    );

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Preparar la consulta SQL
        $sql = "INSERT INTO artistas (
                    nombre, 
                    genero_musical, 
                    descripcion, 
                    presentacion, 
                    imagen_presentacion, 
                    logo_artista
                ) VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }

        $stmt->bind_param(
            "ssssss",
            $nombre,
            $genero_musical,
            $descripcion,
            $presentacion,
            $imagen_presentacion,
            $logo_artista
        );

        // Ejecutar la consulta
        if (!$stmt->execute()) {
            throw new Exception("Error al guardar el artista: " . $stmt->error);
        }

        $artista_id = $stmt->insert_id;
        $stmt->close();

        // Confirmar la transacción
        $conn->commit();

        // Devolver respuesta exitosa
        sendJsonResponse(true, [
            'artista_id' => $artista_id,
            'nombre' => $nombre,
            'imagen_presentacion' => $imagen_presentacion,
            'logo_artista' => $logo_artista
        ]);

    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    // Log del error
    logError($e->getMessage());
    
    // Limpiar archivos en caso de error
    if (isset($ruta_completa) && is_dir($ruta_completa)) {
        array_map('unlink', glob("$ruta_completa/*.*"));
        rmdir($ruta_completa);
    }
    
    sendJsonResponse(false, null, $e->getMessage());
} finally {
    // Cerrar la conexión
    if (isset($conn) && $conn) {
        $conn->close();
    }
}