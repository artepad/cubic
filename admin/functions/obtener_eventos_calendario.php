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
    
    // Obtener eventos y datos relacionados
    $sql = "SELECT 
            e.id,
            e.nombre_evento,
            e.fecha_evento,
            e.hora_evento,
            e.estado_evento,
            CONCAT(c.nombres, ' ', c.apellidos) as cliente,
            a.nombre as artista
            FROM eventos e
            LEFT JOIN clientes c ON e.cliente_id = c.id
            LEFT JOIN artistas a ON e.artista_id = a.id
            WHERE e.fecha_evento IS NOT NULL 
            ORDER BY e.fecha_evento ASC";
            
    $result = $conn->query($sql);
    
    $eventos = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Determinar el color según el estado
            $color = '#6c757d'; // Color por defecto (gris)
            switch($row['estado_evento']) {
                case 'Confirmado':
                    $color = '#28a745'; // Verde
                    break;
                case 'Propuesta':
                    $color = '#ffc107'; // Amarillo
                    break;
                case 'Cancelado':
                    $color = '#dc3545'; // Rojo
                    break;
                case 'Reagendado':
                    $color = '#17a2b8'; // Azul
                    break;
            }

            // Construir el título con información adicional
            $titulo = $row['nombre_evento'];
            if (!empty($row['cliente'])) {
                $titulo .= ' - ' . $row['cliente'];
            }
            if (!empty($row['artista'])) {
                $titulo .= ' (' . $row['artista'] . ')';
            }

            $eventos[] = [
                'id' => (int)$row['id'],
                'title' => $titulo,
                'start' => $row['fecha_evento'] . 
                          (!empty($row['hora_evento']) ? 'T' . $row['hora_evento'] : ''),
                'allDay' => empty($row['hora_evento']),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'estado' => $row['estado_evento']
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
    error_log("Error en calendario: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar eventos']);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
exit;