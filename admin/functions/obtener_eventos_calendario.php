<?php
// functions/obtener_eventos_calendario.php

require_once '../config/config.php';
require_once 'functions.php';

// Verificar la autenticación
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    exit('No autorizado');
}

try {
    // Obtener la conexión
    $conn = getDbConnection();
    
    // Obtener los eventos
    $eventos = obtenerEventosCalendario($conn);
    
    // Devolver los eventos en formato JSON
    header('Content-Type: application/json');
    echo json_encode($eventos);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}