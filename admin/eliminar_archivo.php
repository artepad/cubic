<?php
session_start();
require_once 'config/config.php';
require_once 'functions/functions.php';

// Verificar autenticación y CSRF
checkAuthentication();
header('Content-Type: application/json');

try {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Token de seguridad inválido');
    }

    // Validar ID del archivo
    $archivo_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($archivo_id <= 0) {
        throw new Exception('ID de archivo inválido');
    }

    // Eliminar el archivo
    eliminarArchivoEvento($conn, $archivo_id);

    echo json_encode([
        'success' => true,
        'message' => 'Archivo eliminado correctamente'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();