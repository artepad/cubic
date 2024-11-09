<?php
// exportar_clientes.php
session_start();
require_once 'config/config.php';
require_once 'functions/functions.php';

// Verificar autenticación
checkAuthentication();

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    die("Error de validación de seguridad");
}

// Configurar headers para la descarga del archivo CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=clientes_' . date('Y-m-d') . '.csv');

// Crear el archivo CSV
$output = fopen('php://output', 'w');

// Establecer el separador de columnas para Excel (punto y coma para mejor compatibilidad)
$separator = ";";

// UTF-8 BOM para correcta visualización de caracteres especiales en Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Encabezados de las columnas
fputcsv($output, [
    'ID',
    'Nombres',
    'Apellidos',
    'RUT',
    'Correo',
    'Celular',
    'Género',
    'Empresa',
    'Fecha de Registro'
], $separator);

// Consulta SQL para obtener los datos
$sql = "SELECT c.*, e.nombre as nombre_empresa 
        FROM clientes c 
        LEFT JOIN empresas e ON c.id = e.cliente_id 
        ORDER BY c.id";

$result = $conn->query($sql);

// Escribir los datos en el CSV
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['nombres'],
        $row['apellidos'],
        $row['rut'],
        $row['correo'],
        $row['celular'],
        $row['genero'],
        $row['nombre_empresa'],
        $row['fecha_creacion']
    ], $separator);
}

// Cerrar el archivo y la conexión
fclose($output);
$conn->close();
exit();