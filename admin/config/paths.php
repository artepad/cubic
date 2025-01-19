<?php
// config/paths.php

// Obtener la ruta base del proyecto de forma dinámica
define('BASE_PATH', dirname(__DIR__));

// Definir las rutas relativas para uploads
define('UPLOADS_DIR', 'uploads');
define('ARTISTS_UPLOAD_DIR', UPLOADS_DIR . '/artistas');

// Rutas absolutas para el sistema de archivos
define('UPLOADS_PATH', BASE_PATH . '/' . UPLOADS_DIR);
define('ARTISTS_UPLOAD_PATH', UPLOADS_PATH . '/artistas');

// Rutas relativas para URLs (acceso web)
define('UPLOADS_URL', '../' . UPLOADS_DIR);
define('ARTISTS_UPLOAD_URL', UPLOADS_URL . '/artistas');