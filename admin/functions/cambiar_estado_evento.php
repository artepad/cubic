<?php
// cambiar_estado_evento.php
require_once '../config/config.php';
require_once '../utilities.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $eventoId = $_POST['eventoId'];
    $nuevoEstado = $_POST['nuevoEstado'];

    // Validar que el nuevo estado sea uno de los permitidos
    $estadosPermitidos = ['Propuesta', 'Confirmado', 'Documentación', 'En Producción', 'Finalizado', 'Reagendado', 'Cancelado'];
    if (!in_array($nuevoEstado, $estadosPermitidos)) {
        echo json_encode(['success' => false, 'message' => 'Estado no válido']);
        exit;
    }

    $sql = "UPDATE eventos SET estado_evento = ? WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nuevoEstado, $eventoId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>