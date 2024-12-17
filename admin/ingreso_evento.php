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

// Definir funciones específicas para este archivo
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

function obtenerArtistas($conn)
{
    $sql = "SELECT id, nombre, genero_musical FROM artistas ORDER BY nombre";
    $result = $conn->query($sql);
    if ($result === false) {
        throw new Exception("Error al obtener la lista de artistas: " . $conn->error);
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Inicializar variables
$evento = null;
$is_editing = false;
$evento_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$cliente = null;
$clientes = [];
$pageTitle = "Generador de Eventos";

try {
    // Obtener listas necesarias
    $giras = obtenerGirasRecientes($conn);
    $artistas = obtenerArtistas($conn);

    if ($evento_id > 0) {
        // Modo edición
        $evento = obtenerEventoParaEditar($conn, $evento_id);
        if (!$evento) {
            throw new Exception("No se encontró el evento especificado");
        }

        $is_editing = true;
        $pageTitle = "Editar Evento";

        // Obtener datos del cliente del evento
        if (!empty($evento['cliente_id'])) {
            $cliente = obtenerDatosCliente($conn, $evento['cliente_id']);
            if (!$cliente) {
                throw new Exception("No se encontraron los datos del cliente del evento");
            }
        }
    } else {
        // Modo creación
        $clientes = obtenerListaClientes($conn);
    }
} catch (Exception $e) {
    error_log("Error en ingreso_evento.php: " . $e->getMessage());
    die("<div class='alert alert-danger m-3'>
            <h4>Error al cargar la información</h4>
            <p>Hubo un problema al cargar los datos. Por favor, intente nuevamente.</p>
            <p><small>Detalles: " . htmlspecialchars($e->getMessage()) . "</small></p>
            <a href='index.php' class='btn btn-primary'>Volver al inicio</a>
          </div>");
}

// Cerrar la conexión
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
    <!-- Estilos específicos para eventos -->
    <link href="assets/css/eventos.css" rel="stylesheet">
    <!-- En el head de ingreso_evento.php -->
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
                            <div class="panel-heading"><?php echo $is_editing ? 'Editar Evento' : 'Generador de Eventos'; ?></div>
                            <div class="panel-wrapper collapse in" aria-expanded="true">
                                <div class="panel-body">
                                    <form id="eventoForm" class="form-horizontal" role="form">
                                        <?php echo getCSRFTokenField(); ?>
                                        <input type="hidden" name="is_editing" value="<?php echo $is_editing ? '1' : '0'; ?>">
                                        <?php if ($is_editing): ?>
                                            <input type="hidden" name="evento_id" value="<?php echo htmlspecialchars($evento_id); ?>">
                                        <?php endif; ?>
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
                                                                    <option value="<?php echo $gira['id']; ?>"
                                                                        <?php echo ($is_editing && $evento['gira_id'] == $gira['id']) ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($gira['nombre']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        <?php else: ?>
                                                            <p class="form-control-static">No hay giras disponibles.</p>
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

                                        <!-- Sección de Artista -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Artista: <span class="text-danger">*</span></label>
                                                    <div class="col-md-9">
                                                        <?php if (isset($artistas) && count($artistas) > 0): ?>
                                                            <select class="form-control" id="artista_id" name="artista_id" required>
                                                                <option value="">Seleccione un artista</option>
                                                                <?php foreach ($artistas as $artista): ?>
                                                                    <option value="<?php echo $artista['id']; ?>"
                                                                        data-genero="<?php echo htmlspecialchars($artista['genero_musical']); ?>"
                                                                        <?php echo ($is_editing && $evento['artista_id'] == $artista['id']) ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($artista['nombre']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <small class="form-text text-muted" id="genero_musical_info"></small>
                                                        <?php else: ?>
                                                            <p class="form-control-static">No hay artistas disponibles.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3"></label>
                                                    <div class="col-md-9">
                                                        <a href="ingreso_artista.php" class="btn btn-info btn-sm text-white">
                                                            <i class="fa fa-plus"></i> Nuevo Artista
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Sección de Cliente -->
                                        <?php if (!$is_editing): ?>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Cliente: <span class="text-danger">*</span></label>
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
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3"></label>
                                                        <div class="col-md-9">
                                                            <a href="ingreso_cliente.php" class="btn btn-info btn-sm text-white">
                                                                <i class="fa fa-plus"></i> Nuevo Cliente
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <!-- Agregar este nuevo bloque para mostrar el nombre del cliente en modo edición -->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Cliente:</label>
                                                        <div class="col-md-9">
                                                            <p class="form-control-static">
                                                                <strong><?php echo htmlspecialchars($evento['nombres'] . ' ' . $evento['apellidos']); ?></strong>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="cliente_id" value="<?php echo htmlspecialchars($evento['cliente_id']); ?>">
                                        <?php endif; ?>


                                        <!-- Detalles del Evento -->
                                        <h3 class="box-title">Detalles del Evento</h3>
                                        <hr class="m-t-0 m-b-40">

                                        <!-- Nombre del Evento -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Nombre Evento <span class="text-danger">*</span></label>
                                                    <div class="col-md-9">
                                                        <input type="text" class="form-control" id="nombre_evento"
                                                            name="nombre_evento" maxlength="60" required
                                                            value="<?php echo $is_editing ? htmlspecialchars($evento['nombre_evento']) : ''; ?>">
                                                        <small class="help-block text-muted">Nombre descriptivo del evento</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Fecha <span class="text-danger">*</span></label>
                                                    <div class="col-md-9">
                                                        <input type="date" class="form-control" id="fecha_evento"
                                                            name="fecha_evento" required
                                                            value="<?php echo $is_editing ? htmlspecialchars($evento['fecha_evento']) : ''; ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Hora y Ciudad -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Hora</label>
                                                    <div class="col-md-9">
                                                        <input type="time" class="form-control" id="hora_evento"
                                                            name="hora_evento"
                                                            value="<?php echo $is_editing ? htmlspecialchars($evento['hora_evento']) : ''; ?>">
                                                        <small class="help-block text-muted">Hora del evento (opcional)</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Ciudad</label>
                                                    <div class="col-md-9">
                                                        <input type="text" class="form-control" id="ciudad_evento"
                                                            name="ciudad_evento" maxlength="100"
                                                            value="<?php echo $is_editing ? htmlspecialchars($evento['ciudad_evento']) : ''; ?>">
                                                        <small class="help-block text-muted">Ciudad del evento (opcional)</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Lugar y Valor -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Lugar</label>
                                                    <div class="col-md-9">
                                                        <input type="text" class="form-control" id="lugar_evento"
                                                            name="lugar_evento" maxlength="150"
                                                            value="<?php echo $is_editing ? htmlspecialchars($evento['lugar_evento']) : ''; ?>">
                                                        <small class="help-block text-muted">Lugar específico del evento (opcional)</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Valor <span class="text-danger">*</span></label>
                                                    <div class="col-md-9">
                                                        <input type="number" class="form-control" id="valor_evento"
                                                            name="valor_evento" min="1000000" max="100000000" required
                                                            value="<?php echo $is_editing ? htmlspecialchars($evento['valor_evento']) : ''; ?>">
                                                        <small class="help-block text-muted">Valor en pesos (entre 1.000.000 y 100.000.000)</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tipo de Evento y Encabezado -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Tipo de Evento <span class="text-danger">*</span></label>
                                                    <div class="col-md-9">
                                                        <select class="form-control" id="tipo_evento" name="tipo_evento" required>
                                                            <option value="">Seleccione tipo de evento</option>
                                                            <?php
                                                            $tipos_evento = ['Privado', 'Municipal', 'Matrimonio'];
                                                            foreach ($tipos_evento as $tipo):
                                                            ?>
                                                                <option value="<?php echo $tipo; ?>"
                                                                    <?php echo ($is_editing && $evento['tipo_evento'] == $tipo) ? 'selected' : ''; ?>>
                                                                    <?php echo $tipo; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <small class="help-block text-muted">Categoría del evento</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Encabezado</label>
                                                    <div class="col-md-9">
                                                        <input type="text" class="form-control" id="encabezado_evento"
                                                            name="encabezado_evento" maxlength="100"
                                                            value="<?php echo $is_editing ? htmlspecialchars($evento['encabezado_evento']) : ''; ?>">
                                                        <small class="help-block text-muted">Título para documentos</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Servicios Adicionales -->
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
                                                                        <input type="radio" name="<?php echo $key; ?>" value="Si"
                                                                            <?php echo ($is_editing && $evento[$key] == 'Si') ? 'checked' : ''; ?>> Sí
                                                                    </label>
                                                                    <label class="radio-inline">
                                                                        <input type="radio" name="<?php echo $key; ?>" value="No"
                                                                            <?php echo (!$is_editing || $evento[$key] != 'Si') ? 'checked' : ''; ?>> No
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
                                                        <i class="fa fa-check"></i> <?php echo $is_editing ? 'Actualizar Evento' : 'Crear Evento'; ?>
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
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <!-- Header del Modal -->
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white">
                        <i class="fa fa-check-circle"></i>
                        <?php echo $is_editing ? 'Confirmar Actualización de Evento' : 'Confirmar Creación de Evento'; ?>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Cuerpo del Modal -->
                <div class="modal-body p-4">
                    <div id="resumenEvento">
                        <h4 class="mb-3">Resumen del Evento</h4>
                        <hr class="mb-4">

                        <!-- Información Principal -->
                        <div class="row mb-4">
                            <!-- Columna Izquierda -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted">Gira:</label>
                                    <div id="resumen_gira" class="font-weight-bold"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted">Artista:</label>
                                    <div id="resumen_artista" class="font-weight-bold"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted">Cliente:</label>
                                    <div id="resumen_cliente" class="font-weight-bold"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted">Nombre Evento:</label>
                                    <div id="resumen_nombre_evento" class="font-weight-bold"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted">Tipo:</label>
                                    <div id="resumen_tipo" class="font-weight-bold"></div>
                                </div>
                            </div>

                            <!-- Columna Derecha -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted">Fecha y Hora:</label>
                                    <div id="resumen_fecha_hora" class="font-weight-bold"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted">Lugar:</label>
                                    <div id="resumen_lugar" class="font-weight-bold"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted">Valor:</label>
                                    <div id="resumen_valor" class="font-weight-bold text-success"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-muted">Servicios:</label>
                                    <div id="resumen_servicios" class="font-weight-bold"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje de Confirmación -->
                        <div class="alert alert-info text-center">
                            <i class="fa fa-question-circle"></i>
                            ¿Está seguro que desea crear este evento con la información mostrada?
                        </div>
                    </div>
                </div>

                <!-- Footer del Modal -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-success" id="confirmCreateEvent">
                        <i class="fa fa-check"></i> <?php echo $is_editing ? 'Actualizar' : 'Confirmar'; ?>
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
                if (!date) return 'Fecha no especificada';

                const options = {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                const formattedDate = new Date(date).toLocaleDateString('es-CL', options);
                return time ? `${formattedDate} a las ${time} hrs.` : formattedDate;
            }

            // Actualizar información del artista
            $('#artista_id').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const generoMusical = selectedOption.data('genero');
                $('#genero_musical_info').text(generoMusical ? `Género musical: ${generoMusical}` : '');
            });

            // Validación del formulario
            function validateForm() {
                let isValid = true;
                const errors = [];
                const isEditing = $('input[name="is_editing"]').val() === '1';

                // Modificar los campos requeridos según si estamos editando o no
                const requiredFields = {
                    'artista_id': 'Artista',
                    'nombre_evento': 'Nombre del Evento',
                    'fecha_evento': 'Fecha',
                    'valor_evento': 'Valor',
                    'tipo_evento': 'Tipo de Evento'
                };

                // Solo agregar la validación del cliente si NO estamos en modo edición
                if (!isEditing) {
                    requiredFields['cliente_id'] = 'Cliente';
                }

                // Limpiar validaciones previas
                $('.is-invalid').removeClass('is-invalid');
                $('.text-danger').text('');

                // Validar campos requeridos
                Object.entries(requiredFields).forEach(([fieldId, fieldName]) => {
                    const field = $(`#${fieldId}`);
                    const value = field.val();

                    if (!value || value.trim() === '') {
                        isValid = false;
                        errors.push(`El campo "${fieldName}" es requerido`);
                        field.addClass('is-invalid');
                    }
                });
                // Validar valor del evento
                const valorEvento = $('#valor_evento').val();
                if (valorEvento) {
                    const valor = parseInt(valorEvento);
                    if (valor < 1000000 || valor > 100000000) {
                        isValid = false;
                        errors.push('El valor del evento debe estar entre $1.000.000 y $100.000.000');
                        $('#valor_evento').addClass('is-invalid');
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
                        errors.push('La fecha del evento no puede ser anterior a hoy');
                        $('#fecha_evento').addClass('is-invalid');
                    }
                }

                if (!isValid) {
                    showErrorMessage(errors.join('<br>'));
                }

                return isValid;
            }

            // Actualizar resumen del evento
            function actualizarResumenEvento() {
                $('#resumen_gira').text($('#gira_id option:selected').text() || 'Sin gira');
                $('#resumen_artista').text($('#artista_id option:selected').text());
                $('#resumen_cliente').text($('#nombre_cliente').text());
                $('#resumen_nombre_evento').text($('#nombre_evento').val());

                // Fecha y hora (hora es opcional)
                const fecha = $('#fecha_evento').val();
                const hora = $('#hora_evento').val();
                $('#resumen_fecha_hora').text(formatDateTime(fecha, hora));

                // Lugar (ciudad y lugar son opcionales)
                const ciudad = $('#ciudad_evento').val();
                const lugar = $('#lugar_evento').val();
                let ubicacion = [];
                if (ciudad) ubicacion.push(ciudad);
                if (lugar) ubicacion.push(lugar);
                $('#resumen_lugar').text(ubicacion.length > 0 ? ubicacion.join(' - ') : 'No especificado');

                $('#resumen_valor').text(formatMoney($('#valor_evento').val()));
                $('#resumen_tipo').text($('#tipo_evento option:selected').text());

                // Servicios adicionales
                const servicios = [];
                ['hotel', 'traslados', 'viaticos'].forEach(function(servicio) {
                    if ($(`input[name="${servicio}"][value="Si"]`).is(':checked')) {
                        servicios.push(servicio.charAt(0).toUpperCase() + servicio.slice(1));
                    }
                });
                $('#resumen_servicios').text(servicios.length ? servicios.join(', ') : 'Ninguno');
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

            // Crear o actualizar evento
            function crearEvento() {
                var formData = new FormData(document.getElementById('eventoForm'));
                const isEditing = formData.get('is_editing') === '1';

                $.ajax({
                    url: isEditing ? 'functions/actualizar_evento.php' : 'functions/crear_evento.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    beforeSend: function() {
                        $('#confirmCreateEvent').prop('disabled', true)
                            .html('<i class="fa fa-spinner fa-spin"></i> Procesando...');
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#confirmationModal').modal('hide');

                            // Mostrar mensaje de éxito usando SweetAlert2
                            Swal.fire({
                                title: '¡Éxito!',
                                text: isEditing ? 'El evento ha sido actualizado correctamente.' : 'El evento ha sido creado exitosamente.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Redireccionar después de mostrar el mensaje
                                window.location.href = 'listar_agenda.php';
                            });
                        } else {
                            $('#confirmationModal').modal('hide');
                            // Mostrar mensaje de error
                            Swal.fire({
                                title: 'Error',
                                text: response.message || 'Error al procesar el evento',
                                icon: 'error'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error AJAX:', {
                            xhr,
                            status,
                            error
                        });
                        $('#confirmationModal').modal('hide');

                        let errorMessage = 'Error en la conexión';
                        try {
                            if (xhr.responseJSON) {
                                errorMessage = xhr.responseJSON.message || errorMessage;
                            }
                        } catch (e) {
                            console.error('Error al parsear respuesta:', e);
                            errorMessage = 'Error al procesar la solicitud';
                        }

                        // Mostrar mensaje de error usando SweetAlert2
                        Swal.fire({
                            title: 'Error',
                            text: errorMessage,
                            icon: 'error'
                        });
                    },
                    complete: function() {
                        isSubmitting = false;
                        $('#confirmCreateEvent').prop('disabled', false)
                            .html('<i class="fa fa-check"></i> Confirmar');
                    }
                });
            }
            // Mostrar mensaje de error
            function showErrorMessage(message) {
                $('#errorModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i>
                        ${message}
                    </div>
                `);
                $('#errorModal').modal('show');
            }

            // Formatear valor del evento
            $('#valor_evento').on('input', function() {
                let valor = $(this).val().replace(/\D/g, '');
                if (valor.length > 0) {
                    valor = parseInt(valor);
                    $(this).val(valor);
                }
            });

            // Inicializar fecha mínima
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