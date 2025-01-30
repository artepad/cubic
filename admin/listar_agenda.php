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

$sqlGiras = "SELECT id, nombre FROM giras ORDER BY fecha_creacion DESC";
$resultGiras = $conn->query($sqlGiras);

// Configuración de la paginación
$registrosPorPagina = 50;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$busqueda = isset($_GET['search']) ? $_GET['search'] : '';
$offset = ($paginaActual - 1) * $registrosPorPagina;

// Construir la consulta base
$baseQuery = "
    SELECT e.*, c.nombres, c.apellidos, a.nombre as nombre_artista, g.nombre as nombre_gira 
    FROM eventos e
    LEFT JOIN clientes c ON e.cliente_id = c.id
    LEFT JOIN artistas a ON e.artista_id = a.id
    LEFT JOIN giras g ON e.gira_id = g.id
    WHERE 1=1
";

// Agregar condiciones de búsqueda si existe
if (!empty($busqueda)) {
    $busqueda = $conn->real_escape_string($busqueda);
    $baseQuery .= " AND (
        e.nombre_evento LIKE '%$busqueda%' OR 
        e.ciudad_evento LIKE '%$busqueda%' OR 
        c.nombres LIKE '%$busqueda%' OR 
        c.apellidos LIKE '%$busqueda%' OR
        e.estado_evento LIKE '%$busqueda%'
    )";
}

// Obtener total de registros para la paginación
$sqlTotal = str_replace('e.*, c.nombres, c.apellidos', 'COUNT(*) as total', $baseQuery);
$resultTotal = $conn->query($sqlTotal);
$fila = $resultTotal->fetch_assoc();
$totalRegistros = $fila['total'];
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Agregar límites a la consulta principal
$baseQuery .= " ORDER BY e.fecha_creacion DESC, e.fecha_evento DESC LIMIT $registrosPorPagina OFFSET $offset";
$result_eventos = $conn->query($baseQuery);

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();

