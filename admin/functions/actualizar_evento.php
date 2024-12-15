<?php
// Iniciar sesión y configuración
session_start();
require_once '../config/config.php';
require_once 'functions.php';

// Verificar autenticación y CSRF
checkAuthentication();
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Error de validación de seguridad'
    ]));
}

// Verificar que se recibió un ID de evento
if (!isset($_POST['evento_id']) || !is_numeric($_POST['evento_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'ID de evento inválido'
    ]));
}

try {
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

    $stmt->bind_param("iissssssssssi",
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
        throw new Exception("Error al actualizar el evento: " . $stmt->error);
    }

    // Devolver respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Evento actualizado correctamente',
        'evento_id' => $evento_id
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Cerrar conexiones
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();