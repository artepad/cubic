<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
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

$mensaje = '';
$cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $sql = "INSERT INTO giras (nombre) VALUES ('$nombre')";
    
    if ($conn->query($sql) === TRUE) {
        $nueva_gira_id = $conn->insert_id;
        header("Location: cotizacion.php?id=$cliente_id&nueva_gira=$nueva_gira_id");
        exit;
    } else {
        $mensaje = "Error al guardar la gira: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva Gira - Schaaf Producciones</title>
    <link href="assets/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <h1>Nueva Gira</h1>
    <?php if ($mensaje): ?>
        <div class="alert alert-danger"><?php echo $mensaje; ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre de la Gira</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        <button type="submit" class="btn btn-primary">Guardar Gira</button>
        <a href="cotizacion.php?id=<?php echo $cliente_id; ?>" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<script src="assets/plugins/bower_components/jquery/dist/jquery.min.js"></script>
<script src="assets/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>