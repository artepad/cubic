<?php
session_start();
require_once 'config/config.php';
require_once 'functions/functions.php';

header('Content-Type: application/json');

function sendJsonResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

try {
    // Verificar autenticación
    if (!isset($_SESSION['loggedin'])) {
        throw new Exception('No autorizado');
    }

    // Validar método y datos
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $evento_id = filter_input(INPUT_POST, 'evento_id', FILTER_VALIDATE_INT);
    $nuevo_estado = filter_input(INPUT_POST, 'nuevo_estado', FILTER_SANITIZE_STRING);

    if (!$evento_id || !$nuevo_estado) {
        throw new Exception('Datos inválidos');
    }

    // Validar estado válido
    $estados_validos = ['Propuesta', 'Confirmado', 'Finalizado', 'Reagendado', 'Cancelado'];
    if (!in_array($nuevo_estado, $estados_validos)) {
        throw new Exception('Estado inválido');
    }

    // Actualizar estado
    $sql = "UPDATE eventos SET estado_evento = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $nuevo_estado, $evento_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar el estado');
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception('No se encontró el evento');
    }

    sendJsonResponse(true, 'Estado actualizado correctamente', [
        'nuevo_estado' => generarEstadoEvento($nuevo_estado)
    ]);

} catch (Exception $e) {
    sendJsonResponse(false, $e->getMessage());
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}