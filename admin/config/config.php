<?php
// Definir constantes para la configuración
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'schaaf_producciones');

// Configuración adicional recomendada
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');
define('DISPLAY_DB_ERRORS', false); // Cambiar a false en producción

// Función para obtener conexión a la base de datos
function getDbConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Crear nueva conexión
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            // Verificar conexión
            if ($conn->connect_error) {
                throw new Exception("Error de conexión: " . $conn->connect_error);
            }
            
            // Establecer charset
            if (!$conn->set_charset(DB_CHARSET)) {
                throw new Exception("Error cargando el conjunto de caracteres " . DB_CHARSET);
            }
            
            // Configurar el modo estricto de SQL y otras configuraciones importantes
            $conn->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            
            // Establecer zona horaria
            $conn->query("SET time_zone = '-03:00'"); // Ajusta esto según tu zona horaria
            
            // Configurar el nivel de aislamiento de transacciones
            $conn->query("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
            
        } catch (Exception $e) {
            // Logging del error
            error_log("Error en la conexión a la base de datos: " . $e->getMessage());
            
            // Mensaje de error para el usuario
            if (DISPLAY_DB_ERRORS) {
                die("Error en la conexión a la base de datos: " . $e->getMessage());
            } else {
                die("Error en la conexión a la base de datos. Por favor, contacte al administrador.");
            }
        }
    }
    
    return $conn;
}

// Función para cerrar la conexión
function closeDbConnection() {
    $conn = getDbConnection();
    if ($conn) {
        $conn->close();
    }
}

// Registrar función para cerrar la conexión al finalizar el script
register_shutdown_function('closeDbConnection');

// Obtener la conexión
$conn = getDbConnection();

// Configurar el manejo de errores para desarrollo
if (DISPLAY_DB_ERRORS) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
} else {
    mysqli_report(MYSQLI_REPORT_OFF);
}