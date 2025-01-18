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
    <!-- Estilos específicos para artistas -->
    <link href="assets/css/eventos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
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

                                            <!-- Imágenes -->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Imagen Principal:</label>
                                                        <div class="col-md-9">
                                                            <div class="custom-file-upload">
                                                                <div class="file-area">
                                                                    <input type="file" class="file-input" id="imagen_presentacion" name="imagen_presentacion" accept="image/*">
                                                                    <div class="file-label">
                                                                        <i class="fa fa-cloud-upload"></i>
                                                                        <span>Imagen principal para presentaciones (máx. 2MB)</span>
                                                                    </div>
                                                                </div>
                                                                <div id="preview_container_imagen" class="preview-container">
                                                                    <img id="preview_imagen" src="#" alt="Previsualización" class="img-preview">
                                                                    <button type="button" class="btn-remove" data-input="imagen_presentacion">
                                                                        <i class="fa fa-times"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Logo:</label>
                                                        <div class="col-md-9">
                                                            <div class="custom-file-upload">
                                                                <div class="file-area">
                                                                    <input type="file" class="file-input" id="logo_artista" name="logo_artista" accept="image/*">
                                                                    <div class="file-label">
                                                                        <i class="fa fa-cloud-upload"></i>
                                                                        <span>Logo del artista o grupo (máx. 2MB)</span>
                                                                    </div>
                                                                </div>
                                                                <div id="preview_container_logo" class="preview-container">
                                                                    <img id="preview_logo" src="#" alt="Previsualización Logo" class="img-preview">
                                                                    <button type="button" class="btn-remove" data-input="logo_artista">
                                                                        <i class="fa fa-times"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <style>
                                            .custom-file-upload {
                                                width: 100%;
                                            }

                                            .file-area {
                                                position: relative;
                                                background: #f8f9fa;
                                                border: 1px solid #ddd;
                                                border-radius: 4px;
                                                overflow: hidden;
                                            }

                                            .file-input {
                                                position: absolute;
                                                width: 100%;
                                                height: 100%;
                                                top: 0;
                                                left: 0;
                                                opacity: 0;
                                                cursor: pointer;
                                            }

                                            .file-label {
                                                padding: 10px 15px;
                                                text-align: center;
                                                color: #666;
                                                font-size: 13px;
                                            }

                                            .file-label i {
                                                margin-right: 8px;
                                                color: #2196F3;
                                            }

                                            .preview-container {
                                                display: none;
                                                position: relative;
                                                margin-top: 10px;
                                            }

                                            .img-preview {
                                                width: 100%;
                                                max-height: 150px;
                                                object-fit: contain;
                                                border: 1px solid #ddd;
                                                border-radius: 4px;
                                                padding: 3px;
                                            }

                                            .btn-remove {
                                                position: absolute;
                                                top: -8px;
                                                right: -8px;
                                                background: #dc3545;
                                                color: white;
                                                border: none;
                                                border-radius: 50%;
                                                width: 20px;
                                                height: 20px;
                                                font-size: 10px;
                                                padding: 0;
                                                display: flex;
                                                align-items: center;
                                                justify-content: center;
                                                cursor: pointer;
                                            }

                                            .btn-remove:hover {
                                                background: #c82333;
                                            }
                                            </style>

                                            <script>
                                            $(document).ready(function() {
                                                function initFileUpload(inputId) {
                                                    const fileInput = $(`#${inputId}`);
                                                    const previewContainer = $(`#preview_container_${inputId.split('_')[1]}`);
                                                    const preview = previewContainer.find('img');
                                                    
                                                    fileInput.change(function() {
                                                        const file = this.files[0];
                                                        handleFile(file, fileInput, preview, previewContainer);
                                                    });

                                                    // Botón de eliminar
                                                    previewContainer.find('.btn-remove').click(function() {
                                                        fileInput.val('');
                                                        previewContainer.hide();
                                                    });
                                                }

                                                function handleFile(file, fileInput, preview, previewContainer) {
                                                    if (file) {
                                                        // Validar tamaño (2MB)
                                                        if (file.size > 2 * 1024 * 1024) {
                                                            Swal.fire({
                                                                icon: 'error',
                                                                title: 'Error',
                                                                text: 'La imagen no debe superar los 2MB'
                                                            });
                                                            fileInput.val('');
                                                            return;
                                                        }

                                                        // Validar tipo
                                                        if (!file.type.startsWith('image/')) {
                                                            Swal.fire({
                                                                icon: 'error',
                                                                title: 'Error',
                                                                text: 'Solo se permiten archivos de imagen'
                                                            });
                                                            fileInput.val('');
                                                            return;
                                                        }

                                                        const reader = new FileReader();
                                                        reader.onload = function(e) {
                                                            preview.attr('src', e.target.result);
                                                            previewContainer.show();
                                                        };
                                                        reader.readAsDataURL(file);
                                                    }
                                                }

                                                // Inicializar ambos campos de archivo
                                                initFileUpload('imagen_presentacion');
                                                initFileUpload('logo_artista');
                                            });
                                            </script>

                                        </div>

                                        <!-- Botones de acción -->
                                        <div class="form-actions">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <button type="submit" id="crearArtistaBtn" class="btn btn-success">
                                                        <i class="fa fa-check"></i> Crear Artista
                                                    </button>
                                                    <a href="listar_artistas.php" class="btn btn-default">
                                                        <i class="fa fa-times"></i> Cancelar
                                                    </a>
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
    </div>

    <!-- Scripts -->
    <?php include 'includes/scripts.php'; ?>

    <script>
        $(document).ready(function() {
            // Previsualización de imágenes
            function readURL(input, previewId) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $(previewId)
                            .attr('src', e.target.result)
                            .show();
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            }

            $("#imagen_presentacion").change(function() {
                readURL(this, "#preview_imagen");
            });

            $("#logo_artista").change(function() {
                readURL(this, "#preview_logo");
            });

            // Validación del formulario
            $("#artistaForm").on('submit', function(e) {
                e.preventDefault();
                
                // Validación básica
                if (!validateForm()) {
                    return false;
                }

                // Aquí iría la lógica de envío del formulario
                Swal.fire({
                    title: 'Función no implementada',
                    text: 'La funcionalidad de guardar artistas será implementada próximamente.',
                    icon: 'info'
                });
            });

            function validateForm() {
                let isValid = true;
                const requiredFields = {
                    'nombre': 'Nombre',
                    'genero_musical': 'Género Musical',
                    'descripcion': 'Descripción',
                    'presentacion': 'Presentación'
                };

                // Validar campos requeridos
                Object.entries(requiredFields).forEach(([fieldId, fieldName]) => {
                    const field = $(`#${fieldId}`);
                    if (!field.val().trim()) {
                        isValid = false;
                        field.addClass('is-invalid');
                        Swal.fire({
                            title: 'Error',
                            text: `El campo "${fieldName}" es requerido`,
                            icon: 'error'
                        });
                    } else {
                        field.removeClass('is-invalid');
                    }
                });

                // Validar tamaño de archivos
                const maxSize = 2 * 1024 * 1024; // 2MB
                const imageFields = ['imagen_presentacion', 'logo_artista'];

                imageFields.forEach(fieldId => {
                    const input = document.getElementById(fieldId);
                    if (input.files.length > 0 && input.files[0].size > maxSize) {
                        isValid = false;
                        Swal.fire({
                            title: 'Error',
                            text: `La imagen ${fieldId.replace('_', ' ')} debe ser menor a 2MB`,
                            icon: 'error'
                        });
                    }
                });

                return isValid;
            }
        });
    </script>
</body>

</html>