<?php
// Activar la visualización de errores durante el desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión y configuración
session_start();
require_once 'config/config.php';
require_once 'functions/functions.php';

// Verificar autenticación
checkAuthentication();

// Inicializar la respuesta
$response = [
    'success' => false,
    'message' => 'Error al procesar la solicitud'
];

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitud no válido');
    }

    // Verificar CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Error de validación de seguridad');
    }

    // Verificar ID del cliente
    $cliente_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($cliente_id <= 0) {
        throw new Exception('ID de cliente no válido');
    }

    // Verificar si el cliente existe y obtener información
    $stmt = $conn->prepare("SELECT nombres, apellidos FROM clientes WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $cliente_id);
    if (!$stmt->execute()) {
        throw new Exception('Error al ejecutar la consulta: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc();

    if (!$cliente) {
        throw new Exception('Cliente no encontrado');
    }

    // Iniciar transacción
    if (!$conn->begin_transaction()) {
        throw new Exception('Error al iniciar la transacción');
    }

    // Obtener IDs de eventos relacionados
    $stmt = $conn->prepare("SELECT id FROM eventos WHERE cliente_id = ?");
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta de eventos: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $eventos = $stmt->get_result();

    // Eliminar archivos físicos
    while ($evento = $eventos->fetch_assoc()) {
        $directorios = [
            "uploads/eventos/{$evento['id']}/",
            "uploads/contratos/{$evento['id']}/",
            "uploads/cotizaciones/{$evento['id']}/"
        ];

        foreach ($directorios as $directorio) {
            if (is_dir($directorio)) {
                $archivos = glob($directorio . "*");
                foreach ($archivos as $archivo) {
                    if (is_file($archivo) && !unlink($archivo)) {
                        throw new Exception('Error al eliminar el archivo: ' . $archivo);
                    }
                }
                if (is_dir($directorio) && !rmdir($directorio)) {
                    throw new Exception('Error al eliminar el directorio: ' . $directorio);
                }
            }
        }
    }

    // Eliminar registros en la base de datos
    $tablas = ['empresas', 'eventos', 'clientes'];
    foreach ($tablas as $tabla) {
        $stmt = $conn->prepare("DELETE FROM {$tabla} WHERE " . ($tabla === 'clientes' ? 'id' : 'cliente_id') . " = ?");
        if (!$stmt) {
            throw new Exception("Error al preparar la eliminación de {$tabla}: " . $conn->error);
        }
        
        $stmt->bind_param("i", $cliente_id);
        if (!$stmt->execute()) {
            throw new Exception("Error al eliminar registros de {$tabla}: " . $stmt->error);
        }
    }

    // Confirmar transacción
    if (!$conn->commit()) {
        throw new Exception('Error al confirmar la transacción');
    }

    // Registrar la eliminación exitosa
    $usuario = $_SESSION['username'] ?? 'Sistema';
    $fecha = date('Y-m-d H:i:s');
    error_log("Cliente eliminado exitosamente: {$cliente['nombres']} {$cliente['apellidos']} (ID: $cliente_id) por $usuario el $fecha");

    $response = [
        'success' => true,
        'message' => "Cliente {$cliente['nombres']} {$cliente['apellidos']} eliminado correctamente"
    ];

} catch (Exception $e) {
    // Si hay una transacción activa, hacer rollback
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }

    error_log("Error al eliminar cliente ID $cliente_id: " . $e->getMessage());
    $response['message'] = "Error al eliminar el cliente: " . $e->getMessage();
}

// Cerrar conexión
if (isset($conn)) {
    $conn->close();
}

// Enviar respuesta
header('Content-Type: application/json');
echo json_encode($response);
exit;