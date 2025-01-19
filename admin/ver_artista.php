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

// Obtener detalles del artista si se proporciona un ID
$artista = [];
$artista_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($artista_id > 0) {
    $artista = getArtistaById($conn, $artista_id);
}

// Definir el título de la página
$pageTitle = "Detalles del Artista";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <?php include 'includes/head.php'; ?>
    <style>
        .imagen-preview {
            max-width: 350px;
            /* Reducimos el ancho máximo */
            max-height: 250px;
            /* Establecemos una altura máxima */
            width: auto;
            /* Mantenemos la proporción */
            height: auto;
            /* Mantenemos la proporción */
            margin: 15px 0;
            border-radius: 6px;
            /* Bordes redondeados más sutiles */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            /* Sombra más sutil */
            object-fit: contain;
            /* Asegura que la imagen completa sea visible */
            background-color: #f8f8f8;
            /* Fondo claro para imágenes transparentes */
            padding: 5px;
            /* Pequeño padding para separar del borde */
        }

        /* Contenedor de la imagen para mejor organización */
        .imagen-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 20px;
            padding: 10px;
            background-color: white;
            border: 1px solid #eee;
            border-radius: 8px;
        }

        /* Estilo para las etiquetas de las imágenes */
        .imagen-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 30px;
        }

        .descripcion-texto {
            white-space: pre-line;
            line-height: 1.6;
            color: #555;
        }

        .panel-artista {
            margin-bottom: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .panel-artista .panel-heading {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            background-color: #f5f5f5;
        }

        .form-horizontal .control-label {
            text-align: left;
            margin-bottom: 5px;
            padding-top: 7px;
        }

        .form-control-static {
            min-height: 34px;
            padding-top: 7px;
            padding-bottom: 7px;
            margin-bottom: 0;
        }

        .section-title {
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9e9e9;
        }
    </style>
</head>

<body class="mini-sidebar">
    <!-- ===== Main-Wrapper ===== -->
    <div id="wrapper">
        <?php include 'includes/nav.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <!-- Page-Content -->
        <div class="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-info">
                            <div class="panel-heading"><?php echo $pageTitle; ?></div>
                            <div class="panel-wrapper collapse in" aria-expanded="true">
                                <div class="panel-body">
                                    <?php if (!empty($artista)): ?>
                                        <form class="form-horizontal" role="form">
                                            <!-- Información básica -->
                                            <h3 class="box-title">Información General</h3>
                                            <hr class="m-t-0 m-b-40">

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Nombre:</label>
                                                        <div class="col-md-9">
                                                            <p class="form-control-static">
                                                                <strong><?php echo htmlspecialchars($artista['nombre']); ?></strong>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Género Musical:</label>
                                                        <div class="col-md-9">
                                                            <p class="form-control-static">
                                                                <strong><?php echo htmlspecialchars($artista['genero_musical']); ?></strong>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Descripción -->
                                            <h3 class="box-title section-title">Descripción del Artista</h3>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <div class="col-md-12">
                                                            <div class="descripcion-texto">
                                                                <?php echo nl2br(htmlspecialchars($artista['descripcion'])); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Presentación -->
                                            <h3 class="box-title section-title">Presentación para Cotizaciones</h3>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <div class="col-md-12">
                                                            <div class="descripcion-texto">
                                                                <?php echo nl2br(htmlspecialchars($artista['presentacion'])); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Imágenes -->
                                            <h3 class="box-title section-title">Imágenes</h3>
                                            <div class="row">
                                                <?php if (!empty($artista['imagen_presentacion'])): ?>
                                                    <div class="col-md-6">
                                                        <div class="imagen-container">
                                                            <label class="imagen-label">Imagen de Presentación:</label>
                                                            <img src="assets/img/<?php echo htmlspecialchars($artista['imagen_presentacion']); ?>"
                                                                alt="Imagen de Presentación"
                                                                class="imagen-preview"
                                                                onerror="this.src='assets/img/placeholder.jpg'">
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($artista['logo_artista'])): ?>
                                                    <div class="col-md-6">
                                                        <div class="imagen-container">
                                                            <label class="imagen-label">Logo del Artista:</label>
                                                            <img src="assets/img/<?php echo htmlspecialchars($artista['logo_artista']); ?>"
                                                                alt="Logo del Artista"
                                                                class="imagen-preview"
                                                                onerror="this.src='assets/img/placeholder.jpg'">
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Botones de acción -->
                                            <div class="form-actions">
                                                <div class="row">
                                                    <div class="col-md-12 text-center">
                                                        <div class="btn-group dropup m-r-10">
                                                            <button aria-expanded="false" data-toggle="dropdown"
                                                                class="btn btn-warning dropdown-toggle waves-effect waves-light"
                                                                type="button">
                                                                Opciones <span class="caret"></span>
                                                            </button>
                                                            <ul role="menu" class="dropdown-menu">
                                                                <li>
                                                                    <a href="ingreso_artista.php?id=<?php echo $artista_id; ?>">
                                                                        <i class="fa fa-pencil m-r-5"></i> Editar
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a href="javascript:void(0)" class="eliminar-artista"
                                                                        data-id="<?php echo $artista_id; ?>">
                                                                        <i class="fa fa-trash m-r-5"></i> Eliminar
                                                                    </a>
                                                                </li>
                                                                <li class="divider"></li>
                                                                <li>
                                                                    <a href="listar_artistas.php">
                                                                        <i class="fa fa-arrow-left m-r-5"></i> Volver
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </form>
                                    <?php else: ?>
                                        <div class="alert alert-danger">
                                            <i class="fa fa-exclamation-triangle"></i> No se encontraron detalles del artista.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'includes/footer.php'; ?>
        </div>
        <!-- Page-Content-End -->
    </div>
    <!-- ===== Main-Wrapper-End ===== -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inicializar los dropdowns de Bootstrap
            $('.dropdown-toggle').dropdown();

            // Manejar eliminación de artista
            $('.eliminar-artista').on('click', function(e) {
                e.preventDefault();
                const artistaId = $(this).data('id');

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción eliminará al artista y toda su información asociada. No podrás revertir esto.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'eliminar_artista.php',
                            type: 'POST',
                            data: {
                                id: artistaId,
                                csrf_token: '<?php echo $_SESSION["csrf_token"]; ?>'
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: '¡Eliminado!',
                                        text: 'El artista ha sido eliminado correctamente.',
                                        icon: 'success',
                                        showConfirmButton: false,
                                        timer: 1500
                                    }).then(() => {
                                        window.location.href = 'listar_artistas.php';
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error',
                                        text: response.message || 'Hubo un error al eliminar el artista',
                                        icon: 'error'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Error',
                                    text: 'Hubo un error al procesar la solicitud',
                                    icon: 'error'
                                });
                            }
                        });
                    }
                });
            });
            document.addEventListener('DOMContentLoaded', function() {
                const images = document.querySelectorAll('.imagen-preview');
                images.forEach(img => {
                    img.addEventListener('error', function() {
                        this.src = 'assets/img/placeholder.jpg';
                        this.classList.add('imagen-error');
                    });
                });
            });
        });
    </script>
</body>

</html>