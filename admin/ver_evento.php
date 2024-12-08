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

// Obtener detalles del evento si se proporciona un ID
$evento = [];
$evento_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($evento_id > 0) {
    $evento = obtenerDetallesEvento($conn, $evento_id);
}

// Obtener los archivos del evento
$archivos = [];
if ($evento_id > 0) {
    $archivos = getEventoArchivos($conn, $evento_id);
}

// Definir el título de la página
$pageTitle = "Detalles del Evento";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .upload-area {
            border: 2px dashed #ccc;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            background: #f9f9f9;
            transition: all 0.3s ease;
        }

        .upload-area.dragover {
            background: #e1f5fe;
            border-color: #03a9f4;
        }

        .upload-area__drop {
            padding: 20px;
        }

        .upload-area__drop i {
            color: #666;
            margin-bottom: 15px;
        }

        .upload-area__files {
            margin-top: 20px;
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #eee;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .file-item__name {
            flex-grow: 1;
            margin-right: 10px;
        }

        .file-item__remove {
            color: #dc3545;
            cursor: pointer;
        }

        .progress {
            margin-bottom: 0;
            margin-top: 5px;
        }

        /* Añade estos estilos a la sección existente de CSS */
        .table>tbody>tr>td {
            vertical-align: middle;
        }

        .m-r-5 {
            margin-right: 5px;
        }

        .btn-sm {
            padding: 4px 8px;
        }

        .table>thead>tr>th {
            font-weight: 600;
            background-color: #f5f5f5;
        }

        #archivos-lista tr:hover {
            background-color: #f9f9f9;
        }

        .file-item {
            background: #fff;
            transition: all 0.3s ease;
        }

        .file-item:hover {
            background: #f5f5f5;
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
                            <div class="panel-heading">Detalles del Evento</div>
                            <div class="panel-wrapper collapse in" aria-expanded="true">
                                <div class="panel-body">
                                    <?php if (!empty($evento)): ?>
                                        <form class="form-horizontal" role="form">
                                            <!-- Sección de información del cliente -->
                                            <h3 class="box-title">Cliente</h3>
                                            <hr class="m-t-0 m-b-40">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Nombre:</label>
                                                        <div class="col-md-9">
                                                            <p class="form-control-static"><strong><?php echo htmlspecialchars($evento['nombres'] . ' ' . $evento['apellidos']); ?></strong></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Empresa:</label>
                                                        <div class="col-md-9">
                                                            <p class="form-control-static"><strong><?php echo htmlspecialchars($evento['nombre_empresa'] ?? 'N/A'); ?></strong></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Correo:</label>
                                                    <div class="col-md-9">
                                                        <p class="form-control-static"><strong><?php
                                                                                                $value = empty($evento['correo']) ? 'N/A' : htmlspecialchars($evento['correo']);
                                                                                                echo $value;
                                                                                                ?></strong></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Celular:</label>
                                                    <div class="col-md-9">
                                                        <p class="form-control-static"><strong><?php
                                                                                                $value = empty($evento['celular']) ? 'N/A' : htmlspecialchars($evento['celular']);
                                                                                                echo $value;
                                                                                                ?></strong></p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Sección de detalles del evento -->
                                            <h3 class="box-title">Detalles del Evento</h3>
                                            <hr class="m-t-0 m-b-40">

                                            <?php
                                            $event_fields = [
                                                ['name' => 'nombre_evento', 'label' => 'Nombre Evento'],
                                                ['name' => 'encabezado_evento', 'label' => 'Encabezado'],
                                                ['name' => 'fecha_evento', 'label' => 'Fecha'],
                                                ['name' => 'hora_evento', 'label' => 'Hora'],
                                                ['name' => 'ciudad_evento', 'label' => 'Ciudad'],
                                                ['name' => 'lugar_evento', 'label' => 'Lugar'],
                                                ['name' => 'valor_evento', 'label' => 'Valor'],
                                                ['name' => 'tipo_evento', 'label' => 'Tipo de Evento'],
                                            ];

                                            foreach (array_chunk($event_fields, 2) as $row): ?>
                                                <div class="row">
                                                    <?php foreach ($row as $field): ?>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="control-label col-md-3"><?php echo $field['label']; ?>:</label>
                                                                <div class="col-md-9">
                                                                    <p class="form-control-static">
                                                                        <strong>
                                                                            <?php
                                                                            $value = $evento[$field['name']] ?? 'N/A';
                                                                            if ($field['name'] === 'fecha_evento') {
                                                                                $value = date('d/m/Y', strtotime($value));
                                                                            } elseif ($field['name'] === 'hora_evento') {
                                                                                if ($value === null || $value === '' || trim($value) === '00:00:00' || trim($value) === '01:00:00' || $value === 'N/A') {
                                                                                    $value = 'Por definir';
                                                                                } else {
                                                                                    $value = date('H:i', strtotime($value));
                                                                                }
                                                                            } elseif ($field['name'] === 'valor_evento' && $value !== 'N/A') {
                                                                                $value = '$' . number_format($value, 0, ',', '.');
                                                                            } elseif ($field['name'] === 'encabezado_evento') {
                                                                                $value = empty($value) || $value === null ? 'N/A' : $value;
                                                                            }
                                                                            echo htmlspecialchars($value);
                                                                            ?>
                                                                        </strong>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endforeach; ?>

                                            <!-- Opciones adicionales -->
                                            <?php
                                            $additional_options = ['hotel', 'traslados', 'viaticos'];
                                            foreach (array_chunk($additional_options, 2) as $row): ?>
                                                <div class="row">
                                                    <?php foreach ($row as $option): ?>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="control-label col-md-3"><?php echo ucfirst($option); ?>:</label>
                                                                <div class="col-md-9">
                                                                    <p class="form-control-static"><strong><?php echo $evento[$option] ?? 'No'; ?></strong></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endforeach; ?>


                                            <!-- Sección de Archivos Adjuntos -->
                                            <h3 class="box-title m-t-40">Archivos Adjuntos</h3>
                                            <hr class="m-t-0 m-b-20">

                                            <div class="row">
                                                <div class="col-md-12">
                                                    <?php if ($archivos && $archivos->num_rows > 0): ?>
                                                        <div class="table-responsive m-b-20">
                                                            <table class="table table-hover">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Nombre Archivo</th>
                                                                        <th>Tamaño</th>
                                                                        <th>Fecha</th>
                                                                        <th class="text-center">Acciones</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody id="archivos-lista">
                                                                    <?php
                                                                    while ($archivo = $archivos->fetch_assoc()):
                                                                        $tamano = number_format($archivo['tamano'] / 1024, 2) . ' KB';
                                                                    ?>
                                                                        <tr id="archivo-<?php echo $archivo['id']; ?>">
                                                                            <td>
                                                                                <i class="fa fa-file-o m-r-5"></i>
                                                                                <?php echo htmlspecialchars($archivo['nombre_original']); ?>
                                                                            </td>
                                                                            <td><?php echo $tamano; ?></td>
                                                                            <td><?php echo date('d/m/Y H:i', strtotime($archivo['fecha_subida'])); ?></td>
                                                                            <td class="text-center">
                                                                                <a href="descargar_archivo.php?id=<?php echo $archivo['id']; ?>"
                                                                                    class="btn btn-info btn-sm m-r-5"
                                                                                    title="Descargar">
                                                                                    <i class="fa fa-download"></i>
                                                                                </a>
                                                                                <button type="button"
                                                                                    class="btn btn-danger btn-sm eliminar-archivo"
                                                                                    data-id="<?php echo $archivo['id']; ?>"
                                                                                    title="Eliminar">
                                                                                    <i class="fa fa-trash"></i>
                                                                                </button>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endwhile; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php endif; ?>


                                                </div>
                                            </div>
                                            <div class="form-actions">
                                                <div class="row">
                                                    <div class="col-md-12 text-center">
                                                        <div class="btn-group dropup m-r-10">
                                                            <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info dropdown-toggle waves-effect waves-light" type="button">
                                                                Documentos <span class="caret"></span>
                                                            </button>
                                                            <ul role="menu" class="dropdown-menu">
                                                                <li><a href="generar_cotizacion.php?id=<?php echo $evento_id; ?>">Cotización</a></li>
                                                                <li><a href="#" id="generar-contrato">Contrato</a></li>
                                                                <li>
                                                                    <!-- Botón de subir archivos adaptado -->
                                                                    <button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#modalSubirArchivos">
                                                                        <i class="fa fa-upload m-r-5"></i> Subir Archivos
                                                                    </button>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                        <div class="btn-group dropup m-r-10">
                                                            <button aria-expanded="false" data-toggle="dropdown" class="btn btn-warning dropdown-toggle waves-effect waves-light" type="button">Opciones <span class="caret"></span></button>
                                                            <ul role="menu" class="dropdown-menu">
                                                                <li><a href="eventos.php?id=<?php echo $evento_id; ?>">Editar</a></li>
                                                                <li><a href="eliminar_evento.php?id=<?php echo $evento_id; ?>">Eliminar</a></li>
                                                                <li class="divider"></li>
                                                                <li><a href="agenda.php">Volver</a></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </form>
                                    <?php else: ?>
                                        <p>No se encontraron detalles del evento.</p>
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
    <!-- Modal para subir archivos -->
    <div class="modal fade" id="modalSubirArchivos" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Subir Archivos</h4>
                </div>
                <div class="modal-body">
                    <div class="upload-area" id="uploadArea">
                        <div class="upload-area__drop">
                            <i class="fa fa-cloud-upload fa-3x"></i>
                            <h4>Arrastra los archivos aquí</h4>
                            <p>o</p>
                            <button type="button" class="btn btn-primary" id="selectFiles">
                                Seleccionar Archivos
                            </button>
                            <input type="file" id="fileInput" multiple style="display: none;">
                        </div>
                        <div class="upload-area__files">
                            <ul id="fileList" class="list-unstyled"></ul>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <small>
                            <i class="fa fa-info-circle"></i>
                            Archivos permitidos: PDF, DOC, DOCX (Máximo 5MB por archivo)
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="uploadFiles" disabled>
                        Subir Archivos
                    </button>
                </div>
            </div>
        </div>
    </div>





    <!-- Asegúrate de que estos scripts estén incluidos al final de tu archivo, justo antes de cerrar el tag </body> -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inicializar los dropdowns de Bootstrap
            $('.dropdown-toggle').dropdown();

            // Manejo de generación y descarga de contrato
            $('#generar-contrato').on('click', function(e) {
                e.preventDefault();
                var eventoId = <?php echo json_encode($evento_id); ?>;
                var clienteName = <?php echo json_encode(trim($evento['nombres'] . ' ' . $evento['apellidos'])); ?>;

                // Función para limpiar el nombre del cliente para el nombre del archivo
                function sanitizeFileName(name) {
                    // Eliminar caracteres especiales y espacios extras
                    return name.trim()
                        .normalize("NFD")
                        .replace(/[\u0300-\u036f]/g, "") // Eliminar acentos
                        .replace(/[^a-zA-Z0-9\s]/g, "") // Solo permitir letras, números y espacios
                        .replace(/\s+/g, "_"); // Reemplazar espacios con guiones bajos
                }

                $.ajax({
                    url: 'generar_contrato.php',
                    method: 'GET',
                    data: {
                        id: eventoId
                    },
                    xhrFields: {
                        responseType: 'blob'
                    },
                    success: function(response) {
                        var blob = new Blob([response], {
                            type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                        });

                        // Crear el nombre del archivo usando el nombre del cliente
                        var fileName = "Contrato_Evento_" + sanitizeFileName(clienteName) + ".docx";

                        // Crear y activar el enlace de descarga
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = fileName;

                        // Añadir temporalmente el enlace al documento y hacer clic
                        document.body.appendChild(link);
                        link.click();

                        // Limpiar - remover el enlace y liberar el objeto URL
                        document.body.removeChild(link);
                        window.URL.revokeObjectURL(link.href);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error al generar el contrato:', error);
                        alert('Hubo un error al generar el contrato. Por favor, inténtelo de nuevo.');
                    }
                });
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            const uploadArea = $('#uploadArea');
            const fileInput = $('#fileInput');
            const fileList = $('#fileList');
            const uploadBtn = $('#uploadFiles');
            const selectFilesBtn = $('#selectFiles');
            const maxFiles = 3;
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            let files = [];

            // Manejar drag & drop
            uploadArea.on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });

            uploadArea.on('dragleave dragend drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });

            uploadArea.on('drop', function(e) {
                e.preventDefault();
                const droppedFiles = e.originalEvent.dataTransfer.files;
                handleFiles(droppedFiles);
            });

            // Manejar selección de archivos
            selectFilesBtn.click(() => fileInput.click());
            fileInput.on('change', function(e) {
                handleFiles(this.files);
            });

            function handleFiles(newFiles) {
                const remainingSlots = maxFiles - files.length;
                if (remainingSlots <= 0) {
                    alert('Máximo 3 archivos permitidos');
                    return;
                }

                Array.from(newFiles).slice(0, remainingSlots).forEach(file => {
                    if (!allowedTypes.includes(file.type)) {
                        alert(`Tipo de archivo no permitido: ${file.name}`);
                        return;
                    }
                    if (file.size > maxSize) {
                        alert(`Archivo demasiado grande: ${file.name}`);
                        return;
                    }

                    files.push(file);
                    addFileToList(file);
                });

                updateUploadButton();
            }

            function addFileToList(file) {
                const li = $('<li>')
                    .addClass('file-item')
                    .html(`
                <div class="file-item__name">${file.name}</div>
                <div class="file-item__remove">
                    <i class="fa fa-times"></i>
                </div>
            `);

                li.find('.file-item__remove').click(function() {
                    const index = files.indexOf(file);
                    if (index > -1) {
                        files.splice(index, 1);
                        li.remove();
                        updateUploadButton();
                    }
                });

                fileList.append(li);
            }

            function updateUploadButton() {
                uploadBtn.prop('disabled', files.length === 0);
            }

            // Manejar subida de archivos
            uploadBtn.click(function() {
                const formData = new FormData();
                files.forEach((file, index) => {
                    formData.append(`file${index}`, file);
                });

                formData.append('evento_id', <?php echo $evento_id; ?>);
                formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

                $.ajax({
                    url: 'subir_archivos.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        uploadBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Subiendo...');
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.message || 'Error al subir los archivos');
                        }
                    },
                    error: function() {
                        alert('Error al subir los archivos');
                    },
                    complete: function() {
                        uploadBtn.prop('disabled', false).html('Subir Archivos');
                    }
                });
            });

            // Manejar eliminación de archivos
            $('.eliminar-archivo').click(function() {
                if (!confirm('¿Estás seguro de eliminar este archivo?')) {
                    return;
                }

                const id = $(this).data('id');
                const row = $(`#archivo-${id}`);

                $.ajax({
                    url: 'eliminar_archivo.php',
                    type: 'POST',
                    data: {
                        id: id,
                        csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            row.fadeOut(function() {
                                $(this).remove();
                            });
                        } else {
                            alert(response.message || 'Error al eliminar el archivo');
                        }
                    },
                    error: function() {
                        alert('Error al eliminar el archivo');
                    }
                });
            });
        });
    </script>


</body>

<?php
// Cerrar la conexión después de que todas las operaciones de base de datos están completas
$conn->close();
?>

</html>

</html>