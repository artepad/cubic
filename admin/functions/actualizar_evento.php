<?php
// Iniciar sesión y configuración
session_start();
require_once '../config/config.php';
require_once 'functions.php';

// Verificar autenticación y CSRF
checkAuthentication();

error_log("Iniciando actualización de evento");

// Verificar CSRF primero
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    error_log("Error de validación CSRF");
    die(json_encode([
        'success' => false,
        'message' => 'Error de validación de seguridad'
    ]));
}

// Verificar que se recibió un ID de evento
if (!isset($_POST['evento_id']) || !is_numeric($_POST['evento_id'])) {
    error_log("ID de evento inválido o no proporcionado");
    die(json_encode([
        'success' => false,
        'message' => 'ID de evento inválido'
    ]));
}

try {
    // Log de los datos recibidos
    error_log("Datos recibidos: " . print_r($_POST, true));

    // Validar campos requeridos
    $required_fields = ['artista_id', 'nombre_evento', 'fecha_evento', 'valor_evento', 'tipo_evento'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("El campo " . str_replace('_', ' ', $field) . " es requerido");
        }
    }

    // Sanitizar y validar datos
    $evento_id = intval($_POST['evento_id']);
    $artista_id = intval($_POST['artista_id']);
    $gira_id = !empty($_POST['gira_id']) ? intval($_POST['gira_id']) : null;
    $nombre_evento = trim($_POST['nombre_evento']);
    $fecha_evento = $_POST['fecha_evento'];
    $hora_evento = !empty($_POST['hora_evento']) ? $_POST['hora_evento'] : null;
    $ciudad_evento = !empty($_POST['ciudad_evento']) ? trim($_POST['ciudad_evento']) : null;
    $lugar_evento = !empty($_POST['lugar_evento']) ? trim($_POST['lugar_evento']) : null;
    $valor_evento = intval($_POST['valor_evento']);
    $tipo_evento = trim($_POST['tipo_evento']);
    $encabezado_evento = !empty($_POST['encabezado_evento']) ? trim($_POST['encabezado_evento']) : null;
    
    // Validar servicios adicionales
    $hotel = isset($_POST['hotel']) ? $_POST['hotel'] : 'No';
    $traslados = isset($_POST['traslados']) ? $_POST['traslados'] : 'No';
    $viaticos = isset($_POST['viaticos']) ? $_POST['viaticos'] : 'No';

    // Validaciones adicionales
    if ($valor_evento < 1000000 || $valor_evento > 100000000) {
        throw new Exception("El valor del evento debe estar entre $1.000.000 y $100.000.000");
    }

    if (strtotime($fecha_evento) < strtotime('today')) {
        throw new Exception("La fecha del evento no puede ser anterior a hoy");
    }

    // Verificar que el evento existe antes de actualizarlo
    $check_sql = "SELECT id FROM eventos WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $evento_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception("No se encontró el evento con ID: " . $evento_id);
    }
    $check_stmt->close();

    // Debug: Imprimir valores antes de la actualización
    error_log("Valores a actualizar: " . print_r([
        'evento_id' => $evento_id,
        'artista_id' => $artista_id,
        'gira_id' => $gira_id,
        'nombre_evento' => $nombre_evento,
        'fecha_evento' => $fecha_evento,
        'hora_evento' => $hora_evento,
        'ciudad_evento' => $ciudad_evento,
        'lugar_evento' => $lugar_evento,
        'valor_evento' => $valor_evento,
        'tipo_evento' => $tipo_evento,
        'encabezado_evento' => $encabezado_evento,
        'hotel' => $hotel,
        'traslados' => $traslados,
        'viaticos' => $viaticos
    ], true));

    // Preparar la consulta SQL
    $sql = "UPDATE eventos SET 
            artista_id = ?,
            gira_id = ?,
            nombre_evento = ?,
            fecha_evento = ?,
            hora_evento = ?,
            ciudad_evento = ?,
            lugar_evento = ?,
            valor_evento = ?,
            tipo_evento = ?,
            encabezado_evento = ?,
            hotel = ?,
            traslados = ?,
            viaticos = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("iisssssssssssi",
        $artista_id,
        $gira_id,
        $nombre_evento,
        $fecha_evento,
        $hora_evento,
        $ciudad_evento,
        $lugar_evento,
        $valor_evento,
        $tipo_evento,
        $encabezado_evento,
        $hotel,
        $traslados,
        $viaticos,
        $evento_id
    );

    if (!$stmt->execute()) {
        error_log("Error en la ejecución del query: " . $stmt->error);
        throw new Exception("Error al actualizar el evento: " . $stmt->error);
    }

    if ($stmt->affected_rows < 0) {
        error_log("Error en la actualización");
        throw new Exception("Error al actualizar el evento");
    }

    error_log("Evento actualizado correctamente. ID: " . $evento_id . ", Filas afectadas: " . $stmt->affected_rows);

    // Devolver respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Evento actualizado correctamente',
        'evento_id' => $evento_id,
        'affected_rows' => $stmt->affected_rows
    ]);

} catch (Exception $e) {
    error_log("Error en actualización: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Cerrar conexiones
    if (isset($check_stmt)) {
        $check_stmt->close();
    }
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}