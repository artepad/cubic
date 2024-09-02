<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'schaaf_producciones');

// Configuración de la aplicación
define('APP_NAME', 'Schaaf Producciones');
define('APP_URL', 'http://localhost/schaaf_producciones'); // Ajusta esto a tu URL base

// Configuración de paginación
define('RESULTADOS_POR_PAGINA', 10);

// Rutas de directorios
define('ROOT_PATH', dirname(__DIR__) . '/');
define('CONTROLLERS_PATH', ROOT_PATH . 'controllers/');
define('MODELS_PATH', ROOT_PATH . 'models/');
define('VIEWS_PATH', ROOT_PATH . 'views/');
define('HELPERS_PATH', ROOT_PATH . 'helpers/');

// Configuración de sesión
ini_set('session.cookie_lifetime', 60 * 60 * 24 * 7); // 1 semana
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 7); // 1 semana

// Zona horaria
date_default_timezone_set('America/Santiago'); // Ajusta esto a tu zona horaria

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Función para cargar clases automáticamente
spl_autoload_register(function ($class_name) {
    if (file_exists(MODELS_PATH . $class_name . '.php')) {
        require_once MODELS_PATH . $class_name . '.php';
    } elseif (file_exists(CONTROLLERS_PATH . $class_name . '.php')) {
        require_once CONTROLLERS_PATH . $class_name . '.php';
    } elseif (file_exists(HELPERS_PATH . $class_name . '.php')) {
        require_once HELPERS_PATH . $class_name . '.php';
    }
});

// Función para conectar a la base de datos
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }
    return $conn;
}