<?php
// procesar_artista.php
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
function procesarImagen($file, $artista_folder, $prefix, $old_image = null) {
    // Si no hay nuevo archivo y hay imagen existente, mantener la anterior
    if (empty($file['tmp_name']) && $old_image) {
        return $old_image;
    }

    // Si no hay nuevo archivo y no hay imagen existente, retornar vacío
    if (empty($file['tmp_name']) && !$old_image) {
        return '';
    }

    // Validaciones de archivo
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

    // Eliminar imagen anterior si existe y se está subiendo una nueva
    if ($old_image && file_exists('../' . $old_image)) {
        unlink('../' . $old_image);
    }

    // Crear nombre de archivo único
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $prefix . '_' . uniqid() . '.' . $extension;
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

    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Token CSRF inválido');
    }

    // Validar y sanitizar campos
    $required_fields = ['nombre', 'genero_musical', 'descripcion', 'presentacion'];
    $sanitized_data = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception("El campo {$field} es requerido");
        }
        $sanitized_data[$field] = htmlspecialchars(trim($_POST[$field]), ENT_QUOTES, 'UTF-8');
    }

    // Determinar modo de operación
    $modo_edicion = isset($_POST['artista_id']) && !empty($_POST['artista_id']);
    $artista_id = $modo_edicion ? filter_var($_POST['artista_id'], FILTER_VALIDATE_INT) : null;

    if ($modo_edicion && $artista_id === false) {
        throw new Exception('ID de artista inválido');
    }

    $conn->begin_transaction();

    try {
        if ($modo_edicion) {
            // Verificar que el artista existe
            $stmt = $conn->prepare("SELECT * FROM artistas WHERE id = ?");
            $stmt->bind_param("i", $artista_id);
            $stmt->execute();
            $artista_actual = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$artista_actual) {
                throw new Exception("Artista no encontrado");
            }

            // Determinar el directorio del artista
            $artista_folder = dirname('../' . $artista_actual['imagen_presentacion']);
            if (!is_dir($artista_folder)) {
                $artista_slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $sanitized_data['nombre'])) . '_' . time();
                $artista_folder = ARTISTS_UPLOAD_PATH . '/' . $artista_slug;
                createDirectory($artista_folder);
            }

            // Procesar imágenes
            $imagen_presentacion = procesarImagen(
                $_FILES['imagen_presentacion'], 
                $artista_folder, 
                'presentacion', 
                $artista_actual['imagen_presentacion']
            );
            
            $logo_artista = procesarImagen(
                $_FILES['logo_artista'], 
                $artista_folder, 
                'logo', 
                $artista_actual['logo_artista']
            );

            // Actualizar en la base de datos
            $stmt = $conn->prepare("UPDATE artistas SET 
                nombre = ?, 
                genero_musical = ?, 
                descripcion = ?, 
                presentacion = ?, 
                imagen_presentacion = ?, 
                logo_artista = ? 
                WHERE id = ?");

            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $conn->error);
            }

            $stmt->bind_param("ssssssi", 
                $sanitized_data['nombre'],
                $sanitized_data['genero_musical'],
                $sanitized_data['descripcion'],
                $sanitized_data['presentacion'],
                $imagen_presentacion,
                $logo_artista,
                $artista_id
            );

        } else {
            // Crear directorio para nuevo artista
            createDirectory(ARTISTS_UPLOAD_PATH);
            $artista_slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $sanitized_data['nombre'])) . '_' . time();
            $artista_folder = ARTISTS_UPLOAD_PATH . '/' . $artista_slug;
            createDirectory($artista_folder);

            // Procesar imágenes para nuevo artista
            $imagen_presentacion = procesarImagen($_FILES['imagen_presentacion'], $artista_folder, 'presentacion');
            $logo_artista = procesarImagen($_FILES['logo_artista'], $artista_folder, 'logo');

            // Insertar en la base de datos
            $stmt = $conn->prepare("INSERT INTO artistas 
                (nombre, genero_musical, descripcion, presentacion, imagen_presentacion, logo_artista) 
                VALUES (?, ?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $conn->error);
            }

            $stmt->bind_param("ssssss", 
                $sanitized_data['nombre'],
                $sanitized_data['genero_musical'],
                $sanitized_data['descripcion'],
                $sanitized_data['presentacion'],
                $imagen_presentacion,
                $logo_artista
            );
        }

        if (!$stmt->execute()) {
            throw new Exception("Error al " . ($modo_edicion ? "actualizar" : "guardar") . " el artista: " . $stmt->error);
        }

        $artista_id = $modo_edicion ? $artista_id : $stmt->insert_id;
        $stmt->close();
        
        $conn->commit();

        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'message' => 'Artista ' . ($modo_edicion ? 'actualizado' : 'guardado') . ' exitosamente',
            'data' => [
                'id' => $artista_id,
                'nombre' => $sanitized_data['nombre'],
                'imagen_presentacion' => $imagen_presentacion,
                'logo_artista' => $logo_artista
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    // Si hay un error en el modo de creación, limpiar el directorio creado
    if (!$modo_edicion && isset($artista_folder) && is_dir($artista_folder)) {
        array_map('unlink', glob("$artista_folder/*.*"));
        rmdir($artista_folder);
    }

    error_log("Error en procesar_artista.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}