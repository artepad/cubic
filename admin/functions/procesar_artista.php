<?php
// Control de buffer y errores
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../config/config.php';
require_once 'functions.php';

// Definir constantes
define('UPLOAD_BASE_DIR', dirname(__DIR__) . '/assets/img/artistas/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Configurar headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Función para debug
function debug($message, $data = null) {
    $logFile = dirname(__DIR__) . '/logs/debug.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $output = date('[Y-m-d H:i:s] ') . $message . 
        ($data ? ': ' . print_r($data, true) : '') . "\n";
    error_log($output, 3, $logFile);
}

// Función para enviar respuestas JSON
function sendJsonResponse($success, $data = null, $error = null) {
    // Limpiar cualquier salida previa
    if (ob_get_length()) ob_clean();
    while (ob_get_level()) ob_end_clean();
    
    $response = [
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'message' => $success ? 'Artista guardado exitosamente' : 'Error al guardar el artista'
    ];
    
    debug('Response Data', $response);
    echo json_encode($response);
    exit;
}

// Función para asegurar la existencia y permisos de un directorio
function asegurarDirectorio($ruta) {
    if (!file_exists($ruta)) {
        if (!mkdir($ruta, 0755, true)) {
            throw new Exception("No se pudo crear el directorio: " . $ruta);
        }
    } elseif (!is_writable($ruta)) {
        throw new Exception("El directorio no tiene permisos de escritura: " . $ruta);
    }
    return true;
}

// Función para validar archivo
function validarArchivo($file) {
    if (empty($file['tmp_name'])) {
        return true; // Archivo opcional
    }

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

    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('El archivo excede el tamaño máximo permitido de 10MB');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, ALLOWED_TYPES)) {
        throw new Exception('Tipo de archivo no permitido. Solo se aceptan JPG, PNG y GIF');
    }

    return true;
}

// Función para procesar imagen
function procesarImagen($file, $ruta_carpeta, $prefijo, $nombre_carpeta) {
    if (empty($file['tmp_name'])) {
        return '';
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $nombre_archivo = $prefijo . '_' . uniqid() . '.' . $extension;
    $ruta_archivo = $ruta_carpeta . DIRECTORY_SEPARATOR . $nombre_archivo;

    debug("Intentando guardar archivo en", $ruta_archivo);

    if (!move_uploaded_file($file['tmp_name'], $ruta_archivo)) {
        throw new Exception("Error al guardar el archivo {$prefijo}. Ruta: {$ruta_archivo}");
    }

    return 'assets/img/artistas/' . $nombre_carpeta . '/' . $nombre_archivo;
}

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Método no permitido');
}

// Verificar CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    sendJsonResponse(false, null, 'Token CSRF inválido');
}

try {
    // Verificar conexión a la base de datos
    if (!$conn || $conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos: " . ($conn ? $conn->connect_error : "No hay conexión"));
    }

    // Debug de datos recibidos
    debug('POST Data', $_POST);
    debug('FILES Data', $_FILES);

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

    // Verificar y crear directorios
    debug("Estructura de directorios", [
        'UPLOAD_BASE_DIR' => UPLOAD_BASE_DIR,
        'exists' => file_exists(UPLOAD_BASE_DIR),
        'is_writable' => is_writable(UPLOAD_BASE_DIR),
        'permisos' => decoct(fileperms(UPLOAD_BASE_DIR))
    ]);

    // Asegurar directorio base
    asegurarDirectorio(UPLOAD_BASE_DIR);

    // Crear carpeta para el artista
    $nombre_carpeta = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $nombre)) . '_' . time();
    $ruta_completa = UPLOAD_BASE_DIR . $nombre_carpeta;
    
    debug("Intentando crear directorio en", $ruta_completa);
    asegurarDirectorio($ruta_completa);

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

        if (!$stmt->execute()) {
            throw new Exception("Error al guardar el artista: " . $stmt->error);
        }

        $artista_id = $stmt->insert_id;
        $stmt->close();

        $conn->commit();

        sendJsonResponse(true, [
            'artista_id' => $artista_id,
            'nombre' => $nombre,
            'imagen_presentacion' => $imagen_presentacion,
            'logo_artista' => $logo_artista
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    debug("Error", $e->getMessage());
    
    if (isset($ruta_completa) && is_dir($ruta_completa)) {
        array_map('unlink', glob("$ruta_completa/*.*"));
        rmdir($ruta_completa);
    }
    
    sendJsonResponse(false, null, $e->getMessage());
    
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}