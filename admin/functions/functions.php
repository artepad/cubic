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

?>