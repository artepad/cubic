<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

function sendJsonResponse($success, $message, $data = null) {
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

try {
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Método no permitido");
    }

    // Validar y sanitizar inputs
    $cliente_id = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
    $nombre_evento = filter_input(INPUT_POST, 'nombre_evento', FILTER_SANITIZE_STRING);
    $fecha_evento = filter_input(INPUT_POST, 'fecha_evento', FILTER_SANITIZE_STRING);
    $hora_evento = filter_input(INPUT_POST, 'hora_evento', FILTER_SANITIZE_STRING);
    $ciudad_evento = filter_input(INPUT_POST, 'ciudad_evento', FILTER_SANITIZE_STRING);
    $lugar_evento = filter_input(INPUT_POST, 'lugar', FILTER_SANITIZE_STRING);
    $valor_evento = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_INT);
    $tipo_evento = filter_input(INPUT_POST, 'tipo_evento', FILTER_SANITIZE_STRING);
    $encabezado_evento = filter_input(INPUT_POST, 'encabezado_evento', FILTER_SANITIZE_STRING);
    $estado_evento = filter_input(INPUT_POST, 'estado_evento', FILTER_SANITIZE_STRING);
    $hotel = filter_input(INPUT_POST, 'hotel', FILTER_SANITIZE_STRING);
    $traslados = filter_input(INPUT_POST, 'traslados', FILTER_SANITIZE_STRING);
    $viaticos = filter_input(INPUT_POST, 'viaticos', FILTER_SANITIZE_STRING);
    $gira_id = filter_input(INPUT_POST, 'gira_id', FILTER_VALIDATE_INT);

    if (!$cliente_id || !$nombre_evento || !$fecha_evento || !$hora_evento || !$valor_evento) {
        throw new Exception("Faltan campos obligatorios");
    }

    $sql = "INSERT INTO eventos (cliente_id, gira_id, nombre_evento, fecha_evento, hora_evento, ciudad_evento, lugar_evento, valor_evento, tipo_evento, encabezado_evento, estado_evento, hotel, traslados, viaticos) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("iisssssissssss", $cliente_id, $gira_id, $nombre_evento, $fecha_evento, $hora_evento, $ciudad_evento, $lugar_evento, $valor_evento, $tipo_evento, $encabezado_evento, $estado_evento, $hotel, $traslados, $viaticos);

    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }

    sendJsonResponse(true, "Evento creado con éxito", ["evento_id" => $conn->insert_id]);

} catch (Exception $e) {
    sendJsonResponse(false, $e->getMessage());
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>