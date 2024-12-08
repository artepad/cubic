<?php
session_start();
require_once 'config/config.php';
require_once 'functions/functions.php';

// Verificar autenticación
checkAuthentication();

// Verificar si es una petición POST y si tiene el token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
        exit;
    }

    $evento_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($evento_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de evento inválido']);
        exit;
    }

    try {
        // Iniciar transacción
        $conn->begin_transaction();

        // 1. Obtener información de los archivos asociados al evento
        $sql_archivos = "SELECT * FROM evento_archivos WHERE evento_id = ?";
        $archivos = executeQuery($conn, $sql_archivos, [$evento_id]);

        // 2. Eliminar archivos físicos
        while ($archivo = $archivos->fetch_assoc()) {
            $ruta_archivo = EVENTOS_UPLOAD_DIR . '/' . $evento_id . '/' . $archivo['nombre_archivo'];
            if (file_exists($ruta_archivo)) {
                unlink($ruta_archivo);
            }
        }

        // 3. Eliminar el directorio del evento si existe
        $directorio_evento = EVENTOS_UPLOAD_DIR . '/' . $evento_id;
        if (is_dir($directorio_evento)) {
            rmdir($directorio_evento);
        }

        // 4. Eliminar registros de la base de datos
        // Los archivos se eliminarán automáticamente por la restricción ON DELETE CASCADE
        $sql_eliminar = "DELETE FROM eventos WHERE id = ?";
        executeQuery($conn, $sql_eliminar, [$evento_id]);

        // Confirmar transacción
        $conn->commit();

        echo json_encode(['success' => true]);
        exit;

    } catch (Exception $e) {
        // Revertir cambios en caso de error
        $conn->rollback();
        
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Error al eliminar el evento: ' . $e->getMessage()
        ]);
        exit;
    }
} else {
    // Si no es POST, redirigir a la página principal
    header('Location: agenda.php');
    exit;
}