// Definir el título de la página
$pageTitle = "Listar Agenda";
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

        @media (max-width: 767px) {
            .titulo-busqueda {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-container {
                margin-left: 0;
                margin-top: 10px;
                max-width: none;
            }
        }

        /* Estilos para el nuevo estado */
        .label-info {
            background-color: #5bc0de;
        }

        .label-info[href]:hover,
        .label-info[href]:focus {
            background-color: #31b0d5;
        }

        .filters-container {
            display: flex;
            gap: 15px;
            flex-grow: 1;
            max-width: 500px;
            margin-left: 20px;
        }

        .filter-select {
            min-width: 150px;
            padding: 8px 12px;
            border: 1px solid #e4e7ea;
            border-radius: 3px;
            box-shadow: none;
            color: #565656;
            height: 38px;
            transition: all 300ms linear 0s;
        }

        .filter-select:focus {
            border-color: #7ace4c;
            box-shadow: none;
            outline: 0 none;
        }

        @media (max-width: 767px) {
            .filters-container {
                margin-left: 0;
                margin-top: 10px;
                max-width: none;
                flex-direction: column;
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

        <!-- Page Content -->
        <div class="page-wrapper">
            <div class="container-fluid">
                <?php
                // Mostrar mensaje de éxito si existe
                if (isset($_SESSION['mensaje'])) {
                    echo "<div class='alert alert-" . $_SESSION['mensaje_tipo'] . "'>" . $_SESSION['mensaje'] . "</div>";
                    unset($_SESSION['mensaje']);
                    unset($_SESSION['mensaje_tipo']);
                }
                ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="white-box">
                            <div class="titulo-busqueda">
                                <h3 class="box-title">Agenda de Eventos</h3>
                                <div class="filters-container">
                                    <select class="filter-select" id="filterGira">
                                        <option value="">Todas las giras</option>
                                        <?php while ($gira = $resultGiras->fetch_assoc()): ?>
                                            <option value="<?php echo $gira['id']; ?>">
                                                <?php echo htmlspecialchars($gira['nombre']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="search-container">
                                        <input type="text" id="searchInput" placeholder="Buscar evento..." value="<?php echo htmlspecialchars($busqueda); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <?php if (!$result_eventos || $result_eventos->num_rows === 0): ?>
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i> No se encontraron <b>eventos</b>.
                                    </div>
                                <?php else: ?>
                                    <table id="eventosTable" class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Acciones</th>
                                                <th>Nombre del Evento</th>
                                                <th>Artista</th>
                                                <th>Ciudad</th>
                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th>Cliente</th>
                                                <th>Gira</th>
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
                                                    <td><?php echo htmlspecialchars($evento['ciudad_evento']); ?></td>
                                                    <td><?php echo $evento['fecha_evento'] ? date('d/m/Y', strtotime($evento['fecha_evento'])) : 'Por definir'; ?></td>
                                                    <td><?php echo $evento['hora_evento'] ? date('H:i', strtotime($evento['hora_evento'])) : 'Por definir'; ?></td>
                                                    <td><?php echo htmlspecialchars($evento['nombres'] . ' ' . $evento['apellidos']); ?></td>
                                                    <td data-gira-id="<?php echo $evento['gira_id'] ?? ''; ?>">
                                                        <?php echo htmlspecialchars($evento['nombre_gira'] ?? 'Sin gira'); ?>
                                                    </td>
                                                    <td><?php echo generarEstadoEvento($evento['estado_evento']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>

                                    <!-- Paginación -->
                                    <div class="custom-pagination">
                                        <?php
                                        $rango = 2;
                                        if ($paginaActual > $rango + 1) {
                                            echo "<a href='?pagina=1" . ($busqueda ? "&search=" . urlencode($busqueda) : "") . "' class='page-number'>1</a>";
                                            if ($paginaActual > $rango + 2) {
                                                echo "<span class='page-number'>...</span>";
                                            }
                                        }

                                        for ($i = max(1, $paginaActual - $rango); $i <= min($totalPaginas, $paginaActual + $rango); $i++) {
                                            if ($i == $paginaActual) {
                                                echo "<span class='page-number active'>$i</span>";
                                            } else {
                                                echo "<a href='?pagina=$i" . ($busqueda ? "&search=" . urlencode($busqueda) : "") . "' class='page-number'>$i</a>";
                                            }
                                        }

                                        if ($paginaActual < $totalPaginas - $rango) {
                                            if ($paginaActual < $totalPaginas - $rango - 1) {
                                                echo "<span class='page-number'>...</span>";
                                            }
                                            echo "<a href='?pagina=$totalPaginas" . ($busqueda ? "&search=" . urlencode($busqueda) : "") . "' class='page-number'>$totalPaginas</a>";
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
    <!-- Modalidad para cambio de estado -->
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
                                <option value="Propuesta">Propuesta</option>
                                <option value="Confirmado">Confirmado</option>
                                <option value="Finalizado">Finalizado</option>
                                <option value="Reagendado">Reagendado</option>
                                <option value="Solicitado">Solicitado</option>
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
            // Inicialización de DataTables
            var table = $('#eventosTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json",
                    "zeroRecords": "No se encontraron registros coincidentes",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": ""
                },
                "pageLength": 50, // Registros por página
                "ordering": true,
                "responsive": true,
                "dom": 'rt<"bottom"<"custom-pagination">><"clear">',
                "lengthChange": false,
                "info": false,
                "searching": true,
                "paging": false,
                "columnDefs": [{
                        "targets": 0,
                        "orderable": false
                    },
                    {
                        "targets": 2,
                        "type": "date-eu"
                    }
                ],
                "order": []
            });

            // Implementación del buscador personalizado
            $('#searchInput').on('keyup', function() {
                clearTimeout(window.searchTimeout);
                var searchValue = $(this).val();

                window.searchTimeout = setTimeout(function() {
                    if (table) {
                        table.search(searchValue).draw();
                    }
                }, 300);
            });

            // Ocultar el buscador predeterminado de DataTables
            $('.dataTables_filter').hide();
            $('#searchInput').attr('type', 'search');

            // Inicializar tooltips de Bootstrap
            $('[data-toggle="tooltip"]').tooltip();

            // Manejador para abrir el modal de cambio de estado
            $('.cambiar-estado').on('click', function() {
                var eventoId = $(this).data('id');
                var estadoActual = $(this).closest('tr').find('td:last').text().trim();
                $('#evento_id').val(eventoId);
                $('#nuevo_estado').val(estadoActual);
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

            // Función para actualizar parámetros en la URL
            function updateQueryStringParameter(uri, key, value) {
                var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
                var separator = uri.indexOf('?') !== -1 ? "&" : "?";

                if (uri.match(re)) {
                    return uri.replace(re, '$1' + key + "=" + encodeURIComponent(value) + '$2');
                } else {
                    return uri + separator + key + "=" + encodeURIComponent(value);
                }
            }

            // Manejador global de errores AJAX
            $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
                console.error("Error en la petición AJAX:", thrownError);
            });

            // Limpieza al cerrar la página
            $(window).on('unload', function() {
                if (table) {
                    table.destroy();
                }
                $('[data-toggle="tooltip"]').tooltip('dispose');
            });
            // Función para combinar los filtros de búsqueda y gira
            function aplicarFiltros() {
                var searchValue = $('#searchInput').val().toLowerCase();
                var giraValue = $('#filterGira').val();

                // Aplicar filtros a cada fila
                $('#eventosTable tbody tr').each(function() {
                    var $row = $(this);
                    var mostrar = true;

                    // Aplicar filtro de gira
                    if (giraValue) {
                        var giraId = $row.find('td:eq(7)').data('gira-id');
                        if (giraId != giraValue) {
                            mostrar = false;
                        }
                    }

                    // Aplicar filtro de búsqueda
                    if (searchValue && mostrar) {
                        mostrar = false;
                        $row.find('td').each(function() {
                            if ($(this).text().toLowerCase().indexOf(searchValue) > -1) {
                                mostrar = true;
                                return false; // Salir del bucle each
                            }
                        });
                    }

                    // Mostrar u ocultar la fila
                    $row.toggle(mostrar);
                });
            }


            // Manejadores de eventos para los filtros
            $('#filterGira').on('change', function() {
                aplicarFiltros();
            });

            // Modificar el manejador existente del searchInput
            $('#searchInput').off('keyup').on('keyup', function() {
                clearTimeout(window.searchTimeout);
                window.searchTimeout = setTimeout(function() {
                    aplicarFiltros();
                }, 300);
            });
        });
    </script>
</body>

</html>