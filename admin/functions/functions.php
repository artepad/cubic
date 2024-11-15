<?php
// functions/functions.php

// Verificar autenticación
function checkAuthentication() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Obtener total de clientes
function getTotalClientes($conn) {
    try {
        $sql = "SELECT COUNT(*) as total FROM clientes";
        $stmt = $conn->prepare($sql);
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta");
        }
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    } catch (Exception $e) {
        error_log("Error en getTotalClientes: " . $e->getMessage());
        return 0;
    }
}

// Obtener total de eventos confirmados activos
function getTotalEventosConfirmadosActivos($conn) {
    try {
        $sql = "SELECT COUNT(*) as total FROM eventos 
                WHERE estado_evento = 'Confirmado' 
                AND fecha_evento >= CURDATE()";
        $stmt = $conn->prepare($sql);
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta");
        }
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    } catch (Exception $e) {
        error_log("Error en getTotalEventosConfirmadosActivos: " . $e->getMessage());
        return 0;
    }
}

// Obtener total de eventos del año actual
function getTotalEventosAnioActual($conn) {
    try {
        $sql = "SELECT COUNT(*) as total FROM eventos 
                WHERE YEAR(fecha_evento) = YEAR(CURDATE())";
        $stmt = $conn->prepare($sql);
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta");
        }
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    } catch (Exception $e) {
        error_log("Error en getTotalEventosAnioActual: " . $e->getMessage());
        return 0;
    }
}

// Obtener datos de un cliente específico
function obtenerDatosCliente($conn, $cliente_id) {
    try {
        $sql = "SELECT c.*, e.nombre as nombre_empresa, 
                       e.rut as rut_empresa, 
                       e.direccion as direccion_empresa
                FROM clientes c 
                LEFT JOIN empresas e ON c.id = e.cliente_id 
                WHERE c.id = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta");
        }

        $stmt->bind_param("i", $cliente_id);
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta");
        }

        $result = $stmt->get_result();
        return ($result->num_rows > 0) ? $result->fetch_assoc() : null;
    } catch (Exception $e) {
        error_log("Error en obtenerDatosCliente: " . $e->getMessage());
        return null;
    }
}

// Obtener lista de clientes
function obtenerListaClientes($conn) {
    try {
        $sql = "SELECT id, nombres, apellidos FROM clientes ORDER BY nombres, apellidos";
        $stmt = $conn->prepare($sql);
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta");
        }
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error en obtenerListaClientes: " . $e->getMessage());
        return [];
    }
}

// Obtener giras recientes
function obtenerGirasRecientes($conn) {
    try {
        $sql = "SELECT id, nombre FROM giras ORDER BY fecha_creacion DESC LIMIT 5";
        $stmt = $conn->prepare($sql);
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta");
        }
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error en obtenerGirasRecientes: " . $e->getMessage());
        return [];
    }
}

// Obtener lista de artistas
function obtenerArtistas($conn) {
    try {
        $sql = "SELECT id, nombre, genero_musical FROM artistas ORDER BY nombre";
        $stmt = $conn->prepare($sql);
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta");
        }
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error en obtenerArtistas: " . $e->getMessage());
        return [];
    }
}