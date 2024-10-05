<?php
// Definir un array asociativo con la información de cada estado
$estadosInfo = [
    'Propuesta' => ['class' => 'warning', 'icon' => 'fa-clock-o'],
    'Confirmado' => ['class' => 'success', 'icon' => 'fa-check'],
    'Documentación' => ['class' => 'info', 'icon' => 'fa-file-text-o'],
    'En Producción' => ['class' => 'primary', 'icon' => 'fa-cogs'],
    'Finalizado' => ['class' => 'default', 'icon' => 'fa-flag-checkered'],
    'Reagendado' => ['class' => 'warning', 'icon' => 'fa-calendar'],
    'Cancelado' => ['class' => 'danger', 'icon' => 'fa-times']
];

function getEstadoInfo($estado)
{
    global $estadosInfo;
    return $estadosInfo[$estado] ?? ['class' => 'default', 'icon' => 'fa-question'];
}

// Función para generar el HTML del estado del evento
function generarEstadoEvento($estado)
{
    $info = getEstadoInfo($estado);
    return sprintf(
        '<span class="label label-%s"><i class="fa %s"></i> %s</span>',
        $info['class'],
        $info['icon'],
        htmlspecialchars($estado)
    );
}

function obtenerEstadisticas($conn)
{
    $stats = [];

    // Total de clientes
    $sql_total_clientes = "SELECT COUNT(*) as total FROM clientes";
    $result = $conn->query($sql_total_clientes);
    $stats['total_clientes'] = $result->fetch_assoc()['total'];

    // Total de eventos activos
    $sql_eventos_activos = "SELECT COUNT(*) as total FROM eventos WHERE fecha_evento >= CURDATE()";
    $result = $conn->query($sql_eventos_activos);
    $stats['total_eventos_activos'] = $result->fetch_assoc()['total'];

    return $stats;
}
