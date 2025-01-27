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

// Definir el título de la página
$pageTitle = "Ingresar Nuevo Artista";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
    <!-- Estilos específicos -->
    <link href="assets/css/file-upload.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .btn-default {
            color: #000000 !important;
        }
    </style>
</head>

<body class="mini-sidebar">
    <!-- Main-Wrapper -->
    <div id="wrapper">
        <div class="preloader">
            <div class="cssload-speeding-wheel"></div>
        </div>

        <?php include 'includes/nav.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        <!-- Page-Content -->
        <div class="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-info">
                            <div class="panel-heading">Ingresar Nuevo Artista</div>
                            <div class="panel-wrapper collapse in" aria-expanded="true">
                                <div class="panel-body">
                                    <form id="artistaForm" class="form-horizontal" role="form">
                                        <?php echo getCSRFTokenField(); ?>

                                        <!-- Información Principal -->
                                        <div class="form-body">
                                            <h3 class="box-title">Información del Artista</h3>
                                            <hr class="m-t-0 m-b-40">

                                            <!-- Nombre y Género Musical -->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Nombre: <span class="text-danger">*</span></label>
                                                        <div class="col-md-9">
                                                            <input type="text" class="form-control" id="nombre" name="nombre" required maxlength="100">
                                                            <small class="help-block text-muted">Nombre artístico o nombre del grupo</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Género Musical: <span class="text-danger">*</span></label>
                                                        <div class="col-md-9">
                                                            <input type="text" class="form-control" id="genero_musical" name="genero_musical" required maxlength="50">
                                                            <small class="help-block text-muted">Género musical principal</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Descripción y Presentación -->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Descripción: <span class="text-danger">*</span></label>
                                                        <div class="col-md-9">
                                                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required></textarea>
                                                            <small class="help-block text-muted">Descripción detallada del artista o grupo</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Presentación: <span class="text-danger">*</span></label>
                                                        <div class="col-md-9">
                                                            <textarea class="form-control" id="presentacion" name="presentacion" rows="4" required></textarea>
                                                            <small class="help-block text-muted">Texto que aparecerá en las cotizaciones</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Imágenes -->
                                            <h3 class="box-title m-t-40">Imágenes</h3>
                                            <hr class="m-t-0 m-b-40">

                                            <!-- Para la imagen de presentación -->
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Imagen Principal:</label>
                                                    <div class="col-md-9">
                                                        <div id="container_imagen_presentacion" class="custom-file-upload">
                                                            <input type="file" id="imagen_presentacion" name="imagen_presentacion" class="file-input" accept="image/*">
                                                            <div class="file-label">
                                                                <i class="fa fa-cloud-upload"></i>
                                                                <span>Arrastra aquí tu imagen o haz clic para seleccionar</span>
                                                                <small class="text-muted d-block">Tamaño máximo: 10MB</small>
                                                            </div>
                                                            <div class="preview-container">
                                                                <img id="preview_imagen" src="#" alt="Previsualización">
                                                                <button type="button" class="btn-remove">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            </div>
                                                            <!-- Agregar aquí la barra de progreso -->
                                                            <div class="upload-progress" style="display: none;">
                                                                <div class="progress">
                                                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                                                </div>
                                                                <small class="upload-status">Subiendo archivo...</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Para el logo del artista -->
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Logo:</label>
                                                    <div class="col-md-9">
                                                        <div id="container_logo_artista" class="custom-file-upload">
                                                            <input type="file" id="logo_artista" name="logo_artista" class="file-input" accept="image/*">
                                                            <div class="file-label">
                                                                <i class="fa fa-cloud-upload"></i>
                                                                <span>Arrastra aquí el logo o haz clic para seleccionar</span>
                                                                <small class="text-muted d-block">Tamaño máximo: 10MB</small>
                                                            </div>
                                                            <div class="preview-container">
                                                                <img id="preview_logo" src="#" alt="Previsualización Logo">
                                                                <button type="button" class="btn-remove">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            </div>
                                                            <!-- Agregar aquí la barra de progreso -->
                                                            <div class="upload-progress" style="display: none;">
                                                                <div class="progress">
                                                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                                                </div>
                                                                <small class="upload-status">Subiendo archivo...</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Botones de acción -->
                                            <div class="form-actions">
                                                <div class="row">
                                                    <div class="col-md-12 text-center">
                                                        <button type="submit" id="crearArtistaBtn" class="btn btn-success">
                                                            <i class="fa fa-check"></i> Crear Artista
                                                        </button>
                                                        <a href="listar_artistas.php" class="btn btn-default">
                                                            Cancelar
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'includes/footer.php'; ?>
        </div>


        <!-- Scripts -->
        <?php include 'includes/scripts.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
        <script src="assets/js/file-upload.js"></script>
</body>

</html>