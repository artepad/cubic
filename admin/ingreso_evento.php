<?php
// Iniciar sesión y configuración
session_start();
require_once 'config/config.php';
require_once 'functions/functions.php';

// Verificar autenticación
checkAuthentication();

// Configurar manejo de errores y charset
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Asegurar la codificación UTF-8 para la conexión
if (!$conn->set_charset("utf8mb4")) {
    die("Error cargando el conjunto de caracteres utf8mb4: " . $conn->error);
}

// Obtener datos iniciales
try {
    // Obtener estadísticas generales
    $totalClientes = getTotalClientes($conn);
    $totalEventosActivos = getTotalEventosConfirmadosActivos($conn);
    $totalEventosAnioActual = getTotalEventosAnioActual($conn);
    
    // Obtener ID del cliente si se proporciona
    $cliente_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
    
    // Inicializar variables
    $cliente = null;
    $clientes = [];
    
    // Obtener datos necesarios
    $giras = obtenerGirasRecientes($conn);
    $artistas = obtenerArtistas($conn);

    // Obtener información del cliente específico o lista de clientes
    if ($cliente_id > 0) {
        $cliente = obtenerDatosCliente($conn, $cliente_id);
        if (!$cliente) {
            throw new Exception("Cliente no encontrado");
        }
    } else {
        $clientes = obtenerListaClientes($conn);
    }
} catch (Exception $e) {
    error_log("Error en la obtención de datos iniciales: " . $e->getMessage());
    die("Error: " . $e->getMessage());
} finally {
    // Cerrar la conexión después de obtener los datos necesarios
    if (isset($conn)) {
        $conn->close();
    }
}

