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

// Funciones específicas de eventos.php
function obtenerDatosCliente($conn, $cliente_id)
{
    $sql = "SELECT c.*, e.nombre as nombre_empresa, e.rut as rut_empresa, e.direccion as direccion_empresa
            FROM clientes c 
            LEFT JOIN empresas e ON c.id = e.cliente_id 
            WHERE c.id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("i", $cliente_id);
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }

    $result = $stmt->get_result();
    return ($result->num_rows > 0) ? $result->fetch_assoc() : null;
}

function obtenerListaClientes($conn)
{
    $sql = "SELECT id, nombres, apellidos FROM clientes ORDER BY nombres, apellidos";
    $result = $conn->query($sql);
    if ($result === false) {
        throw new Exception("Error al obtener la lista de clientes: " . $conn->error);
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

function obtenerGirasRecientes($conn)
{
    $sql = "SELECT id, nombre FROM giras ORDER BY fecha_creacion DESC LIMIT 5";
    $result = $conn->query($sql);
    if ($result === false) {
        throw new Exception("Error al obtener las giras: " . $conn->error);
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Procesar la solicitud
try {
    $cliente_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $cliente = null;
    $clientes = [];
    $giras = obtenerGirasRecientes($conn);

    if ($cliente_id > 0) {
        $cliente = obtenerDatosCliente($conn, $cliente_id);
        if (!$cliente) {
            throw new Exception("Cliente no encontrado");
        }
    } else {
        $clientes = obtenerListaClientes($conn);
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();

// Definir el título de la página
$pageTitle = "Generador de Eventos";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
    <!-- Estilos adicionales específicos para eventos si es necesario -->
    <link href="assets/css/eventos.css" rel="stylesheet">
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
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-info">
                            <div class="panel-heading">Generador de Eventos</div>
                            <div class="panel-wrapper collapse in" aria-expanded="true">
                                <div class="panel-body">
                                    <form id="eventoForm" class="form-horizontal" role="form">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Gira</label>
                                                    <div class="col-md-9">
                                                        <?php if (count($giras) > 0): ?>
                                                            <select class="form-control" id="gira_id" name="gira_id">
                                                                <option value="">Seleccione una gira</option>
                                                                <?php foreach ($giras as $gira): ?>
                                                                    <option value="<?php echo $gira['id']; ?>"><?php echo htmlspecialchars($gira['nombre']); ?></option>
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
                                                    <label class="control-label col-md-3"></label>
                                                    <div class="col-md-9">
                                                        <a href="ingreso_giras.php" class="btn btn-info btn-sm text-white">
                                                            <i class="fa fa-plus"></i> Nueva Gira
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($cliente_id == 0): ?>
                                            <!-- Selector de cliente si no hay cliente seleccionado -->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Seleccionar Cliente:</label>
                                                        <div class="col-md-9">
                                                            <select class="form-control" id="cliente_id" name="cliente_id" required>
                                                                <option value="">Seleccione un cliente</option>
                                                                <?php foreach ($clientes as $c): ?>
                                                                    <option value="<?php echo $c['id']; ?>">
                                                                        <?php echo htmlspecialchars($c['nombres'] . ' ' . $c['apellidos']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <!-- Esta columna se deja vacía para mantener la alineación -->
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
                                        <?php endif; ?>

                                        <!-- Campos del formulario -->
                                        <input type="hidden" name="evento_id" id="evento_id" value="0">

                                        <!-- Sección de información del cliente -->
                                        <h3 class="box-title">Cliente</h3>
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
                                            ['name' => 'nombre_evento', 'label' => 'Nombre Evento', 'type' => 'text', 'required' => true, 'maxlength' => 60],
                                            ['name' => 'encabezado_evento', 'label' => 'Encabezado', 'type' => 'text', 'maxlength' => 100],
                                            ['name' => 'fecha_evento', 'label' => 'Fecha', 'type' => 'date', 'required' => true],
                                            ['name' => 'hora_evento', 'label' => 'Hora', 'type' => 'time', 'required' => true, 'step' => 1800],
                                            ['name' => 'ciudad_evento', 'label' => 'Ciudad', 'type' => 'text', 'required' => true, 'maxlength' => 100],
                                            ['name' => 'lugar', 'label' => 'Lugar', 'type' => 'text', 'required' => true, 'maxlength' => 150],
                                            ['name' => 'valor', 'label' => 'Valor', 'type' => 'number', 'required' => true, 'min' => 1000000, 'max' => 100000000],
                                            ['name' => 'tipo_evento', 'label' => 'Tipo de Evento', 'type' => 'text', 'required' => true, 'maxlength' => 100],
                                        ];

                                        foreach (array_chunk($event_fields, 2) as $row): ?>
                                            <div class="row">
                                                <?php foreach ($row as $field): ?>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label col-md-3"><?php echo $field['label']; ?></label>
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
                                                                <?php if ($field['name'] === 'nombre_evento' || $field['name'] === 'valor'): ?>
                                                                    <span id="<?php echo $field['name']; ?>_error" class="text-danger"></span>
                                                                <?php endif; ?>
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
                                                            <label class="control-label col-md-3"><?php echo ucfirst($option); ?></label>
                                                            <div class="col-md-9">
                                                                <div class="radio-list">
                                                                    <label class="radio-inline">
                                                                        <input type="radio" name="<?php echo $option; ?>" value="Si"> Sí
                                                                    </label>
                                                                    <label class="radio-inline">
                                                                        <input type="radio" name="<?php echo $option; ?>" value="No" checked> No
                                                                    </label>
                                                                </div>
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
                                                    <button type="button" class="btn btn-default">Cancelar</button>
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
        <!-- Page-Content-End -->
    </div>
    <!-- ===== Main-Wrapper-End ===== -->

    <!-- Modal de Confirmación -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirmar Creación de Evento</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    ¿Está seguro que desea crear un nuevo evento?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
                    <button type="button" class="btn btn-primary" id="confirmCreateEvent">Sí</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Error -->
    <div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="errorModalBody">
                    <!-- El mensaje de error se insertará aquí -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <?php include 'includes/scripts.php'; ?>
    
    <!-- Script específico para eventos -->
    <script>
        $(document).ready(function() {
            let isSubmitting = false;

            // Verificar si hay una nueva gira en la URL
            const urlParams = new URLSearchParams(window.location.search);
            const nuevaGiraId = urlParams.get('nueva_gira');

            if (nuevaGiraId) {
                $('#gira_id').val(nuevaGiraId);
                window.history.replaceState({}, document.title, window.location.pathname);
            }

            // Manejar el envío del formulario
            $('#eventoForm').on('submit', function(e) {
                e.preventDefault();
                if (!isSubmitting && validateForm()) {
                    $('#confirmationModal').modal('show');
                }
            });

            // Confirmar la creación del evento
            $('#confirmCreateEvent').on('click', function() {
                if (!isSubmitting) {
                    isSubmitting = true;
                    crearEvento();
                }
            });

            // Función para crear el evento
            function crearEvento() {
                var formData = new FormData(document.getElementById('eventoForm'));

                $.ajax({
                    url: 'functions/crear_evento.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        $('#confirmationModal').modal('hide');
                        if (response.success) {
                            window.location.href = 'index.php';
                        } else {
                            showErrorMessage(response.message || 'Error desconocido al crear el evento');
                        }
                        isSubmitting = false;
                    },
                    error: function(xhr, status, error) {
                        $('#confirmationModal').modal('hide');
                        console.error('Ajax error:', status, error);
                        showErrorMessage('Error en la conexión: ' + error);
                        isSubmitting = false;
                    }
                });
            }

            // Validación del formulario
            function validateForm() {
                var isValid = true;

                if ($('#nombre_evento').val().trim() === '') {
                    $('#nombre_evento_error').text('El nombre del evento es requerido');
                    isValid = false;
                } else {
                    $('#nombre_evento_error').text('');
                }

                var valor = $('#valor').val();
                if (valor === '' || isNaN(valor) || parseFloat(valor) < 1000000 || parseFloat(valor) > 100000000) {
                    $('#valor_error').text('El valor debe estar entre 1,000,000 y 100,000,000');
                    isValid = false;
                } else {
                    $('#valor_error').text('');
                }

                return isValid;
            }

            // Mostrar mensaje de error
            function showErrorMessage(message) {
                $('#errorModalBody').text(message);
                $('#errorModal').modal('show');
            }

            // Manejar cambio en la selección del cliente
            $('#cliente_id').on('change', function() {
                var clienteId = $(this).val();
                if (clienteId) {
                    $.ajax({
                        url: 'functions/obtener_cliente.php',
                        type: 'GET',
                        data: {
                            id: clienteId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $('#nombre_cliente').text(response.cliente.nombres + ' ' + response.cliente.apellidos);
                                $('#empresa_cliente').text(response.cliente.nombre_empresa || 'N/A');
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('AJAX error:', textStatus, errorThrown);
                            console.log('Respuesta del servidor:', jqXHR.responseText);
                            alert('Error en la conexión: ' + textStatus);
                        }
                    });
                } else {
                    $('#nombre_cliente').text('');
                    $('#empresa_cliente').text('');
                }
            });
        });
    </script>
</body>

</html>