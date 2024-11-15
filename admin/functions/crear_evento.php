<?php

// Configuración de errores y logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar archivo de log personalizado
$logFile = __DIR__ . '/debug.log';
ini_set('error_log', $logFile);

function debug_log($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log .= "\nData: " . print_r($data, true);
    }
    $log .= "\n" . str_repeat('-', 50) . "\n";
    error_log($log);
}

// Iniciar logging de la solicitud
debug_log("=== Nueva solicitud de creación de evento ===");
debug_log("POST Data", $_POST);
debug_log("Server Info", $_SERVER);



ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/config.php';
require_once '../functions/functions.php';

header('Content-Type: application/json');

// Función para enviar respuestas JSON
function sendJsonResponse($success, $message, $data = null)
{
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

// Validar campos requeridos
function validateRequiredFields($fields)
{
    foreach ($fields as $field => $value) {
        if (empty($value)) {
            throw new Exception("El campo {$field} es requerido");
        }
    }
}

// Función para validar el formato de fecha
function validateDate($date)
{
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
        throw new Exception("Formato de fecha inválido");
    }
    $dateTime = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateTime || $dateTime->format('Y-m-d') !== $date) {
        throw new Exception("Fecha inválida");
    }
    if ($dateTime < new DateTime('today')) {
        throw new Exception("La fecha del evento no puede ser anterior a hoy");
    }
}

// Función para validar el formato de hora
function validateTime($time)
{
    if (!preg_match("/^\d{2}:\d{2}(:\d{2})?$/", $time)) {
        throw new Exception("Formato de hora inválido");
    }
}

// Función para validar el valor del evento
function validateEventValue($value)
{
    if (!is_numeric($value) || $value < 1000000 || $value > 100000000) {
        throw new Exception("El valor del evento debe estar entre $1.000.000 y $100.000.000");
    }
}

// Función para obtener o crear gira predeterminada
function obtenerGiraPredeterminada($conn)
{
    $nombreGiraPredeterminada = "Sin Gira";

    try {
        $sql = "SELECT id FROM giras WHERE nombre = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nombreGiraPredeterminada);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id'];
        }

        // Si no existe, crear la gira predeterminada
        $sql = "INSERT INTO giras (nombre) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nombreGiraPredeterminada);
        $stmt->execute();
        return $conn->insert_id;
    } catch (Exception $e) {
        throw new Exception("Error al gestionar la gira predeterminada: " . $e->getMessage());
    }
}

try {
    // Verificar el método de la solicitud
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Método no permitido");
    }

    // Validar y sanitizar inputs
    $required_fields = [
        'cliente_id' => filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT),
        'artista_id' => filter_input(INPUT_POST, 'artista_id', FILTER_VALIDATE_INT),
        'nombre_evento' => filter_input(INPUT_POST, 'nombre_evento', FILTER_SANITIZE_STRING),
        'fecha_evento' => filter_input(INPUT_POST, 'fecha_evento', FILTER_SANITIZE_STRING),
        'hora_evento' => filter_input(INPUT_POST, 'hora_evento', FILTER_SANITIZE_STRING),
        'ciudad_evento' => filter_input(INPUT_POST, 'ciudad_evento', FILTER_SANITIZE_STRING),
        'lugar_evento' => filter_input(INPUT_POST, 'lugar_evento', FILTER_SANITIZE_STRING),
        'valor_evento' => filter_input(INPUT_POST, 'valor_evento', FILTER_VALIDATE_INT),
        'tipo_evento' => filter_input(INPUT_POST, 'tipo_evento', FILTER_SANITIZE_STRING)
    ];

    // Validar campos requeridos
    validateRequiredFields($required_fields);

    // Validar formato de fecha y hora
    validateDate($required_fields['fecha_evento']);
    validateTime($required_fields['hora_evento']);
    validateEventValue($required_fields['valor_evento']);

    // Campos opcionales
    $encabezado_evento = filter_input(INPUT_POST, 'encabezado_evento', FILTER_SANITIZE_STRING);
    $estado_evento = filter_input(INPUT_POST, 'estado_evento', FILTER_SANITIZE_STRING) ?: 'Propuesta';
    $hotel = filter_input(INPUT_POST, 'hotel', FILTER_SANITIZE_STRING) ?: 'No';
    $traslados = filter_input(INPUT_POST, 'traslados', FILTER_SANITIZE_STRING) ?: 'No';
    $viaticos = filter_input(INPUT_POST, 'viaticos', FILTER_SANITIZE_STRING) ?: 'No';
    $gira_id = filter_input(INPUT_POST, 'gira_id', FILTER_VALIDATE_INT);

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Si no se seleccionó una gira, usar la gira predeterminada
        if (!$gira_id) {
            $gira_id = obtenerGiraPredeterminada($conn);
        }

        // Verificar si el evento ya existe
        $check_sql = "SELECT id FROM eventos WHERE nombre_evento = ? AND fecha_evento = ? AND cliente_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param(
            "ssi",
            $required_fields['nombre_evento'],
            $required_fields['fecha_evento'],
            $required_fields['cliente_id']
        );
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            throw new Exception("Este evento ya existe para este cliente en la fecha especificada.");
        }

        // Preparar la consulta de inserción
        $sql = "INSERT INTO eventos (
                    cliente_id, artista_id, gira_id, nombre_evento, 
                    fecha_evento, hora_evento, ciudad_evento, lugar_evento, 
                    valor_evento, tipo_evento, encabezado_evento, estado_evento, 
                    hotel, traslados, viaticos
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta: " . $conn->error);
        }

        // Vincular parámetros
        $stmt->bind_param(
            "iiisssssisssss",
            $required_fields['cliente_id'],
            $required_fields['artista_id'],
            $gira_id,
            $required_fields['nombre_evento'],
            $required_fields['fecha_evento'],
            $required_fields['hora_evento'],
            $required_fields['ciudad_evento'],
            $required_fields['lugar_evento'],
            $required_fields['valor_evento'],
            $required_fields['tipo_evento'],
            $encabezado_evento,
            $estado_evento,
            $hotel,
            $traslados,
            $viaticos
        );

        // Ejecutar la consulta
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }

        // Confirmar la transacción
        $conn->commit();

        // Registrar el éxito
        error_log("Evento creado exitosamente. ID: " . $conn->insert_id);

        sendJsonResponse(true, "Evento creado con éxito", [
            "evento_id" => $conn->insert_id,
            "nombre_evento" => $required_fields['nombre_evento']
        ]);
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    error_log("Error en crear_evento.php: " . $e->getMessage());
    sendJsonResponse(false, "Error al crear el evento: " . $e->getMessage());
} finally {
    // Cerrar todas las declaraciones y conexiones
    if (isset($stmt)) $stmt->close();
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($conn)) $conn->close();
}
