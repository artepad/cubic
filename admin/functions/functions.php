<?php

/**
 * Funciones de autenticación y seguridad
 */
function checkAuthentication() {
    // Asegurarse que la sesión está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar si el usuario está logueado
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        // Guardar la URL actual para redirigir después del login si es necesario
        $_SESSION['redirect_url'] = $_SERVER['PHP_SELF'];
        
        // Redirigir al login
        header("Location: login.php");
        exit;
    }

    // Generar o renovar el token CSRF si no existe
    initCSRFToken();
}

/**
 * Inicializa o renueva el token CSRF
 * Esta función debe ser llamada al inicio de cada sesión
 */
function initCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        // Generar un token aleatorio de 32 bytes y convertirlo a hexadecimal
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Valida el token CSRF
 * @param string $token Token recibido para validar
 * @return bool True si el token es válido, False si no lo es
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Genera un campo oculto con el token CSRF para formularios
 * @return string HTML del campo oculto con el token CSRF
 */
function getCSRFTokenField() {
    if (!isset($_SESSION['csrf_token'])) {
        initCSRFToken();
    }
    return '<input type="hidden" name="csrf_token" value="' . 
           htmlspecialchars($_SESSION['csrf_token']) . '">';
}

/**
 * Funciones de consulta base
 */
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

/**
 * Funciones para obtener eventos
 */
// Función para obtener todos los eventos (sin filtros)
function getAllEventos($conn) {
    $sql = "SELECT e.*, c.nombres, c.apellidos 
            FROM eventos e 
            LEFT JOIN clientes c ON e.cliente_id = c.id 
            ORDER BY e.fecha_evento DESC";
    
    return executeQuery($conn, $sql);
}

// Función para obtener solo eventos confirmados y activos
function getEventosConfirmados($conn) {
    $sql = "SELECT e.*, c.nombres, c.apellidos 
            FROM eventos e 
            LEFT JOIN clientes c ON e.cliente_id = c.id 
            WHERE e.estado_evento = ? 
            AND e.fecha_evento >= CURDATE()
            ORDER BY e.fecha_evento ASC";
    
    return executeQuery($conn, $sql, ['Confirmado']);
}

/**
 * Funciones para conteos y estadísticas
 */
function getTotalClientes($conn) {
    $sql = "SELECT COUNT(*) as total FROM clientes";
    $result = executeQuery($conn, $sql);
    return $result->fetch_assoc()['total'];
}

function getTotalEventosActivos($conn) {
    $sql = "SELECT COUNT(*) as total FROM eventos WHERE fecha_evento >= CURDATE()";
    $result = executeQuery($conn, $sql);
    return $result->fetch_assoc()['total'];
}

function getTotalEventosAnioActual($conn) {
    $sql = "SELECT COUNT(*) as total FROM eventos WHERE YEAR(fecha_evento) = YEAR(CURDATE())";
    $result = executeQuery($conn, $sql);
    return $result->fetch_assoc()['total'];
}

function getTotalEventosConfirmadosActivos($conn) {
    $sql = "SELECT COUNT(*) as total 
            FROM eventos 
            WHERE estado_evento = ? 
            AND fecha_evento >= CURDATE()";
    
    $result = executeQuery($conn, $sql, ['Confirmado']);
    return $result->fetch_assoc()['total'];
}

/**
 * Funciones de formato y presentación
 */
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

function formatearFecha($fecha, $formato = 'd/m/Y') {
    return date($formato, strtotime($fecha));
}

function formatearHora($hora, $formato = 'H:i') {
    return date($formato, strtotime($hora));
}

/**
 * Funciones para detalles de eventos
 */
function obtenerDetallesEvento($conn, $evento_id) {
    $sql = "SELECT e.*, c.nombres, c.apellidos, c.correo, c.celular, 
                   em.nombre as nombre_empresa, g.nombre as nombre_gira
            FROM eventos e
            LEFT JOIN clientes c ON e.cliente_id = c.id
            LEFT JOIN empresas em ON c.id = em.cliente_id
            LEFT JOIN giras g ON e.gira_id = g.id
            WHERE e.id = ?";
    
    $result = executeQuery($conn, $sql, [$evento_id]);
    return $result->fetch_assoc();
}