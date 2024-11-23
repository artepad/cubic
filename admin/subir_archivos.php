<?php
session_start();
require_once 'config/config.php';
require_once 'functions/functions.php';

// Verificar autenticación y CSRF
checkAuthentication();
header('Content-Type: application/json');

try {
    // Verificar que se recibió el ID del evento
    if (!isset($_POST['evento_id']) || empty($_POST['evento_id'])) {
        throw new Exception('ID de evento no proporcionado');
    }

    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Token de seguridad inválido');
    }

    $evento_id = intval($_POST['evento_id']);

    // Verificar que el evento existe
    $sql = "SELECT id FROM eventos WHERE id = ?";
    $result = executeQuery($conn, $sql, [$evento_id]);
    if ($result->num_rows === 0) {
        throw new Exception('Evento no encontrado');
    }

    // Verificar cantidad de archivos existentes
    $sql = "SELECT COUNT(*) as total FROM evento_archivos WHERE evento_id = ?";
    $result = executeQuery($conn, $sql, [$evento_id]);
    $total_actual = $result->fetch_assoc()['total'];

    if ($total_actual >= 3) {
        throw new Exception('Ya se ha alcanzado el límite máximo de archivos para este evento');
    }

    // Crear directorio específico para el evento si no existe
    $evento_dir = EVENTOS_UPLOAD_DIR . '/' . $evento_id;
    if (!file_exists($evento_dir)) {
        if (!mkdir($evento_dir, 0755, true)) {
            throw new Exception('Error al crear el directorio para los archivos');
        }
    }

    $archivos_subidos = 0;
    $errores = [];

    // Procesar cada archivo
    foreach ($_FILES as $key => $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errores[] = "Error al subir {$file['name']}: " . getUploadErrorMessage($file['error']);
            continue;
        }

        try {
            // Validar tipo de archivo
            $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception("Tipo de archivo no permitido: {$file['name']}");
            }

            // Validar tamaño
            $max_size = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $max_size) {
                throw new Exception("El archivo {$file['name']} excede el tamaño máximo permitido (5MB)");
            }

            // Generar nombre único
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $nombre_archivo = uniqid() . '_' . time() . '.' . $extension;
            $ruta_completa = $evento_dir . '/' . $nombre_archivo;

            // Mover el archivo
            if (!move_uploaded_file($file['tmp_name'], $ruta_completa)) {
                throw new Exception("Error al guardar el archivo {$file['name']}");
            }

            // Guardar en la base de datos
            $sql = "INSERT INTO evento_archivos (evento_id, nombre_original, nombre_archivo, tipo_archivo, tamano) 
                    VALUES (?, ?, ?, ?, ?)";
            executeQuery($conn, $sql, [
                $evento_id,
                $file['name'],
                $nombre_archivo,
                $file['type'],
                $file['size']
            ]);

            $archivos_subidos++;

        } catch (Exception $e) {
            $errores[] = $e->getMessage();
        }
    }

    if ($archivos_subidos > 0) {
        $response = [
            'success' => true,
            'message' => "Se subieron $archivos_subidos archivos correctamente"
        ];
        if (!empty($errores)) {
            $response['warnings'] = $errores;
        }
    } else {
        throw new Exception('No se pudo subir ningún archivo: ' . implode(', ', $errores));
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();

function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'El archivo excede el tamaño máximo permitido por el servidor';
        case UPLOAD_ERR_FORM_SIZE:
            return 'El archivo excede el tamaño máximo permitido por el formulario';
        case UPLOAD_ERR_PARTIAL:
            return 'El archivo se subió parcialmente';
        case UPLOAD_ERR_NO_FILE:
            return 'No se subió ningún archivo';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'No se encontró una carpeta temporal';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Error al escribir el archivo en el disco';
        case UPLOAD_ERR_EXTENSION:
            return 'Una extensión de PHP detuvo la subida del archivo';
        default:
            return 'Error desconocido al subir el archivo';
    }
}