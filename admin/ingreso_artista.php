<?php
// Iniciar sesión y configuración
session_start();
require_once 'config/config.php';
require_once 'functions/functions.php';

// Verificar autenticación
checkAuthentication();

// Obtener el ID del artista si estamos en modo edición
$artista = null;
$modo = 'crear';
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $modo = 'editar';
    $artista_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($artista_id !== false) {
        $artista = getArtistaById($conn, $artista_id);
        if (!$artista) {
            // Redirigir si el artista no existe
            header('Location: listar_artistas.php');
            exit;
        }
    }
}

// Obtener datos comunes
$totalClientes = getTotalClientes($conn);
$totalEventosActivos = getTotalEventosConfirmadosActivos($conn);
$totalEventosAnioActual = getTotalEventos($conn);
$totalArtistas = getTotalArtistas($conn);

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();

// Definir el título de la página según el modo
$pageTitle = $modo === 'editar' ? "Editar Artista: " . htmlspecialchars($artista['nombre']) : "Ingresar Nuevo Artista";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
    <link href="assets/css/file-upload.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <style>
        .btn-default {
            color: #000000 !important;
        }

        .preview-container img.existing-image {
            max-width: 200px;
            height: auto;
            display: block;
            margin: 10px 0;
        }
    </style>
    <style>
        /* Contenedor personalizado para carga de archivos */
        .custom-file-upload {
            border: 2px dashed #ddd;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            background: #f9f9f9;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        /* Estilo para la previsualización de imágenes */
        .preview-container {
            display: none;
            margin-top: 15px;
            position: relative;
        }

        .preview-container img {
            max-width: 200px;
            max-height: 200px;
            width: auto;
            height: auto;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            object-fit: contain;
        }

        /* Estilo para imágenes existentes */
        .existing-image-container {
            margin-bottom: 15px;
        }

        .existing-image-container img {
            max-width: 200px;
            max-height: 200px;
            width: auto;
            height: auto;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            object-fit: contain;
        }

        /* Botón para remover imagen */
        .btn-remove {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #ff5555;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            line-height: 25px;
            text-align: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-remove:hover {
            background: #ff3333;
        }

        /* Estado activo cuando se está arrastrando un archivo */
        .custom-file-upload.dragover {
            background: #e3f2fd;
            border-color: #2196f3;
        }

        /* Cuando hay un archivo seleccionado */
        .custom-file-upload.has-file .file-label {
            display: none;
        }

        .custom-file-upload.has-file .preview-container {
            display: inline-block;
        }

        /* Ocultar input de archivo nativo */
        .file-input {
            display: none;
        }

        /* Estilo para el label de carga */
        .file-label {
            cursor: pointer;
            padding: 15px;
            display: block;
        }

        .file-label i {
            font-size: 24px;
            color: #666;
            margin-bottom: 10px;
        }

        /* Barra de progreso */
        .upload-progress {
            margin-top: 10px;
        }

        .upload-progress .progress {
            margin-bottom: 5px;
        }

        .upload-status {
            color: #666;
        }

        /* Responsive */
        @media (max-width: 768px) {

            .preview-container img,
            .existing-image-container img {
                max-width: 150px;
                max-height: 150px;
            }
        }
    </style>
</head>

<body class="mini-sidebar">
    <div id="wrapper">
        <div class="preloader">
            <div class="cssload-speeding-wheel"></div>
        </div>

        <?php include 'includes/nav.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div class="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-info">
                            <div class="panel-heading"><?php echo $pageTitle; ?></div>
                            <div class="panel-wrapper collapse in" aria-expanded="true">
                                <div class="panel-body">
                                    <form id="artistaForm" class="form-horizontal" role="form">
                                        <?php echo getCSRFTokenField(); ?>

                                        <!-- Campo oculto para el ID en modo edición -->
                                        <?php if ($modo === 'editar'): ?>
                                            <input type="hidden" name="artista_id" value="<?php echo htmlspecialchars($artista['id']); ?>">
                                        <?php endif; ?>

                                        <div class="form-body">
                                            <h3 class="box-title">Información del Artista</h3>
                                            <hr class="m-t-0 m-b-40">

                                            <!-- Nombre y Género Musical -->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Nombre: <span class="text-danger">*</span></label>
                                                        <div class="col-md-9">
                                                            <input type="text" class="form-control" id="nombre" name="nombre" required maxlength="100"
                                                                value="<?php echo $modo === 'editar' ? htmlspecialchars($artista['nombre']) : ''; ?>">
                                                            <small class="help-block text-muted">Nombre artístico o nombre del grupo</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Género Musical: <span class="text-danger">*</span></label>
                                                        <div class="col-md-9">
                                                            <input type="text" class="form-control" id="genero_musical" name="genero_musical" required maxlength="50"
                                                                value="<?php echo $modo === 'editar' ? htmlspecialchars($artista['genero_musical']) : ''; ?>">
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
                                                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required><?php echo $modo === 'editar' ? htmlspecialchars($artista['descripcion']) : ''; ?></textarea>
                                                            <small class="help-block text-muted">Descripción detallada del artista o grupo</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Presentación: <span class="text-danger">*</span></label>
                                                        <div class="col-md-9">
                                                            <textarea class="form-control" id="presentacion" name="presentacion" rows="4" required><?php echo $modo === 'editar' ? htmlspecialchars($artista['presentacion']) : ''; ?></textarea>
                                                            <small class="help-block text-muted">Texto que aparecerá en las cotizaciones</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Imágenes -->
                                            <h3 class="box-title m-t-40">Imágenes</h3>
                                            <hr class="m-t-0 m-b-40">

                                            <!-- Imagen de presentación -->
                                            <!-- Imagen de presentación -->
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Imagen Principal:</label>
                                                    <div class="col-md-9">
                                                        <div id="container_imagen_presentacion" class="custom-file-upload">
                                                            <?php if ($modo === 'editar' && !empty($artista['imagen_presentacion'])): ?>
                                                                <div class="existing-image-container">
                                                                    <img src="<?php echo htmlspecialchars($artista['imagen_presentacion']); ?>"
                                                                        alt="Imagen actual"
                                                                        class="existing-image">
                                                                </div>
                                                            <?php endif; ?>
                                                            <input type="file" id="imagen_presentacion" name="imagen_presentacion" class="file-input" accept="image/*">
                                                            <label for="imagen_presentacion" class="file-label">
                                                                <i class="fa fa-cloud-upload"></i>
                                                                <span>Arrastra aquí tu imagen o haz clic para seleccionar</span>
                                                                <small class="text-muted d-block">Tamaño máximo: 10MB</small>
                                                            </label>
                                                            <div class="preview-container">
                                                                <img id="preview_imagen" src="#" alt="Vista previa">
                                                                <button type="button" class="btn-remove">×</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Logo del artista -->
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Logo:</label>
                                                    <div class="col-md-9">
                                                        <div id="container_logo_artista" class="custom-file-upload">
                                                            <?php if ($modo === 'editar' && !empty($artista['logo_artista'])): ?>
                                                                <div class="existing-image-container">
                                                                    <img src="<?php echo htmlspecialchars($artista['logo_artista']); ?>"
                                                                        alt="Logo actual"
                                                                        class="existing-image">
                                                                </div>
                                                            <?php endif; ?>
                                                            <input type="file" id="logo_artista" name="logo_artista" class="file-input" accept="image/*">
                                                            <label for="logo_artista" class="file-label">
                                                                <i class="fa fa-cloud-upload"></i>
                                                                <span>Arrastra aquí el logo o haz clic para seleccionar</span>
                                                                <small class="text-muted d-block">Tamaño máximo: 10MB</small>
                                                            </label>
                                                            <div class="preview-container">
                                                                <img id="preview_logo" src="#" alt="Vista previa">
                                                                <button type="button" class="btn-remove">×</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <style>
                                                .existing-image-container {
                                                    margin-bottom: 20px;
                                                    text-align: center;
                                                }

                                                .image-wrapper {
                                                    display: inline-block;
                                                    margin-bottom: 10px;
                                                }

                                                .existing-image {
                                                    max-width: 200px;
                                                    max-height: 200px;
                                                    width: auto;
                                                    height: auto;
                                                    display: block;
                                                    margin: 0 auto;
                                                }

                                                .custom-file-upload {
                                                    border: 2px dashed #ddd;
                                                    border-radius: 4px;
                                                    padding: 20px;
                                                    text-align: center;
                                                    background: #f9f9f9;
                                                    transition: all 0.3s ease;
                                                    margin-bottom: 20px;
                                                }

                                                .file-label {
                                                    cursor: pointer;
                                                    display: block;
                                                }

                                                .file-input {
                                                    display: none;
                                                }

                                                .mt-2 {
                                                    margin-top: 10px;
                                                }

                                                .text-center {
                                                    text-align: center;
                                                }
                                            </style>

                                            <!-- Botones de acción -->
                                            <div class="form-actions">
                                                <div class="row">
                                                    <div class="col-md-12 text-center">
                                                        <button type="submit" id="submitBtn" class="btn btn-success">
                                                            <i class="fa fa-check"></i>
                                                            <?php echo $modo === 'editar' ? 'Actualizar Artista' : 'Crear Artista'; ?>
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
    </div>

    <?php include 'includes/scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="assets/js/file-upload.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Función para mostrar imágenes existentes
            function showExistingImage(container, imageUrl) {
                if (imageUrl) {
                    const previewContainer = container.querySelector('.preview-container');
                    const previewImage = previewContainer.querySelector('img');
                    previewImage.src = imageUrl;
                    container.classList.add('has-file');
                }
            }

            // Inicializar manejadores de archivos
            const managers = [
                new FileUploadManager({
                    inputSelector: '#imagen_presentacion',
                    previewSelector: '#preview_imagen',
                    containerSelector: '#container_imagen_presentacion',
                    maxSize: 10 * 1024 * 1024,
                    allowedTypes: ['image/jpeg', 'image/png', 'image/gif']
                }),
                new FileUploadManager({
                    inputSelector: '#logo_artista',
                    previewSelector: '#preview_logo',
                    containerSelector: '#container_logo_artista',
                    maxSize: 10 * 1024 * 1024,
                    allowedTypes: ['image/jpeg', 'image/png', 'image/gif']
                })
            ];

            // Mostrar imágenes existentes si estamos en modo edición
            const existingImagePresentacion = document.querySelector('.existing-image[src*="imagen_presentacion"]');
            const existingLogoArtista = document.querySelector('.existing-image[src*="logo_artista"]');

            if (existingImagePresentacion) {
                showExistingImage(document.querySelector('#container_imagen_presentacion'), existingImagePresentacion.src);
            }

            if (existingLogoArtista) {
                showExistingImage(document.querySelector('#container_logo_artista'), existingLogoArtista.src);
            }

            // Configurar manejador del formulario
            const form = document.getElementById('artistaForm');
            if (form) {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();

                    try {
                        // Mostrar loader
                        Swal.fire({
                            title: 'Procesando...',
                            text: 'Por favor espere',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        const formData = new FormData(form);

                        // Validar campos requeridos
                        const requiredFields = ['nombre', 'genero_musical', 'descripcion', 'presentacion'];
                        let hasError = false;

                        requiredFields.forEach(field => {
                            const value = formData.get(field);
                            if (!value || !value.trim()) {
                                hasError = true;
                                document.getElementById(field).classList.add('is-invalid');
                            }
                        });

                        if (hasError) {
                            throw new Error('Por favor complete todos los campos requeridos');
                        }

                        const response = await fetch('functions/procesar_artista.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            await Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: data.message,
                                showConfirmButton: true
                            });

                            // Redirigir a la lista de artistas
                            window.location.href = 'listar_artistas.php';
                        } else {
                            throw new Error(data.error || 'Error al procesar el formulario');
                        }
                    } catch (error) {
                        console.error('Error:', error);

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Ocurrió un error al procesar la solicitud'
                        });
                    }
                });

                // Remover clase is-invalid al escribir
                form.querySelectorAll('.form-control').forEach(input => {
                    input.addEventListener('input', () => {
                        input.classList.remove('is-invalid');
                    });
                });
            }
        });
    </script>
</body>

</html>