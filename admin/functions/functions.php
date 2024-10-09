<?php
// Función para ejecutar consultas seguras
function executeQuery($conn, $sql, $params = []) {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        die("Error en la ejecución de la consulta: " . $stmt->error);
    }
    
    return $stmt->get_result();
}

// Función para verificar la autenticación
function checkAuthentication() {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header("location: login.php");
        exit;
    }
}

// Función para obtener el total de clientes
function getTotalClientes($conn) {
    $sql = "SELECT COUNT(*) as total FROM clientes";
    $result = executeQuery($conn, $sql);
    return $result->fetch_assoc()['total'];
}

// Función para obtener el total de eventos activos
function getTotalEventosActivos($conn) {
    $sql = "SELECT COUNT(*) as total FROM eventos WHERE fecha_evento >= CURDATE()";
    $result = executeQuery($conn, $sql);
    return $result->fetch_assoc()['total'];
}

// Nueva función para obtener el total de eventos del año actual
function getTotalEventosAnioActual($conn) {
    $sql = "SELECT COUNT(*) as total FROM eventos WHERE YEAR(fecha_evento) = YEAR(CURDATE())";
    $result = executeQuery($conn, $sql);
    return $result->fetch_assoc()['total'];
}

// Otras funciones que puedas necesitar...
// Funciones relacionadas con eventos
function getEventos($conn) {
    $sql = "SELECT e.*, c.nombres, c.apellidos 
            FROM eventos e 
            LEFT JOIN clientes c ON e.cliente_id = c.id 
            ORDER BY e.fecha_creacion DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->get_result();
}

function generarEstadoEvento($estado) {
    $estadosInfo = [
        'Propuesta' => ['class' => 'warning', 'icon' => 'fa-clock-o'],
        'Confirmado' => ['class' => 'success', 'icon' => 'fa-check'],
        'Documentación' => ['class' => 'info', 'icon' => 'fa-file-text-o'],
        'En Producción' => ['class' => 'primary', 'icon' => 'fa-cogs'],
        'Finalizado' => ['class' => 'default', 'icon' => 'fa-flag-checkered'],
        'Reagendado' => ['class' => 'warning', 'icon' => 'fa-calendar'],
        'Cancelado' => ['class' => 'danger', 'icon' => 'fa-times']
    ];

    $info = $estadosInfo[$estado] ?? ['class' => 'default', 'icon' => 'fa-question'];
    return "<span class=\"label label-{$info['class']}\"><i class=\"fa {$info['icon']}\"></i> $estado</span>";
}

function getClientes($conn) {
    $sql = "SELECT c.*, e.nombre as nombre_empresa 
            FROM clientes c 
            LEFT JOIN empresas e ON c.id = e.cliente_id 
            ORDER BY c.id DESC";
    $result = $conn->query($sql);
    
    if ($result === false) {
        // Manejo de error
        error_log("Error en la consulta: " . $conn->error);
        return false;
    }
    
    return $result;
}

?>