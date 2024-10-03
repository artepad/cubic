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

function getEstadoInfo($estado) {
    global $estadosInfo;
    return $estadosInfo[$estado] ?? ['class' => 'default', 'icon' => 'fa-question'];
}

// Función para generar el HTML del estado del evento
function generarEstadoEvento($estado) {
    $info = getEstadoInfo($estado);
    return sprintf(
        '<span class="label label-%s"><i class="fa %s"></i> %s</span>',
        $info['class'],
        $info['icon'],
        htmlspecialchars($estado)
    );
}
?>