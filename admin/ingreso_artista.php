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

// Determinar si es edición o nuevo registro
$esEdicion = isset($_GET['id']);
$artista = null;

// Inicializar variables para mantener los valores del formulario
$nombre = $genero_musical = $descripcion = $presentacion = '';
$imagen_presentacion = $logo_artista = '';

if ($esEdicion) {
    // Obtener datos del artista para edición
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM artistas WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $artista = $result->fetch_assoc();

    if (!$artista) {
        $_SESSION['mensaje'] = "Artista no encontrado.";
        $_SESSION['mensaje_tipo'] = "danger";
        header("Location: listar_artistas.php");
        exit();
    }

    // Asignar valores existentes
    $nombre = $artista['nombre'];
    $genero_musical = $artista['genero_musical'];
    $descripcion = $artista['descripcion'];
    $presentacion = $artista['presentacion'];
    $imagen_presentacion = $artista['imagen_presentacion'];
    $logo_artista = $artista['logo_artista'];
}

$errores = [];

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capturar los valores del formulario
    $nombre = $_POST['nombre'] ?? '';
    $genero_musical = $_POST['genero_musical'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $presentacion = $_POST['presentacion'] ?? '';
    
    // Validaciones básicas
    if (empty($nombre)) {
        $errores['nombre'] = "El nombre es requerido.";
    } elseif (strlen($nombre) > 100) {
        $errores['nombre'] = "El nombre no puede exceder los 100 caracteres.";
    }

    if (empty($genero_musical)) {
        $errores['genero_musical'] = "El género musical es requerido.";
    } elseif (strlen($genero_musical) > 50) {
        $errores['genero_musical'] = "El género musical no puede exceder los 50 caracteres.";
    }

    // Procesar archivos si se han subido
    if (!empty($_FILES['imagen_presentacion']['name'])) {
        $archivo_imagen = $_FILES['imagen_presentacion'];
        $resultado_imagen = procesarArchivo($archivo_imagen, 'imagenes/presentacion/');
        if ($resultado_imagen['error']) {
            $errores['imagen_presentacion'] = $resultado_imagen['mensaje'];
        } else {
            $imagen_presentacion = $resultado_imagen['ruta'];
        }
    }

    if (!empty($_FILES['logo_artista']['name'])) {
        $archivo_logo = $_FILES['logo_artista'];
        $resultado_logo = procesarArchivo($archivo_logo, 'imagenes/logos/');
        if ($resultado_logo['error']) {
            $errores['logo_artista'] = $resultado_logo['mensaje'];
        } else {
            $logo_artista = $resultado_logo['ruta'];
        }
    }

    if (empty($errores)) {
        try {
            if ($esEdicion) {
                // Actualizar artista existente
                $sql = "UPDATE artistas SET 
                        nombre = ?,
                        genero_musical = ?,
                        descripcion = ?,
                        presentacion = ?
                        " . (!empty($imagen_presentacion) ? ", imagen_presentacion = ?" : "") . "
                        " . (!empty($logo_artista) ? ", logo_artista = ?" : "") . "
                        WHERE id = ?";

                $params = [$nombre, $genero_musical, $descripcion, $presentacion];
                if (!empty($imagen_presentacion)) {
                    $params[] = $imagen_presentacion;
                }
                if (!empty($logo_artista)) {
                    $params[] = $logo_artista;
                }
                $params[] = $id;

                $stmt = $conn->prepare($sql);
                $tipos = str_repeat('s', count($params) - 1) . 'i';
                $stmt->bind_param($tipos, ...$params);
            } else {
                // Insertar nuevo artista
                $sql = "INSERT INTO artistas (nombre, genero_musical, descripcion, presentacion, imagen_presentacion, logo_artista) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $nombre, $genero_musical, $descripcion, $presentacion, $imagen_presentacion, $logo_artista);
            }

            if ($stmt->execute()) {
                $_SESSION['mensaje'] = $esEdicion ? "Artista actualizado con éxito." : "Artista agregado con éxito.";
                $_SESSION['mensaje_tipo'] = "success";
                header("Location: listar_artistas.php");
                exit();
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            $mensaje = "Error al " . ($esEdicion ? "actualizar" : "agregar") . " el artista: " . $e->getMessage();
            $mensaje_tipo = "danger";
            error_log("Error en ingreso_artista.php: " . $e->getMessage());
        }
    }
}

// Función para procesar archivos subidos
function procesarArchivo($archivo, $directorio) {
    $resultado = [
        'error' => false,
        'mensaje' => '',
        'ruta' => ''
    ];

    // Verificar si hay errores
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $resultado['error'] = true;
        $resultado['mensaje'] = "Error al subir el archivo.";
        return $resultado;
    }

    // Verificar el tipo de archivo
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($archivo['type'], $tipos_permitidos)) {
        $resultado['error'] = true;
        $resultado['mensaje'] = "Tipo de archivo no permitido.";
        return $resultado;
    }

    // Verificar tamaño (5MB máximo)
    if ($archivo['size'] > 5 * 1024 * 1024) {
        $resultado['error'] = true;
        $resultado['mensaje'] = "El archivo es demasiado grande.";
        return $resultado;
    }

    // Generar nombre único
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombre_archivo = uniqid() . '.' . $extension;
    $ruta_destino = $directorio . $nombre_archivo;

    // Intentar mover el archivo
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        $resultado['error'] = true;
        $resultado['mensaje'] = "Error al guardar el archivo.";
        return $resultado;
    }

    $resultado['ruta'] = $ruta_destino;
    return $resultado;
}

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .error-message {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
            display: none;
        }

        .preview-image {
            max-width: 150px;
            max-height: 150px;
            margin-top: 10px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-control {
            height: 38px;
            border-radius: 4px;
            border: 1px solid #e4e7ea;
            box-shadow: none;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #7460ee;
            box-shadow: 0 0 0 0.2rem rgba(116, 96, 238, 0.25);
        }

        textarea.form-control {
            height: auto;
            min-height: 100px;
        }

        .custom-file {
            position: relative;
            display: inline-block;
            width: 100%;
            margin-bottom: 0;
        }

        .custom-file-input {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 38px;
            margin: 0;
            opacity: 0;
        }

        .custom-file-label {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            z-index: 1;
            height: 38px;
            padding: .375rem .75rem;
            font-weight: 400;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            border: 1px solid #e4e7ea;
            border-radius: 4px;
            display: flex;
            align-items: center;
        }

        .custom-file-label::after {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            z-index: 3;
            display: block;
            height: 38px;
            padding: .375rem .75rem;
            line-height: 1.5;
            color: #fff;
            content: "Examinar";
            background-color: #7460ee;
            border-left: inherit;
            border-radius: 0 4px 4px 0;
            display: flex;
            align-items: center;
        }

        .white-box {
            background: #fff;
            border-radius: 4px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 1px 4px 0 rgba(0,0,0,.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: .5rem;
        }

        .btn {
            padding: .375rem 1.5rem;
            font-size: 14px;
            font-weight: 500;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .btn-success {
            background-color: #2ecc71;
            border-color: #2ecc71;
        }

        .btn-success:hover {
            background-color: #27ae60;
            border-color: #27ae60;
        }

        .btn-default {
            background-color: #ecf0f1;
            border-color: #bdc3c7;
            color: #7f8c8d;
        }

        .btn-default:hover {
            background-color: #bdc3c7;
            border-color: #95a5a6;
        }

        .form-actions {
            padding: 20px 0;
            margin-top: 20px;
        }

        .form-actions .btn {
            padding: 8px 20px;
            font-weight: 500;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 120px;
        }

        .form-actions .btn i {
            margin-right: 8px;
        }

        .form-actions .btn-success {
            background-color: #2ecc71;
            border-color: #27ae60;
        }

        .form-actions .btn-success:hover {
            background-color: #27ae60;
            border-color: #219a52;
        }

        .mr-2 {
            margin-right: 1rem;
        }

        .d-flex {
            display: flex;
        }

        /* Estilos del formulario */
        .form-horizontal .control-label {
            padding-top: 7px;
            margin-bottom: 0;
            text-align: right;
        }

        .form-control {
            height: 38px;
            border-radius: 0;
            box-shadow: none;
            border: 1px solid #e4e7ea;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #7460ee;
            box-shadow: none;
        }

        textarea.form-control {
            height: auto;
            resize: vertical;
        }

        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }

        .preview-image {
            max-width: 150px;
            max-height: 150px;
            margin-top: 10px;
            border: 1px solid #e4e7ea;
        }

        .white-box {
            background: #fff;
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 0;
        }

        .btn {
            border-radius: 0;
            padding: 6px 15px;
            font-size: 14px;
            margin-left: 5px;
        }

        .btn-success {
            background-color: #2ecc71;
            border-color: #27ae60;
        }

        .btn-default {
            background-color: #f8f9fa;
            border-color: #ddd;
            color: #666;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-text {
            margin-top: 5px;
            font-size: 12px;
        }

        .box-title {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .text-muted {
            font-size: 13px;
            margin-bottom: 20px;
        }

        .row {
            margin-bottom: 15px;
        }

        /* Footer styles */
        .main-footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            border-top: 1px solid #e4e7ea;
            background: #f8f9fa;
        }

        .main-footer p {
            margin: 0;
            color: #666;
            font-size: 13px;
        }

        .image-preview-container {
            margin-top: 10px;
            text-align: center;
        }

        .section-divider {
            margin: 30px 0;
            border-top: 1px solid #eee;
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
                <?php if (isset($mensaje)): ?>
                    <div class="alert alert-<?php echo $mensaje_tipo; ?>"><?php echo $mensaje; ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="white-box">
                            <h3 class="box-title m-b-0"><?php echo $esEdicion ? 'Editar Artista' : 'Ingresar Nuevo Artista'; ?></h3>
                            <p class="text-muted m-b-30 font-13">Información del Artista</p>

                            <form id="artistaForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($esEdicion ? "?id=" . $id : "")); ?>" class="form-horizontal" enctype="multipart/form-data">
                                <div class="form-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Nombre del Artista</label>
                                                <input type="text" class="form-control" name="nombre" 
                                                    value="<?php echo htmlspecialchars($nombre); ?>" 
                                                    placeholder="Ingrese el nombre del artista"
                                                    required>
                                                <?php if (isset($errores['nombre'])): ?>
                                                    <span class="error-message"><?php echo $errores['nombre']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Género Musical</label>
                                                <input type="text" class="form-control" name="genero_musical" 
                                                    value="<?php echo htmlspecialchars($genero_musical); ?>" 
                                                    placeholder="Ej: Rock, Pop, Cumbia"
                                                    required>
                                                <?php if (isset($errores['genero_musical'])): ?>
                                                    <span class="error-message"><?php echo $errores['genero_musical']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="section-divider"></div>
                                    
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Descripción Detallada</label>
                                                <textarea class="form-control" name="descripcion" 
                                                    rows="4" 
                                                    placeholder="Ingrese una descripción detallada del artista..."><?php echo htmlspecialchars($descripcion); ?></textarea>
                                                <small class="form-text text-muted">Esta descripción se utilizará para información interna y gestión del artista.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Presentación para Cotizaciones</label>
                                                <textarea class="form-control" name="presentacion" 
                                                    rows="6" 
                                                    placeholder="Ingrese el texto de presentación que aparecerá en las cotizaciones..."><?php echo htmlspecialchars($presentacion); ?></textarea>
                                                <small class="form-text text-muted">Este texto aparecerá en las cotizaciones enviadas a los clientes.</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="section-divider"></div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Imagen de Presentación</label>
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" name="imagen_presentacion" id="imagen_presentacion" accept="image/*">
                                                    <label class="custom-file-label" for="imagen_presentacion">Seleccionar archivo...</label>
                                                </div>
                                                <?php if (isset($errores['imagen_presentacion'])): ?>
                                                    <span class="error-message"><?php echo $errores['imagen_presentacion']; ?></span>
                                                <?php endif; ?>
                                                <div class="image-preview-container">
                                                    <?php if (!empty($imagen_presentacion)): ?>
                                                        <img src="<?php echo htmlspecialchars($imagen_presentacion); ?>" class="preview-image" alt="Imagen de presentación">
                                                    <?php endif; ?>
                                                </div>
                                                <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Logo del Artista</label>
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" name="logo_artista" id="logo_artista" accept="image/*">
                                                    <label class="custom-file-label" for="logo_artista">Seleccionar archivo...</label>
                                                </div>
                                                <?php if (isset($errores['logo_artista'])): ?>
                                                    <span class="error-message"><?php echo $errores['logo_artista']; ?></span>
                                                <?php endif; ?>
                                                <div
                                </div>

                                <div class="form-actions">
                                    <div class="row">
                                        <div class="col-md-12 text-center">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fa fa-check"></i> <?php echo $esEdicion ? 'Actualizar' : 'Guardar'; ?>
                                            </button>
                                            <a href="listar_artistas.php" class="btn btn-default">Cancelar</a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Constantes para las validaciones
            const MAX_NOMBRE_LENGTH = 100;
            const MAX_GENERO_LENGTH = 50;
            const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB en bytes

            // Función para validar campos de texto
            function validarCampo($campo, maxLength, errorMsg) {
                const valor = $campo.val().trim();
                const $errorSpan = $campo.siblings('.error-message');
                
                if (!$errorSpan.length) {
                    $campo.after('<span class="error-message"></span>');
                }

                if (valor.length === 0) {
                    mostrarError($campo, 'Este campo es requerido');
                    return false;
                } else if (valor.length > maxLength) {
                    mostrarError($campo, errorMsg);
                    return false;
                }

                ocultarError($campo);
                return true;
            }

            // Función para mostrar mensaje de error
            function mostrarError($campo, mensaje) {
                const $errorSpan = $campo.siblings('.error-message');
                $campo.addClass('is-invalid');
                $errorSpan.text(mensaje).show();
            }

            // Función para ocultar mensaje de error
            function ocultarError($campo) {
                const $errorSpan = $campo.siblings('.error-message');
                $campo.removeClass('is-invalid');
                $errorSpan.text('').hide();
            }

            // Validación de archivos
            function validarArchivo($campo) {
                const archivo = $campo[0].files[0];
                if (!archivo) return true; // Si no hay archivo seleccionado, es válido

                if (archivo.size > MAX_FILE_SIZE) {
                    mostrarError($campo, 'El archivo no debe superar los 5MB');
                    return false;
                }

                const tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif'];
                if (!tiposPermitidos.includes(archivo.type)) {
                    mostrarError($campo, 'Solo se permiten archivos JPG, PNG y GIF');
                    return false;
                }

                ocultarError($campo);
                return true;
            }

            // Vista previa de imágenes
            function mostrarVistaPrevia($input) {
                const archivo = $input[0].files[0];
                if (archivo) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        let $preview = $input.siblings('.preview-image');
                        if (!$preview.length) {
                            $preview = $('<img class="preview-image">').insertAfter($input);
                        }
                        $preview.attr('src', e.target.result);
                    };
                    reader.readAsDataURL(archivo);
                }
            }

            // Validación en tiempo real para campos de texto
            $('input[name="nombre"]').on('input', function() {
                validarCampo($(this), MAX_NOMBRE_LENGTH, `El nombre no puede exceder los ${MAX_NOMBRE_LENGTH} caracteres`);
            });

            $('input[name="genero_musical"]').on('input', function() {
                validarCampo($(this), MAX_GENERO_LENGTH, `El género musical no puede exceder los ${MAX_GENERO_LENGTH} caracteres`);
            });

            // Vista previa y validación de archivos
            $('input[type="file"]').on('change', function() {
                if (validarArchivo($(this))) {
                    mostrarVistaPrevia($(this));
                } else {
                    $(this).val(''); // Limpiar el campo si no es válido
                }
            });

            // Validación del formulario completo antes de enviar
            $('#artistaForm').on('submit', function(e) {
                let isValid = true;

                // Validar campos requeridos
                if (!validarCampo($('input[name="nombre"]'), MAX_NOMBRE_LENGTH, 
                    `El nombre no puede exceder los ${MAX_NOMBRE_LENGTH} caracteres`)) {
                    isValid = false;
                }

                if (!validarCampo($('input[name="genero_musical"]'), MAX_GENERO_LENGTH,
                    `El género musical no puede exceder los ${MAX_GENERO_LENGTH} caracteres`)) {
                    isValid = false;
                }

                // Validar archivos si se han seleccionado
                $('input[type="file"]').each(function() {
                    if ($(this).val() && !validarArchivo($(this))) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    mostrarMensajeError('Por favor, corrija los errores en el formulario antes de continuar.');
                }
            });

            // Función para mostrar mensaje de error general
            function mostrarMensajeError(mensaje) {
                const $alertaError = $('<div>')
                    .addClass('alert alert-danger alert-dismissible fade show mt-3')
                    .attr('role', 'alert')
                    .html(`
                        ${mensaje}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    `);

                // Insertar el mensaje al principio del formulario
                $('#artistaForm').prepend($alertaError);

                // Autodesaparecer después de 5 segundos
                setTimeout(() => {
                    $alertaError.alert('close');
                }, 5000);
            }
        });
    </script>
</body>
</html>