// Definir el título de la página
$pageTitle = "Generador de Eventos";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'includes/head.php'; ?>
    <link href="assets/css/eventos.css" rel="stylesheet">
    <style>
        .help-block {
            font-size: 0.85em;
            color: #6c757d;
            margin-top: 5px;
        }
        .text-danger {
            color: #dc3545;
        }
        .form-group {
            margin-bottom: 1.5rem;
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
                            <div class="panel-heading">Generador de Eventos</div>
                            <div class="panel-wrapper collapse in" aria-expanded="true">
                                <div class="panel-body">
                                    <form id="eventoForm" class="form-horizontal" role="form">
                                        <!-- Sección de Gira -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Gira:</label>
                                                    <div class="col-md-9">
                                                        <?php if (count($giras) > 0): ?>
                                                            <select class="form-control" id="gira_id" name="gira_id">
                                                                <option value="">Seleccione una gira</option>
                                                                <?php foreach ($giras as $gira): ?>
                                                                    <option value="<?php echo htmlspecialchars($gira['id']); ?>">
                                                                        <?php echo htmlspecialchars($gira['nombre']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        <?php else: ?>
                                                            <p class="form-control-static">No hay giras disponibles. Por favor, cree una nueva gira.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="col-md-9">
                                                        <a href="ingreso_giras.php" class="btn btn-info btn-sm text-white">
                                                            <i class="fa fa-plus"></i> Nueva Gira
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Sección de Artista -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Artista:</label>
                                                    <div class="col-md-9">
                                                        <?php if (isset($artistas) && count($artistas) > 0): ?>
                                                            <select class="form-control" id="artista_id" name="artista_id" required>
                                                                <option value="">Seleccione un artista</option>
                                                                <?php foreach ($artistas as $artista): ?>
                                                                    <option value="<?php echo htmlspecialchars($artista['id']); ?>"
                                                                            data-genero="<?php echo htmlspecialchars($artista['genero_musical']); ?>">
                                                                        <?php echo htmlspecialchars($artista['nombre']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <small class="form-text text-muted" id="genero_musical_info"></small>
                                                        <?php else: ?>
                                                            <p class="form-control-static">No hay artistas disponibles. Por favor, agregue un nuevo artista.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="col-md-9">
                                                        <a href="ingreso_artista.php" class="btn btn-info btn-sm text-white">
                                                            <i class="fa fa-plus"></i> Nuevo Artista
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Sección de Cliente -->
                                        <?php if ($cliente_id == 0): ?>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Cliente:</label>
                                                        <div class="col-md-9">
                                                            <select class="form-control" id="cliente_id" name="cliente_id" required>
                                                                <option value="">Seleccione un cliente</option>
                                                                <?php foreach ($clientes as $c): ?>
                                                                    <option value="<?php echo htmlspecialchars($c['id']); ?>">
                                                                        <?php echo htmlspecialchars($c['nombres'] . ' ' . $c['apellidos']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <div class="col-md-9">
                                                            <a href="ingreso_cliente.php" class="btn btn-info btn-sm text-white">
                                                                <i class="fa fa-plus"></i> Nuevo Cliente
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
                                        <?php endif; ?>

                                        <!-- Campo oculto para ID del evento -->
                                        <input type="hidden" name="evento_id" id="evento_id" value="0">

                                        <!-- Sección de información del cliente -->
                                        <h3 class="box-title">Información del Cliente</h3>
                                        <hr class="m-t-0 m-b-40">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Nombre:</label>
                                                    <div class="col-md-9">
                                                        <p class="form-control-static" id="nombre_cliente">
                                                            <?php echo $cliente_id > 0 ? htmlspecialchars($cliente['nombres'] . ' ' . $cliente['apellidos']) : ''; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Empresa:</label>
                                                    <div class="col-md-9">
                                                        <p class="form-control-static" id="empresa_cliente">
                                                            <?php echo $cliente_id > 0 ? htmlspecialchars($cliente['nombre_empresa'] ?? 'N/A') : ''; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Sección de detalles del evento -->
                                        <h3 class="box-title">Detalles del Evento</h3>
                                        <hr class="m-t-0 m-b-40">

                                        <!-- Campos del evento -->
                                        <?php
                                        $event_fields = [
                                            [
                                                'name' => 'nombre_evento',
                                                'label' => 'Nombre Evento',
                                                'type' => 'text',
                                                'required' => true,
                                                'maxlength' => 60,
                                                'help' => 'Ingrese un nombre descriptivo para el evento'
                                            ],
                                            [
                                                'name' => 'encabezado_evento',
                                                'label' => 'Encabezado',
                                                'type' => 'text',
                                                'maxlength' => 100,
                                                'help' => 'Texto que aparecerá como título en los documentos'
                                            ],
                                            [
                                                'name' => 'fecha_evento',
                                                'label' => 'Fecha',
                                                'type' => 'date',
                                                'required' => true,
                                                'help' => 'Fecha en que se realizará el evento',
                                                'min' => date('Y-m-d')
                                            ],
                                            [
                                                'name' => 'hora_evento',
                                                'label' => 'Hora',
                                                'type' => 'time',
                                                'required' => true,
                                                'step' => 1800,
                                                'help' => 'Hora de inicio del evento'
                                            ],
                                            [
                                                'name' => 'ciudad_evento',
                                                'label' => 'Ciudad',
                                                'type' => 'text',
                                                'required' => true,
                                                'maxlength' => 100,
                                                'help' => 'Ciudad donde se realizará el evento'
                                            ],
                                            [
                                                'name' => 'lugar_evento',
                                                'label' => 'Lugar',
                                                'type' => 'text',
                                                'required' => true,
                                                'maxlength' => 150,
                                                'help' => 'Ubicación específica del evento'
                                            ],
                                            [
                                                'name' => 'valor_evento',
                                                'label' => 'Valor',
                                                'type' => 'number',
                                                'required' => true,
                                                'min' => 1000000,
                                                'max' => 100000000,
                                                'help' => 'Valor en pesos (entre 1.000.000 y 100.000.000)'
                                            ],
                                            [
                                                'name' => 'tipo_evento',
                                                'label' => 'Tipo de Evento',
                                                'type' => 'text',
                                                'required' => true,
                                                'maxlength' => 100,
                                                'help' => 'Categoría o tipo de evento (ej: Concierto, Festival, etc.)'
                                            ]
                                        ];

                                        foreach (array_chunk($event_fields, 2) as $row): ?>
                                            <div class="row">
                                                <?php foreach ($row as $field): ?>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label col-md-3">
                                                                <?php echo $field['label']; ?>
                                                                <?php if (isset($field['required']) && $field['required']): ?>
                                                                    <span class="text-danger">*</span>
                                                                <?php endif; ?>
                                                            </label>
                                                            <div class="col-md-9">
                                                                <input type="<?php echo $field['type']; ?>"
                                                                    class="form-control"
                                                                    id="<?php echo $field['name']; ?>"
                                                                    name="<?php echo $field['name']; ?>"
                                                                    <?php echo isset($field['required']) && $field['required'] ? 'required' : ''; ?>
                                                                    <?php echo isset($field['maxlength']) ? 'maxlength="' . $field['maxlength'] . '"' : ''; ?>
                                                                    <?php echo isset($field['min']) ? 'min="' . $field['min'] . '"' : ''; ?>
                                                                    <?php echo isset($field['max']) ? 'max="' . $field['max'] . '"' : ''; ?>
                                                                    <?php echo isset($field['step']) ? 'step="' . $field['step'] . '"' : ''; ?>>
                                                                <?php if (isset($field['help'])): ?>
                                                                    <small class="help-block text-muted"><?php echo $field['help']; ?></small>
                                                                <?php endif; ?>
                                                                <?php if ($field['name'] === 'nombre_evento' || $field['name'] === 'valor_evento'): ?>
                                                                    <span id="<?php echo $field['name']; ?>_error" class="text-danger"></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>

                                        <!-- Opciones adicionales -->
                                        <h3 class="box-title">Servicios Adicionales</h3>
                                        <hr class="m-t-0 m-b-40">

                                        <?php
                                        $additional_options = [
                                            'hotel' => 'Incluir servicio de hotel para el artista',
                                            'traslados' => 'Incluir servicio de traslados',
                                            'viaticos' => 'Incluir viáticos'
                                        ];

                                        foreach (array_chunk($additional_options, 2, true) as $row): ?>
                                            <div class="row">
                                                <?php foreach ($row as $key => $description): ?>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label col-md-3"><?php echo ucfirst($key); ?></label>
                                                            <div class="col-md-9">
                                                                <div class="radio-list">
                                                                    <label class="radio-inline">
                                                                        <input type="radio" name="<?php echo $key; ?>" value="Si"> Sí
                                                                    </label>
                                                                    <label class="radio-inline">
                                                                        <input type="radio" name="<?php echo $key; ?>" value="No" checked> No
                                                                    </label>
                                                                </div>
                                                                <small class="help-block text-muted"><?php echo $description; ?></small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>

                                        <!-- Botones de acción -->
                                        <div class="form-actions">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <button type="submit" id="crearEventoBtn" class="btn btn-success">
                                                        <i class="fa fa-check"></i> Crear Evento
                                                    </button>
                                                    <a href="index.php" class="btn btn-default">
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

    <!-- Modal de Confirmación -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Confirmar Creación de Evento</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="resumenEvento">
                        <h4>Resumen del Evento</h4>
                        <hr>
                        <dl class="row">
                            <dt class="col-sm-4">Gira:</dt>
                            <dd class="col-sm-8" id="resumen_gira"></dd>

                            <dt class="col-sm-4">Artista:</dt>
                            <dd class="col-sm-8" id="resumen_artista"></dd>

                            <dt class="col-sm-4">Cliente:</dt>
                            <dd class="col-sm-8" id="resumen_cliente"></dd>

                            <dt class="col-sm-4">Evento:</dt>
                            <dd class="col-sm-8" id="resumen_nombre_evento"></dd>

                            <dt class="col-sm-4">Fecha y Hora:</dt>
                            <dd class="col-sm-8" id="resumen_fecha_hora"></dd>

                            <dt class="col-sm-4">Lugar:</dt>
                            <dd class="col-sm-8" id="resumen_lugar"></dd>

                            <dt class="col-sm-4">Valor:</dt>
                            <dd class="col-sm-8" id="resumen_valor"></dd>

                            <dt class="col-sm-4">Servicios:</dt>
                            <dd class="col-sm-8" id="resumen_servicios"></dd>
                        </dl>
                    </div>
                    <p class="mt-3">¿Está seguro que desea crear este evento con la información mostrada?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-success" id="confirmCreateEvent">
                        <i class="fa fa-check"></i> Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Error -->
    <div class="modal fade" id="errorModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <i class="fa fa-exclamation-triangle fa-2x text-danger"></i>
                    <span id="errorModalBody"></span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <?php include 'includes/scripts.php'; ?>

    <script>
        $(document).ready(function() {
            let isSubmitting = false;

            // Formatear valores monetarios
            function formatMoney(amount) {
                return new Intl.NumberFormat('es-CL', {
                    style: 'currency',
                    currency: 'CLP'
                }).format(amount);
            }

            // Formatear fecha y hora
            function formatDateTime(date, time) {
                const options = {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                const formattedDate = new Date(date).toLocaleDateString('es-CL', options);
                return `${formattedDate} a las ${time} hrs.`;
            }

            // Actualizar información del artista
            $('#artista_id').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const generoMusical = selectedOption.data('genero');
                $('#genero_musical_info').text(generoMusical ? `Género musical: ${generoMusical}` : '');
            });

            // Manejar cambio en la selección del cliente
            $('#cliente_id').on('change', function() {
                const clienteId = $(this).val();
                if (clienteId) {
                    $.ajax({
                        url: 'functions/obtener_cliente.php',
                        type: 'GET',
                        data: { id: clienteId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success && response.cliente) {
                                const nombreCompleto = response.cliente.nombres + ' ' + response.cliente.apellidos;
                                $('#nombre_cliente').text(nombreCompleto);
                                $('#empresa_cliente').text(response.cliente.nombre_empresa || 'N/A');
                            } else {
                                showErrorMessage('Error: ' + (response.message || 'No se pudo obtener la información del cliente'));
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error AJAX:', {
                                status: status,
                                error: error,
                                response: xhr.responseText
                            });
                            showErrorMessage('Error en la conexión: ' + error);
                        }
                    });
                } else {
                    $('#nombre_cliente').text('');
                    $('#empresa_cliente').text('');
                }
            });

            // Validación del formulario
            function validateForm() {
                let isValid = true;
                $('.is-invalid').removeClass('is-invalid');
                $('.text-danger').text('');

                // Validar campos requeridos
                const requiredFields = {
                    'artista_id': 'Artista',
                    'cliente_id': 'Cliente',
                    'nombre_evento': 'Nombre del Evento',
                    'fecha_evento': 'Fecha',
                    'hora_evento': 'Hora',
                    'ciudad_evento': 'Ciudad',
                    'lugar_evento': 'Lugar',
                    'valor_evento': 'Valor',
                    'tipo_evento': 'Tipo de Evento'
                };

                Object.entries(requiredFields).forEach(([fieldId, fieldName]) => {
                    const field = $(`#${fieldId}`);
                    if (!field.val()) {
                        isValid = false;
                        field.addClass('is-invalid');
                        $(`#${fieldId}_error`).text(`El campo ${fieldName} es requerido`);
                    }
                });

                // Validar valor del evento
                const valorEvento = $('#valor_evento').val();
                if (valorEvento) {
                    const valor = parseInt(valorEvento);
                    if (valor < 1000000 || valor > 100000000) {
                        isValid = false;
                        $('#valor_evento').addClass('is-invalid');
                        $('#valor_evento_error').text('El valor debe estar entre $1.000.000 y $100.000.000');
                    }
                }

                // Validar fecha
                const fechaEvento = $('#fecha_evento').val();
                if (fechaEvento) {
                    const fecha = new Date(fechaEvento);
                    const hoy = new Date();
                    hoy.setHours(0, 0, 0, 0);

                    if (fecha < hoy) {
                        isValid = false;
                        $('#fecha_evento').addClass('is-invalid');
                        $('#fecha_evento_error').text('La fecha no puede ser anterior a hoy');
                    }
                }

                if (!isValid) {
                    showErrorMessage('Por favor, corrija los errores en el formulario antes de continuar.');
                }

                return isValid;
            }

            // Actualizar resumen del evento
            function actualizarResumenEvento() {
                $('#resumen_gira').text($('#gira_id option:selected').text() || 'Sin gira');
                $('#resumen_artista').text($('#artista_id option:selected').text());
                $('#resumen_cliente').text($('#nombre_cliente').text());
                $('#resumen_nombre_evento').text($('#nombre_evento').val());
                $('#resumen_fecha_hora').text(
                    formatDateTime($('#fecha_evento').val(), $('#hora_evento').val())
                );
                $('#resumen_lugar').text(
                    $('#ciudad_evento').val() + ' - ' + $('#lugar_evento').val()
                );
                $('#resumen_valor').text(formatMoney($('#valor_evento').val()));

                const servicios = [];
                ['hotel', 'traslados', 'viaticos'].forEach(function(servicio) {
                    if ($(`input[name="${servicio}"][value="Si"]`).is(':checked')) {
                        servicios.push(servicio.charAt(0).toUpperCase() + servicio.slice(1));
                    }
                });
                $('#resumen_servicios').text(servicios.length ? servicios.join(', ') : 'Ninguno');
            }

            // Función para mostrar mensajes de error
            function showErrorMessage(message) {
                $('#errorModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i>
                        ${message}
                    </div>
                `);
                $('#errorModal').modal('show');
            }

            // Manejar envío del formulario
            $('#eventoForm').on('submit', function(e) {
                e.preventDefault();
                if (!isSubmitting && validateForm()) {
                    actualizarResumenEvento();
                    $('#confirmationModal').modal('show');
                }
            });

            // Confirmar creación del evento
            $('#confirmCreateEvent').on('click', function() {
                if (!isSubmitting) {
                    isSubmitting = true;
                    $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Procesando...');
                    crearEvento();
                }
            });

            // Función para crear el evento
            function crearEvento() {
                const formData = new FormData(document.getElementById('eventoForm'));

                $.ajax({
                    url: 'functions/crear_evento.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = 'index.php?mensaje=' + encodeURIComponent('Evento creado exitosamente');
                        } else {
                            $('#confirmationModal').modal('hide');
                            showErrorMessage(response.message || 'Error desconocido al crear el evento');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#confirmationModal').modal('hide');
                        console.error('Error AJAX:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });

                        let errorMessage = 'Error en la conexión';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.message || errorMessage;
                        } catch (e) {
                            console.error('Error al parsear respuesta:', xhr.responseText);
                            errorMessage += ': ' + error;
                        }
                        
                        showErrorMessage(errorMessage);
                    },
                    complete: function() {
                        isSubmitting = false;
                        $('#confirmCreateEvent').prop('disabled', false)
                            .html('<i class="fa fa-check"></i> Confirmar');
                    }
                });
            }

            // Formatear valor del evento
            $('#valor_evento').on('input', function() {
                let valor = $(this).val().replace(/\D/g, '');
                if (valor.length > 0) {
                    valor = parseInt(valor);
                    $(this).val(valor);
                }
            });

            // Inicializar campos de fecha con valores por defecto
            const today = new Date();
            const formattedDate = today.toISOString().split('T')[0];
            $('#fecha_evento').attr('min', formattedDate);
            if (!$('#fecha_evento').val()) {
                $('#fecha_evento').val(formattedDate);
            }

            // Verificar nueva gira en URL
            const urlParams = new URLSearchParams(window.location.search);
            const nuevaGiraId = urlParams.get('nueva_gira');
            if (nuevaGiraId) {
                $('#gira_id').val(nuevaGiraId);
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>
</body>
</html>