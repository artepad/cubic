<?php
// Definir constantes para la configuración
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'schaaf_producciones');

// Función para obtener conexión a la base de datos
function getDbConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                throw new Exception("Error de conexión: " . $conn->connect_error);
            }
            
            // Establecer charset a utf8mb4
            if (!$conn->set_charset("utf8mb4")) {
                throw new Exception("Error cargando el conjunto de caracteres utf8mb4");
            }
            
            // Configurar el modo estricto de SQL
            $conn->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
            
        } catch (Exception $e) {
            error_log("Error en la conexión a la base de datos: " . $e->getMessage());
            die("Error en la conexión a la base de datos. Por favor, contacte al administrador.");
        }
    }
    
    return $conn;
}

// Obtener la conexión
$conn = getDbConnection();
?>