<?php
require_once '../config/config.php';
require_once 'functions.php';

// Deshabilitar el búfer de salida
ob_end_clean();

// Establecer headers correctos
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Verificar autenticación
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $conn = getDbConnection();
    
    // Obtener eventos
    $sql = "SELECT 
            id,
            nombre_evento,
            fecha_evento,
            hora_evento,
            estado_evento
            FROM eventos 
            WHERE fecha_evento IS NOT NULL 
            ORDER BY fecha_evento ASC";
            
    $result = $conn->query($sql);
    
    $eventos = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $fecha = $row['fecha_evento'];
            $hora = $row['hora_evento'] ? $row['hora_evento'] : '00:00:00';
            
            $eventos[] = [
                'id' => (int)$row['id'],
                'title' => $row['nombre_evento'],
                'start' => $fecha . 'T' . $hora,
                'allDay' => empty($row['hora_evento']),
                'backgroundColor' => '#28a745',
                'borderColor' => '#28a745'
            ];
        }
    }
    
    // Asegurarse de que no hay salida previa
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Enviar JSON
    echo json_encode($eventos);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
exit;