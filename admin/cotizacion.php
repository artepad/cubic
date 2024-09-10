<?php
// Habilitar la visualización de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo "No has iniciado sesión. Redirigiendo...";
    header("location: login.php");
    exit;
}

// Conectar a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "schaaf_producciones";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el ID del cliente de la URL
$cliente_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($cliente_id > 0) {
    // Consulta para obtener los datos del cliente y su empresa
    $sql_cliente = "SELECT c.*, e.nombre as nombre_empresa, e.rut as rut_empresa, e.direccion as direccion_empresa
                    FROM clientes c 
                    LEFT JOIN empresas e ON c.id = e.cliente_id 
                    WHERE c.id = ?";

    $stmt = $conn->prepare($sql_cliente);
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result_cliente = $stmt->get_result();

    if ($result_cliente->num_rows > 0) {
        $cliente = $result_cliente->fetch_assoc();
    } else {
        die("Cliente no encontrado");
    }
} else {
    die("ID de cliente no válido");
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generar Cotización - Schaaf Producciones</title>
    <link href="assets/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .radio-group {
            margin-bottom: 15px;
        }
        .radio-group label {
            margin-right: 15px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Generar Cotización para <?php echo htmlspecialchars($cliente['nombres'] . ' ' . $cliente['apellidos']); ?></h1>
    <form id="cotizacionForm" method="post" action="generar_cotizacion.php">
        <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
        
        <div class="form-group">
            <label for="encabezado">Encabezado</label>
            <input type="text" class="form-control" id="encabezado" name="encabezado" required>
        </div>
        
        <div class="form-group">
            <label for="ciudad">Ciudad</label>
            <input type="text" class="form-control" id="ciudad" name="ciudad" required>
        </div>
        
        <div class="form-group">
            <label for="fecha">Fecha</label>
            <input type="date" class="form-control" id="fecha" name="fecha" required>
        </div>
        
        <div class="form-group">
            <label for="horario">Horario</label>
            <input type="time" class="form-control" id="horario" name="horario" required>
        </div>
        
        <div class="form-group">
            <label for="evento">Evento</label>
            <input type="text" class="form-control" id="evento" name="evento" required>
        </div>
        
        <div class="form-group">
            <label for="valor">Valor</label>
            <input type="number" class="form-control" id="valor" name="valor" required>
        </div>
        
        <div class="radio-group">
            <label>Hotel:</label>
            <label><input type="radio" name="hotel" value="Si" required> Sí</label>
            <label><input type="radio" name="hotel" value="No" required> No</label>
        </div>
        
        <div class="radio-group">
            <label>Transporte:</label>
            <label><input type="radio" name="transporte" value="Si" required> Sí</label>
            <label><input type="radio" name="transporte" value="No" required> No</label>
        </div>
        
        <div class="radio-group">
            <label>Viáticos:</label>
            <label><input type="radio" name="viaticos" value="Si" required> Sí</label>
            <label><input type="radio" name="viaticos" value="No" required> No</label>
        </div>
    </form>
    
    <!-- Botón de prueba -->
    <button onclick="cargarDatosPrueba()" class="btn btn-primary mt-3">Generar Cotización de Prueba</button>
</div>

    <script src="assets/plugins/bower_components/jquery/dist/jquery.min.js"></script>
    <script src="assets/bootstrap/dist/js/bootstrap.min.js"></script>
</body>
<script>
function cargarDatosPrueba() {
    document.getElementById('encabezado').value = 'Cotización de prueba';
    document.getElementById('ciudad').value = 'Ciudad de Prueba';
    document.getElementById('fecha').value = '2024-09-15';
    document.getElementById('horario').value = '14:00';
    document.getElementById('evento').value = 'Evento de Prueba';
    document.getElementById('valor').value = '1000000';
    document.querySelector('input[name="hotel"][value="Si"]').checked = true;
    document.querySelector('input[name="transporte"][value="Si"]').checked = true;
    document.querySelector('input[name="viaticos"][value="No"]').checked = true;
    
    // Enviar el formulario automáticamente
    document.getElementById('cotizacionForm').submit();
}
</script>

<!-- ... (scripts existentes) ... -->
</body>
</html>