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

// Consulta para obtener las giras disponibles
$sql_giras = "SELECT id, nombre FROM giras ORDER BY nombre";
$result_giras = $conn->query($sql_giras);

// Verificar si se ha agregado una nueva gira
$nueva_gira_id = isset($_GET['nueva_gira']) ? intval($_GET['nueva_gira']) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generar Cotización - Schaaf Producciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: 50px;
        }
        h1 {
            color: #0056b3;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .btn-primary:hover {
            background-color: #003d82;
            border-color: #003d82;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center">Generar Cotización</h1>
    <h2 class="text-center mb-4"><?php echo htmlspecialchars($cliente['nombres'] . ' ' . $cliente['apellidos']); ?></h2>
    
    <form id="cotizacionForm" method="post" action="generar_cotizacion.php">
        <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
        
        <div class="row mb-4">
            <div class="col-md-8">
                <select id="gira" name="gira" class="form-select">
                    <option value="">Seleccione una gira</option>
                    <?php
                    if ($result_giras->num_rows > 0) {
                        while($row = $result_giras->fetch_assoc()) {
                            $selected = ($row['id'] == $nueva_gira_id) ? 'selected' : '';
                            echo "<option value='" . $row['id'] . "' $selected>" . htmlspecialchars($row['nombre']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4">
                <a href="ingreso-giras.php?cliente_id=<?php echo $cliente_id; ?>" class="btn btn-success w-100">
                    <i class="fas fa-plus-circle"></i> Nueva Gira
                </a>
            </div>
        </div>

        <input type="hidden" name="gira_id" id="gira_id" value="<?php echo $nueva_gira_id; ?>">
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="encabezado">Encabezado</label>
                    <input type="text" class="form-control" id="encabezado" name="encabezado" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="encabezado2">Encabezado 2 (Opcional)</label>
                    <input type="text" class="form-control" id="encabezado2" name="encabezado2">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="ciudad">Ciudad</label>
                    <input type="text" class="form-control" id="ciudad" name="ciudad" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="fecha">Fecha</label>
                    <input type="date" class="form-control" id="fecha" name="fecha" required>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="horario">Horario</label>
                    <input type="time" class="form-control" id="horario" name="horario" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="evento">Evento</label>
                    <input type="text" class="form-control" id="evento" name="evento" required>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="valor">Valor</label>
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" class="form-control" id="valor" name="valor" required>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="hotel" name="hotel" value="Si">
                    <label class="form-check-label" for="hotel">Hotel</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="transporte" name="transporte" value="Si">
                    <label class="form-check-label" for="transporte">Transporte</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="viaticos" name="viaticos" value="Si">
                    <label class="form-check-label" for="viaticos">Viáticos</label>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-file-invoice-dollar"></i> Generar Cotización
            </button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('#gira').on('change', function() {
        $('#gira_id').val($(this).val());
    });
});
</script>
</body>
</html>