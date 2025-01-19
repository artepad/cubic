<?php
// functions/procesar_artista.php
session_start();
header('Content-Type: application/json');
require_once '../config/config.php';
require_once 'functions.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Método no permitido']));
}

// En procesar_artista.php
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    die(json_encode(['error' => 'Token CSRF inválido']));
}

try {
    // Validar campos requeridos
    $campos_requeridos = ['nombre', 'genero_musical', 'descripcion', 'presentacion'];
    $errores = [];
    
    foreach ($campos_requeridos as $campo) {
        if (empty($_POST[$campo])) {
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

    // Crear carpeta para el artista
    $carpeta_base = '../assets/img/artistas/';
    $nombre_carpeta = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $nombre)) . '_' . time();
    $ruta_completa = $carpeta_base . $nombre_carpeta;
    
    if (!file_exists($carpeta_base)) {
        if (!mkdir($carpeta_base, 0755, true)) {
            throw new Exception("Error al crear el directorio base de artistas");
        }
    }
    
    if (!mkdir($ruta_completa, 0755, true)) {
        throw new Exception("Error al crear el directorio del artista");
    }

    // Procesar imágenes
    $imagen_presentacion = '';
    $logo_artista = '';

    // Función para procesar cada imagen
    function procesarImagen($archivo, $ruta_carpeta, $prefijo, $nombre_carpeta) {
        if (empty($archivo['tmp_name'])) {
            return '';
        }
    
        // Validar tipo de archivo
        $tipo = mime_content_type($archivo['tmp_name']);
        if (!in_array($tipo, ['image/jpeg', 'image/png', 'image/gif'])) {
            throw new Exception("Tipo de archivo no permitido para {$prefijo}");
        }
    
        // Validar tamaño (10MB)
        if ($archivo['size'] > 10 * 1024 * 1024) {
            throw new Exception("El archivo {$prefijo} excede el tamaño máximo permitido (10MB)");
        }
    
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombre_archivo = $prefijo . '_' . uniqid() . '.' . $extension;
        $ruta_archivo = $ruta_carpeta . '/' . $nombre_archivo;
    
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_archivo)) {
            throw new Exception("Error al guardar el archivo {$prefijo}");
        }
    
        return 'artistas/' . $nombre_carpeta . '/' . $nombre_archivo;
    }

    // Procesar cada imagen si fue proporcionada
    if (isset($_FILES['imagen_presentacion']) && !empty($_FILES['imagen_presentacion']['tmp_name'])) {
        $imagen_presentacion = procesarImagen(
            $_FILES['imagen_presentacion'],
            $ruta_completa,
            'presentacion',
            $nombre_carpeta
        );
    }

    if (isset($_FILES['logo_artista']) && !empty($_FILES['logo_artista']['tmp_name'])) {
        $logo_artista = procesarImagen(
            $_FILES['logo_artista'],
            $ruta_completa,
            'logo',
            $nombre_carpeta
        );
    }

    // Preparar la consulta SQL
    $sql = "INSERT INTO artistas (
                nombre, 
                genero_musical, 
                descripcion, 
                presentacion, 
                imagen_presentacion, 
                logo_artista
            ) VALUES (?, ?, ?, ?, ?, ?)";

    // Ejecutar la consulta
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
        // Si falla la inserción, eliminar la carpeta creada
        if (is_dir($ruta_completa)) {
            array_map('unlink', glob("$ruta_completa/*.*"));
            rmdir($ruta_completa);
        }
        throw new Exception("Error al guardar el artista: " . $stmt->error);
    }

    $artista_id = $stmt->insert_id;
    $stmt->close();
    $conn->close();

    // Devolver respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Artista guardado exitosamente',
        'artista_id' => $artista_id
    ]);

} catch (Exception $e) {
    // En caso de error, intentar limpiar archivos creados
    if (isset($ruta_completa) && is_dir($ruta_completa)) {
        array_map('unlink', glob("$ruta_completa/*.*"));
        rmdir($ruta_completa);
    }
    
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}