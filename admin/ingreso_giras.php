<?php
// Iniciar sesión y configuración
session_start();
require_once 'config/config.php';
require_once 'functions/functions.php';

// Verificar autenticación
checkAuthentication();

// Obtener datos comunes
$totalClientes = getTotalClientes($conn);
$totalEventosActivos = getTotalEventosActivos($conn);
$totalEventosAnioActual = getTotalEventosAnioActual($conn);

// Lógica específica de ingreso-giras.php
$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $conn->real_escape_string($_POST['nombre']);

    $sql = "INSERT INTO giras (nombre) VALUES (?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nombre);
    
    if ($stmt->execute()) {
        $nueva_gira_id = $conn->insert_id;
        header("Location: ingreso_evento.php?nueva_gira=$nueva_gira_id");
        exit;
    } else {
        $mensaje = "Error al guardar la gira: " . $conn->error;
    }
}

// Definir el título de la página
$pageTitle = "Nueva Gira - Schaaf Producciones";

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
</head>

<body class="mini-sidebar">
    <!-- ===== Main-Wrapper ===== -->
    <div id="wrapper">
        <div class="preloader">
            <div class="cssload-speeding-wheel"></div>
        </div>

        <?php include 'includes/nav.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <!-- Page-Content -->
        <div class="page-wrapper">
            <!-- ===== Page-Container ===== -->
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="white-box">
                            <h3 class="box-title">Nueva Gira</h3>
                            <?php if ($mensaje): ?>
                                <div class="alert alert-danger"><?php echo $mensaje; ?></div>
                            <?php endif; ?>
                            <form method="post" class="form-horizontal form-material">
                                <div class="form-group">
                                    <label class="col-md-12">Nombre de la Gira</label>
                                    <div class="col-md-12">
                                        <input type="text" class="form-control form-control-line" id="nombre" name="nombre" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-12">
                                        <button type="submit" class="btn btn-success">Guardar Gira</button>
                                        <a href="eventos.php" class="btn btn-primary">Volver</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ===== Page-Container-End ===== -->

            <?php include 'includes/footer.php'; ?>
        </div>
        <!-- Page-Content-End -->
    </div>
    <!-- ===== Main-Wrapper-End ===== -->

    <?php include 'includes/scripts.php'; ?>
</body>

</html>