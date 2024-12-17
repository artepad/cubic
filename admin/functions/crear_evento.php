<?php
session_start();
require_once '../config/config.php';
require_once '../functions/functions.php';

header('Content-Type: application/json');

// Función para respuestas JSON
function sendResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

try {
    // Validar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Recoger y validar datos
    $evento = [
        'cliente_id' => filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT),
        'gira_id' => filter_input(INPUT_POST, 'gira_id', FILTER_VALIDATE_INT) ?: null,
        'artista_id' => filter_input(INPUT_POST, 'artista_id', FILTER_VALIDATE_INT),
        'nombre_evento' => trim($_POST['nombre_evento'] ?? ''),
        'fecha_evento' => trim($_POST['fecha_evento'] ?? ''),
        'hora_evento' => !empty($_POST['hora_evento']) ? trim($_POST['hora_evento']) : null,
        'ciudad_evento' => !empty($_POST['ciudad_evento']) ? trim($_POST['ciudad_evento']) : null,
        'lugar_evento' => !empty($_POST['lugar_evento']) ? trim($_POST['lugar_evento']) : null,
        'valor_evento' => filter_input(INPUT_POST, 'valor_evento', FILTER_VALIDATE_INT),
        'tipo_evento' => trim($_POST['tipo_evento'] ?? ''),
        'encabezado_evento' => trim($_POST['encabezado_evento'] ?? ''),
        'hotel' => $_POST['hotel'] ?? 'No',
        'traslados' => $_POST['traslados'] ?? 'No',
        'viaticos' => $_POST['viaticos'] ?? 'No'
    ];

    // Validaciones básicas (solo para campos obligatorios)
    if (empty($evento['cliente_id'])) throw new Exception("El cliente es requerido");
    if (empty($evento['artista_id'])) throw new Exception("El artista es requerido");
    if (empty($evento['nombre_evento'])) throw new Exception("El nombre del evento es requerido");
    if (empty($evento['fecha_evento'])) throw new Exception("La fecha es requerida");
    if (empty($evento['valor_evento'])) throw new Exception("El valor es requerido");
    if (empty($evento['tipo_evento'])) throw new Exception("El tipo de evento es requerido");

    // Validar valor del evento
    if ($evento['valor_evento'] < 1000000 || $evento['valor_evento'] > 100000000) {
        throw new Exception('El valor del evento debe estar entre $1.000.000 y $100.000.000');
    }

    // Validar fecha
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $evento['fecha_evento'])) {
        throw new Exception("Formato de fecha inválido");
    }

    // Validar que la fecha no sea anterior a hoy
    if (strtotime($evento['fecha_evento']) < strtotime(date('Y-m-d'))) {
        throw new Exception("La fecha del evento no puede ser anterior a hoy");
    }

    // Validar que el tipo de evento sea uno de los permitidos
    $tipos_permitidos = ['Privado', 'Municipal', 'Matrimonio'];
    if (!in_array($evento['tipo_evento'], $tipos_permitidos)) {
        throw new Exception("Tipo de evento no válido");
    }

    // Iniciar transacción
    $conn->begin_transaction();

    // Verificar si el evento ya existe
    $check_sql = "SELECT id FROM eventos WHERE nombre_evento = ? AND fecha_evento = ? AND cliente_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ssi", 
        $evento['nombre_evento'],
        $evento['fecha_evento'],
        $evento['cliente_id']
    );
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        throw new Exception("Ya existe un evento con este nombre para este cliente en la fecha especificada");
    }
    $check_stmt->close();

    // Preparar la consulta de inserción
    $sql = "INSERT INTO eventos (
        cliente_id, gira_id, artista_id, nombre_evento, fecha_evento,
        hora_evento, ciudad_evento, lugar_evento, valor_evento, tipo_evento,
        encabezado_evento, hotel, traslados, viaticos
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparando la consulta: " . $conn->error);
    }

    // Vincular parámetros
    $stmt->bind_param("iiisssssisssss",
        $evento['cliente_id'],
        $evento['gira_id'],
        $evento['artista_id'],
        $evento['nombre_evento'],
        $evento['fecha_evento'],
        $evento['hora_evento'],
        $evento['ciudad_evento'],
        $evento['lugar_evento'],
        $evento['valor_evento'],
        $evento['tipo_evento'],
        $evento['encabezado_evento'],
        $evento['hotel'],
        $evento['traslados'],
        $evento['viaticos']
    );

    if (!$stmt->execute()) {
        throw new Exception("Error ejecutando la consulta: " . $stmt->error);
    }

    $evento_id = $stmt->insert_id;
    $conn->commit();

    sendResponse(true, "Evento creado exitosamente", [
        'evento_id' => $evento_id,
        'nombre_evento' => $evento['nombre_evento']
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    sendResponse(false, "Error al crear el evento: " . $e->getMessage());
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}