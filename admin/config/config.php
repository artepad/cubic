<?php
// Configuración de versión
define('APP_VERSION', '1.0.0');

// Detectar el ambiente basado en el hostname del servidor
$environment = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') ? 'local' : 'production';

// Configuraciones para cada ambiente
$config = [
    'local' => [
        'version' => APP_VERSION,
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'name' => 'schaaf_producciones'
    ],
    'production' => [
        'version' => APP_VERSION,
        'host' => '162.241.61.65',
        'user' => 'schaafpr_admin',
        'pass' => 'entrar-03',
        'name' => 'schaafpr_bd'
    ]
];

// Definir constantes según el ambiente
define('DB_HOST', $config[$environment]['host']);
define('DB_USER', $config[$environment]['user']);
define('DB_PASS', $config[$environment]['pass']);
define('DB_NAME', $config[$environment]['name']);

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

// Definir directorios de upload
define('UPLOAD_BASE_DIR', __DIR__ . '/../uploads');
define('EVENTOS_UPLOAD_DIR', UPLOAD_BASE_DIR . '/eventos');

// Crear directorios si no existen
if (!file_exists(UPLOAD_BASE_DIR)) {
    mkdir(UPLOAD_BASE_DIR, 0755, true);
}
if (!file_exists(EVENTOS_UPLOAD_DIR)) {
    mkdir(EVENTOS_UPLOAD_DIR, 0755, true);
}

// Obtener la conexión
$conn = getDbConnection();

// Debug info
if ($environment === 'local') {
    error_log("Ambiente actual: " . $environment);
    error_log("Base de datos: " . DB_NAME);
}
?>