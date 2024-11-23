<?php
// Configuración de directorios
define('UPLOAD_BASE_DIR', __DIR__ . '/uploads');
define('EVENTOS_UPLOAD_DIR', UPLOAD_BASE_DIR . '/eventos');

// Función para crear directorios si no existen
function initializeUploadDirectories() {
    $dirs = [UPLOAD_BASE_DIR, EVENTOS_UPLOAD_DIR];
    
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0755, true)) {
                die("Error: No se pudo crear el directorio $dir");
            }
        }
    }
    
    // Crear archivo .htaccess para proteger el directorio
    $htaccess = UPLOAD_BASE_DIR . '/.htaccess';
    if (!file_exists($htaccess)) {
        $content = "Options -Indexes\n";
        $content .= "<FilesMatch '\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|htm|html|shtml|sh|cgi)$'>\n";
        $content .= "    Deny from all\n";
        $content .= "</FilesMatch>\n";
        
        file_put_contents($htaccess, $content);
    }
}

// Ejecutar la inicialización
initializeUploadDirectories();

echo "Directorios de upload inicializados correctamente.";