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

function getTotalEventos($conn) {
    $sql = "SELECT COUNT(*) as total FROM eventos";
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


/**
 * Funciones para manejo de archivos de eventos
 */

 function getEventoArchivos($conn, $evento_id) {
    $sql = "SELECT * FROM evento_archivos WHERE evento_id = ? ORDER BY fecha_subida DESC";
    return executeQuery($conn, $sql, [$evento_id]);
}

function guardarArchivoEvento($conn, $evento_id, $archivo) {
    // Validar el archivo
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($archivo['type'], $allowed_types)) {
        throw new Exception('Tipo de archivo no permitido');
    }
    
    if ($archivo['size'] > $max_size) {
        throw new Exception('El archivo excede el tamaño máximo permitido (5MB)');
    }
    
    // Crear directorio específico para el evento si no existe
    $evento_dir = EVENTOS_UPLOAD_DIR . '/' . $evento_id;
    if (!file_exists($evento_dir)) {
        mkdir($evento_dir, 0755, true);
    }
    
    // Generar nombre único para el archivo
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombre_archivo = uniqid() . '_' . time() . '.' . $extension;
    $ruta_completa = $evento_dir . '/' . $nombre_archivo;
    
    // Mover el archivo
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        throw new Exception('Error al guardar el archivo');
    }
    
    // Guardar referencia en la base de datos
    $sql = "INSERT INTO evento_archivos (evento_id, nombre_original, nombre_archivo, tipo_archivo, tamano) 
            VALUES (?, ?, ?, ?, ?)";
    
    executeQuery($conn, $sql, [
        $evento_id,
        $archivo['name'],
        $nombre_archivo,
        $archivo['type'],
        $archivo['size']
    ]);
    
    return true;
}

function eliminarArchivoEvento($conn, $archivo_id) {
    // Obtener información del archivo
    $sql = "SELECT * FROM evento_archivos WHERE id = ?";
    $result = executeQuery($conn, $sql, [$archivo_id]);
    $archivo = $result->fetch_assoc();
    
    if (!$archivo) {
        throw new Exception('Archivo no encontrado');
    }
    
    // Eliminar archivo físico
    $ruta_archivo = EVENTOS_UPLOAD_DIR . '/' . $archivo['evento_id'] . '/' . $archivo['nombre_archivo'];
    if (file_exists($ruta_archivo)) {
        unlink($ruta_archivo);
    }
    
    // Eliminar registro de la base de datos
    $sql = "DELETE FROM evento_archivos WHERE id = ?";
    executeQuery($conn, $sql, [$archivo_id]);
    
    return true;
}