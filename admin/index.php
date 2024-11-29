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

// Configuración de la paginación
$registrosPorPagina = 8;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;

// Obtener el total de registros de eventos activos
$sqlTotal = "SELECT COUNT(*) as total FROM eventos e 
             WHERE e.estado_evento IN ('confirmado', 'en_proceso')";
$resultTotal = $conn->query($sqlTotal);
$fila = $resultTotal->fetch_assoc();
$totalRegistros = $fila['total'];

// Calcular el total de páginas
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Consulta principal con paginación
$sql = "SELECT e.*, c.nombres, c.apellidos, a.nombre as nombre_artista 
        FROM eventos e 
        LEFT JOIN clientes c ON e.cliente_id = c.id 
        LEFT JOIN artistas a ON e.artista_id = a.id
        WHERE e.estado_evento IN ('confirmado', 'en_proceso') 
        ORDER BY e.fecha_evento ASC 
        LIMIT $registrosPorPagina OFFSET $offset";

$result_eventos = $conn->query($sql);

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();

// Definir el título de la página
$pageTitle = "Lista de Eventos";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <style>
        .titulo-busqueda {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .titulo-busqueda h3 {
            margin: 0;
        }

        .search-container {
            flex-grow: 1;
            max-width: 300px;
            margin-left: 20px;
        }

        #searchInput {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e4e7ea;
            border-radius: 3px;
            box-shadow: none;
            color: #565656;
            height: 38px;
            transition: all 300ms linear 0s;
        }

        #searchInput:focus {
            border-color: #7ace4c;
            box-shadow: none;
            outline: 0 none;
        }

        .custom-pagination {
            text-align: center;
            margin-top: 20px;
        }

        .custom-pagination .page-number {
            display: inline-block;
            padding: 5px 10px;
            margin: 0 5px;
            border: 1px solid #ddd;
            color: #333;
            text-decoration: none;
            border-radius: 3px;
        }

        .custom-pagination .page-number.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        .custom-pagination .page-number:hover:not(.active) {
            background-color: #f8f9fa;
        }

        @media (max-width: 767px) {
            .titulo-busqueda {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-container {
                margin-left: 0;
                margin-top: 10px;
                max-width: none;
                width: 100%;
            }

            .table-responsive {
                overflow-x: auto;
            }
        }
    </style>
    <style>
        .alert {
            padding: 15px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-info {
            color: #31708f;
            background-color: #d9edf7;
            border-color: #bce8f1;
        }

        .alert i {
            margin-right: 8px;
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
                        <div class="white-box">
                            <div class="titulo-busqueda">
                                <h3 class="box-title">Eventos Activos</h3>
                                <div class="search-container">
                                    <input type="text" id="searchInput" placeholder="Buscar evento...">
                                </div>
                            </div>

                            <?php if (!$result_eventos || $result_eventos->num_rows === 0): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> No se encontraron eventos activos.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table id="eventosTable" class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Acciones</th>
                                                <th>Nombre del Evento</th>
                                                <th>Artista</th>
                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th>Cliente</th>
                                                <th>Ciudad</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($evento = $result_eventos->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <a href="ver_evento.php?id=<?php echo $evento['id']; ?>"
                                                            class="btn btn-sm btn-info"
                                                            data-toggle="tooltip"
                                                            title="Ver Evento">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <button type="button"
                                                            class="btn btn-sm btn-warning cambiar-estado"
                                                            data-id="<?php echo $evento['id']; ?>"
                                                            data-toggle="tooltip"
                                                            title="Cambiar Estado">
                                                            <i class="fa fa-exchange"></i>
                                                        </button>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($evento['nombre_evento']); ?></td>
                                                    <td><?php echo htmlspecialchars($evento['nombre_artista'] ?? 'No asignado'); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($evento['fecha_evento'])); ?></td>
                                                    <td><?php echo date('H:i', strtotime($evento['hora_evento'])); ?></td>
                                                    <td><?php echo htmlspecialchars($evento['nombres'] . ' ' . $evento['apellidos']); ?></td>
                                                    <td><?php echo htmlspecialchars($evento['ciudad_evento']); ?></td>
                                                    <td><?php echo generarEstadoEvento($evento['estado_evento']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Paginación personalizada -->
                                <div class="custom-pagination">
                                    <?php
                                    $rango = 2; // Número de páginas a mostrar antes y después de la página actual

                                    // Mostrar primera página si estamos lejos de ella
                                    if ($paginaActual - $rango > 1) {
                                        echo "<a href='?pagina=1' class='page-number'>1</a>";
                                        if ($paginaActual - $rango > 2) {
                                            echo "<span class='page-number'>...</span>";
                                        }
                                    }

                                    // Mostrar páginas alrededor de la página actual
                                    for ($i = max(1, $paginaActual - $rango); $i <= min($totalPaginas, $paginaActual + $rango); $i++) {
                                        if ($i == $paginaActual) {
                                            echo "<span class='page-number active'>$i</span>";
                                        } else {
                                            echo "<a href='?pagina=$i' class='page-number'>$i</a>";
                                        }
                                    }

                                    // Mostrar última página si estamos lejos de ella
                                    if ($paginaActual + $rango < $totalPaginas) {
                                        if ($paginaActual + $rango < $totalPaginas - 1) {
                                            echo "<span class='page-number'>...</span>";
                                        }
                                        echo "<a href='?pagina=$totalPaginas' class='page-number'>$totalPaginas</a>";
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <div class="modal fade" id="cambioEstadoModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cambiar Estado del Evento</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formCambioEstado">
                        <input type="hidden" id="evento_id">
                        <div class="form-group">
                            <label for="nuevo_estado">Nuevo Estado:</label>
                            <select class="form-control" id="nuevo_estado" name="nuevo_estado">
                                <option value="Finalizado" selected>Finalizado</option>
                                <option value="Reagendado">Reagendado</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="guardarEstado">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Manejador para abrir el modal de cambio de estado
            $('.cambiar-estado').on('click', function() {
                var eventoId = $(this).data('id');
                $('#evento_id').val(eventoId);
                // Por defecto seleccionamos "Finalizado"
                $('#nuevo_estado').val('Finalizado');
                $('#cambioEstadoModal').modal('show');
            });

            // Manejador para guardar el cambio de estado
            $('#guardarEstado').on('click', function() {
                var eventoId = $('#evento_id').val();
                var nuevoEstado = $('#nuevo_estado').val();
                var $boton = $(this);
                var $fila = $('button.cambiar-estado[data-id="' + eventoId + '"]').closest('tr');

                // Deshabilitar el botón durante el proceso
                $boton.prop('disabled', true);

                // Petición AJAX para actualizar el estado
                $.ajax({
                    url: 'cambiar_estado.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        evento_id: eventoId,
                        nuevo_estado: nuevoEstado
                    },
                    success: function(response) {
                        if (response.success) {
                            // Actualizar el estado en la tabla
                            $fila.find('td:last').html(response.data.nuevo_estado);
                            $('#cambioEstadoModal').modal('hide');

                            // Mostrar mensaje de éxito
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(function() {
                                // Recargar la página después de mostrar el mensaje
                                location.reload();
                            });
                        } else {
                            // Mostrar mensaje de error si la respuesta no fue exitosa
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Manejar errores de red o servidor
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al cambiar el estado: ' + error
                        });
                    },
                    complete: function() {
                        // Rehabilitar el botón al completar la petición
                        $boton.prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>

</html>