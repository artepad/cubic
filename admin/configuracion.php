<?php
// Iniciar sesión y configuración
session_start();
require_once 'config/config.php';
require_once 'functions/functions.php';

// Verificar autenticación
checkAuthentication();

// Obtener datos comunes
$totalClientes = getTotalClientes($conn);
$totalEventosActivos = getTotalEventosConfirmadosActivos($conn);
$totalEventosAnioActual = getTotalEventos($conn);
$totalArtistas = getTotalArtistas($conn);

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();

// Definir el título de la página y contenido específico
// Estos valores deberían ser establecidos antes de incluir este archivo
// $pageTitle = "Título de la Página";
// $contentFile = "ruta/al/contenido/especifico.php";
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
            <?php
            // Aquí se incluirá el contenido específico de cada página
            if (isset($contentFile) && file_exists($contentFile)) {
                include $contentFile;
            } else {
                echo '<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; text-align: center; color: #555;">
                            <h1 style="font-size: 24px;">🚧 Página en construcción 🚧</h1>
                            <p style="font-size: 18px;">Estamos trabajando para brindarte una mejor experiencia. ¡Vuelve pronto!</p>
                        </div>';
            }
            ?>

            <?php include 'includes/footer.php'; ?>
        </div>
        <!-- Page-Content-End -->
    </div>
    <!-- ===== Main-Wrapper-End ===== -->

</body>

</html>