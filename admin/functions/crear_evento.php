<?php
// Inicialización y configuración
session_start();
require_once '../config/config.php';
require_once '../functions/functions.php';

// Configuración de errores y logging
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Función para logging
function logDebug($message, $data = null)
{
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log .= "\nData: " . print_r($data, true);
    }
    error_log($log);
}

// Función para respuestas JSON
function sendResponse($success, $message, $data = null)
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Log inicial
logDebug("=== Inicio de creación de evento ===");
logDebug("POST Data", $_POST);

try {
    // Verificar conexión
    if (!isset($conn) || $conn->connect_error) {
        logDebug("Error de conexión", $conn->connect_error ?? 'Conexión no establecida');
        throw new Exception("Error de conexión a la base de datos");
    }

    // Validar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Validar y recoger datos
    // Validar y recoger datos
    $evento = [
        'cliente_id' => filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT),
        'gira_id' => filter_input(INPUT_POST, 'gira_id', FILTER_VALIDATE_INT),
        'artista_id' => filter_input(INPUT_POST, 'artista_id', FILTER_VALIDATE_INT),
        'nombre_evento' => trim(htmlspecialchars($_POST['nombre_evento'] ?? '')),
        'fecha_evento' => trim($_POST['fecha_evento'] ?? ''),
        'hora_evento' => trim($_POST['hora_evento'] ?? ''),
        'ciudad_evento' => trim(htmlspecialchars($_POST['ciudad_evento'] ?? '')),
        'lugar_evento' => trim(htmlspecialchars($_POST['lugar_evento'] ?? '')),
        'valor_evento' => filter_input(INPUT_POST, 'valor_evento', FILTER_VALIDATE_INT),
        'tipo_evento' => trim(htmlspecialchars($_POST['tipo_evento'] ?? '')),
        'encabezado_evento' => trim(htmlspecialchars($_POST['encabezado_evento'] ?? '')),
        'estado_evento' => 'Propuesta',  // Valor inicial fijo como string
        'hotel' => $_POST['hotel'] ?? 'No',
        'traslados' => $_POST['traslados'] ?? 'No',
        'viaticos' => $_POST['viaticos'] ?? 'No'
    ];

    // Validaciones básicas
    $requiredFields = [
        'cliente_id',
        'artista_id',
        'nombre_evento',
        'fecha_evento',
        'hora_evento',
        'ciudad_evento',
        'lugar_evento',
        'valor_evento',
        'tipo_evento'
    ];

    foreach ($requiredFields as $field) {
        if (empty($evento[$field])) {
            throw new Exception("El campo $field es requerido");
        }
    }

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

    // Validar hora
    if (!preg_match("/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/", $evento['hora_evento'])) {
        throw new Exception("Formato de hora inválido");
    }

    // Validar los campos enum
    $enum_validations = [
        'hotel' => ['Si', 'No'],
        'traslados' => ['Si', 'No'],
        'viaticos' => ['Si', 'No']
    ];

    foreach ($enum_validations as $field => $valid_values) {
        if (!in_array($evento[$field], $valid_values)) {
            $evento[$field] = 'No'; // Valor por defecto si no es válido
        }
    }
    // Agregar validación para estado_evento
    $estados_validos = ['Propuesta', 'Confirmado', 'Documentación', 'En Producción', 'Finalizado', 'Reagendado', 'Cancelado'];
    if (!in_array($evento['estado_evento'], $estados_validos)) {
        throw new Exception("Estado de evento no válido");
    }

    // Validaciones básicas
    $requiredFields = [
        'cliente_id',
        'artista_id',
        'nombre_evento',
        'fecha_evento',
        'hora_evento',
        'ciudad_evento',
        'lugar_evento',
        'valor_evento',
        'tipo_evento'
    ];

    foreach ($requiredFields as $field) {
        if (empty($evento[$field])) {
            throw new Exception("El campo $field es requerido");
        }
    }

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

    // Validar hora
    if (!preg_match("/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/", $evento['hora_evento'])) {
        throw new Exception("Formato de hora inválido");
    }

    // Log pre-transacción
    logDebug("Iniciando transacción con datos", $evento);

    // Iniciar transacción
    $conn->begin_transaction();

    // Verificar si el evento ya existe
    $check_sql = "SELECT id FROM eventos WHERE nombre_evento = ? AND fecha_evento = ? AND cliente_id = ?";
    $check_stmt = $conn->prepare($check_sql);

    if (!$check_stmt) {
        throw new Exception("Error preparando la consulta de verificación: " . $conn->error);
    }

    $check_stmt->bind_param(
        "ssi",
        $evento['nombre_evento'],
        $evento['fecha_evento'],
        $evento['cliente_id']
    );

    if (!$check_stmt->execute()) {
        throw new Exception("Error ejecutando la verificación: " . $check_stmt->error);
    }

    $result = $check_stmt->get_result();
    if ($result->num_rows > 0) {
        throw new Exception("Ya existe un evento con este nombre para este cliente en la fecha especificada");
    }

    $check_stmt->close();

    // Preparar la consulta de inserción
    $sql = "INSERT INTO eventos (
        cliente_id, gira_id, artista_id, nombre_evento, fecha_evento,
        hora_evento, ciudad_evento, lugar_evento, valor_evento, tipo_evento,
        encabezado_evento, estado_evento, hotel, traslados, viaticos
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    logDebug("Preparando inserción con SQL", $sql);

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparando la consulta de inserción: " . $conn->error);
    }

    // Vincular parámetros
    if (!$stmt->bind_param("iiisssssississs",
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
    $evento['estado_evento'],
    $evento['hotel'],
    $evento['traslados'],
    $evento['viaticos']
)) {
    throw new Exception("Error vinculando parámetros: " . $stmt->error);
}

    // Ejecutar la inserción
    if (!$stmt->execute()) {
        throw new Exception("Error ejecutando la inserción: " . $stmt->error);
    }

    // Obtener el ID del evento insertado
    $evento_id = $stmt->insert_id;

    // Log de éxito
    logDebug("Evento creado exitosamente", [
        'evento_id' => $evento_id,
        'nombre_evento' => $evento['nombre_evento']
    ]);

    // Confirmar la transacción
    $conn->commit();

    // Enviar respuesta exitosa
    sendResponse(true, "Evento creado exitosamente", [
        'evento_id' => $evento_id,
        'nombre_evento' => $evento['nombre_evento']
    ]);
} catch (Exception $e) {
    // Log del error
    logDebug("Error en la creación del evento", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    // Revertir la transacción en caso de error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
        logDebug("Transacción revertida");
    }

    // Enviar respuesta de error
    sendResponse(false, "Error al crear el evento: " . $e->getMessage());
} finally {
    // Cerrar las conexiones
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($check_stmt)) {
        $check_stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
