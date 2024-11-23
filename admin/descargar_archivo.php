<?php
session_start();
require_once 'config/config.php';
require_once 'functions/functions.php';

// Verificar autenticación
checkAuthentication();

try {
    // Validar ID del archivo
    $archivo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($archivo_id <= 0) {
        throw new Exception('ID de archivo inválido');
    }

    // Obtener información del archivo
    $sql = "SELECT * FROM evento_archivos WHERE id = ?";
    $result = executeQuery($conn, $sql, [$archivo_id]);
    $archivo = $result->fetch_assoc();

    if (!$archivo) {
        throw new Exception('Archivo no encontrado');
    }

    // Construir ruta del archivo
    $ruta_archivo = EVENTOS_UPLOAD_DIR . '/' . $archivo['evento_id'] . '/' . $archivo['nombre_archivo'];

    // Verificar que el archivo existe
    if (!file_exists($ruta_archivo)) {
        throw new Exception('Archivo físico no encontrado');
    }

    // Configurar headers para la descarga
    header('Content-Type: ' . $archivo['tipo_archivo']);
    header('Content-Disposition: attachment; filename="' . $archivo['nombre_original'] . '"');
    header('Content-Length: ' . filesize($ruta_archivo));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    // Enviar archivo
    readfile($ruta_archivo);
    exit;

} catch (Exception $e) {
    die($e->getMessage());
}

$conn->close();