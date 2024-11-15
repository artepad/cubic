<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/config.php';
require_once '../functions/functions.php';

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

header('Content-Type: application/json');

// Función para enviar respuestas JSON
function sendJsonResponse($success, $message, $data = null) {
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

// Función de sanitización para reemplazar FILTER_SANITIZE_STRING
function sanitizeString($str) {
    if ($str === null) {
        return '';
    }
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

// Validar campos requeridos
function validateRequiredFields($fields) {
    foreach ($fields as $field => $value) {
        if (empty($value) && $value !== '0') {
            throw new Exception("El campo {$field} es requerido");
        }
    }
}

// Función para validar el formato de fecha
function validateDate($date) {
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
function validateTime($time) {
    if (!preg_match("/^\d{2}:\d{2}(:\d{2})?$/", $time)) {
        throw new Exception("Formato de hora inválido");
    }
}

// Función para validar el valor del evento
function validateEventValue($value) {
    if (!is_numeric($value) || $value < 1000000 || $value > 100000000) {
        throw new Exception("El valor del evento debe estar entre $1.000.000 y $100.000.000");
    }
}

// Función para validar el estado del evento
function validateEventStatus($status) {
    $validStatus = [
        'Propuesta',
        'Confirmado',
        'Documentación',
        'En Producción',
        'Finalizado',
        'Reagendado',
        'Cancelado'
    ];
    
    if (empty($status) || !in_array($status, $validStatus)) {
        return 'Propuesta'; // Valor por defecto
    }
    return $status;
}

// Función para obtener o crear gira predeterminada
function obtenerGiraPredeterminada($conn) {
    $nombreGiraPredeterminada = "Sin Gira";
    
    try {
        $sql = "SELECT id FROM giras WHERE nombre = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta de gira: " . $conn->error);
        }

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
        if (!$stmt) {
            throw new Exception("Error preparando inserción de gira: " . $conn->error);
        }

        $stmt->bind_param("s", $nombreGiraPredeterminada);
        if (!$stmt->execute()) {
            throw new Exception("Error creando gira predeterminada: " . $stmt->error);
        }
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
        'nombre_evento' => sanitizeString($_POST['nombre_evento'] ?? ''),
        'fecha_evento' => sanitizeString($_POST['fecha_evento'] ?? ''),
        'hora_evento' => sanitizeString($_POST['hora_evento'] ?? ''),
        'ciudad_evento' => sanitizeString($_POST['ciudad_evento'] ?? ''),
        'lugar_evento' => sanitizeString($_POST['lugar_evento'] ?? ''),
        'valor_evento' => filter_input(INPUT_POST, 'valor_evento', FILTER_VALIDATE_INT),
        'tipo_evento' => sanitizeString($_POST['tipo_evento'] ?? '')
    ];

    debug_log("Campos requeridos procesados", $required_fields);

    // Validar campos requeridos
    validateRequiredFields($required_fields);

    // Validar formato de fecha y hora
    validateDate($required_fields['fecha_evento']);
    validateTime($required_fields['hora_evento']);
    validateEventValue($required_fields['valor_evento']);

    // Campos opcionales con validación de estado
    $encabezado_evento = sanitizeString($_POST['encabezado_evento'] ?? '');
    $estado_evento = validateEventStatus(sanitizeString($_POST['estado_evento'] ?? ''));
    $hotel = sanitizeString($_POST['hotel'] ?? 'No');
    $traslados = sanitizeString($_POST['traslados'] ?? 'No');
    $viaticos = sanitizeString($_POST['viaticos'] ?? 'No');
    $gira_id = filter_input(INPUT_POST, 'gira_id', FILTER_VALIDATE_INT);

    debug_log("Campos opcionales procesados", [
        'encabezado_evento' => $encabezado_evento,
        'estado_evento' => $estado_evento,
        'hotel' => $hotel,
        'traslados' => $traslados,
        'viaticos' => $viaticos,
        'gira_id' => $gira_id
    ]);

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Si no se seleccionó una gira, usar la gira predeterminada
        if (!$gira_id) {
            $gira_id = obtenerGiraPredeterminada($conn);
            debug_log("Usando gira predeterminada", ['gira_id' => $gira_id]);
        }

        // Verificar si el evento ya existe
        $check_sql = "SELECT id FROM eventos WHERE nombre_evento = ? AND fecha_evento = ? AND cliente_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        if (!$check_stmt) {
            throw new Exception("Error preparando verificación: " . $conn->error);
        }

        $check_stmt->bind_param("ssi", 
            $required_fields['nombre_evento'],
            $required_fields['fecha_evento'],
            $required_fields['cliente_id']
        );

        if (!$check_stmt->execute()) {
            throw new Exception("Error ejecutando verificación: " . $check_stmt->error);
        }

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

        debug_log("SQL Query", $sql);

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta: " . $conn->error);
        }

        $stmt->bind_param("iiisssssississs",
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

        debug_log("Parámetros para insert", [
            'cliente_id' => $required_fields['cliente_id'],
            'artista_id' => $required_fields['artista_id'],
            'gira_id' => $gira_id,
            'nombre_evento' => $required_fields['nombre_evento'],
            'fecha_evento' => $required_fields['fecha_evento'],
            'hora_evento' => $required_fields['hora_evento'],
            'ciudad_evento' => $required_fields['ciudad_evento'],
            'lugar_evento' => $required_fields['lugar_evento'],
            'valor_evento' => $required_fields['valor_evento'],
            'tipo_evento' => $required_fields['tipo_evento'],
            'encabezado_evento' => $encabezado_evento,
            'estado_evento' => $estado_evento,
            'hotel' => $hotel,
            'traslados' => $traslados,
            'viaticos' => $viaticos
        ]);

        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error . " (Código: " . $stmt->errno . ")");
        }

        // Confirmar la transacción
        $conn->commit();

        // Registrar el éxito
        debug_log("Evento creado exitosamente", ["id" => $conn->insert_id]);

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
    debug_log("Error en crear_evento.php", ["error" => $e->getMessage()]);
    sendJsonResponse(false, "Error al crear el evento: " . $e->getMessage());
} finally {
    // Cerrar todas las declaraciones y conexiones
    if (isset($stmt)) $stmt->close();
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($conn) && $conn->ping()) $conn->close();
}