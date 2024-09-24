<?php
// Iniciar sesión y verificar si el usuario está logueado
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    exit(json_encode(['success' => false, 'message' => 'No autorizado']));
}

// Incluir el archivo de configuración de la base de datos
require_once 'config.php';

// Verificar si se ha proporcionado un ID de cliente
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    exit(json_encode(['success' => false, 'message' => 'ID de cliente no válido']));
}

$cliente_id = intval($_GET['id']);

// Preparar la respuesta
$response = ['success' => false, 'message' => '', 'cliente' => null];

try {
    // Obtener información del cliente y su empresa asociada
    $sql_cliente = "SELECT c.*, e.nombre as nombre_empresa, e.rut as rut_empresa, e.direccion as direccion_empresa
                    FROM clientes c 
                    LEFT JOIN empresas e ON c.id = e.cliente_id 
                    WHERE c.id = ?";

    $stmt = $conn->prepare($sql_cliente);
    if ($stmt === false) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("i", $cliente_id);
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response['cliente'] = $result->fetch_assoc();
        $response['success'] = true;
    } else {
        $response['message'] = 'Cliente no encontrado';
    }
} catch (Exception $e) {
    $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
}

// Cerrar la conexión
if (isset($stmt)) $stmt->close();
$conn->close();

// Enviar la respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);