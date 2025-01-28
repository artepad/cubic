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

// Configuración de la paginación
$registrosPorPagina = 50;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;

// Obtener el total de registros de eventos activos
$sqlTotal = "SELECT COUNT(*) as total FROM eventos e 
             WHERE e.estado_evento IN ('Confirmado', 'En Producción')";
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
        WHERE e.estado_evento IN ('Confirmado', 'En Producción') 
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
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

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
            position: relative;
            flex-grow: 1;
            max-width: 300px;
            margin-left: 20px;
        }

        #searchInput {
            width: 100%;
            padding: 8px 35px 8px 12px;
            border: 1px solid #e4e7ea;
            border-radius: 4px;
            box-shadow: none;
            color: #565656;
            height: 38px;
            transition: all 300ms linear 0s;
        }

        #searchInput:focus {
            border-color: #7ace4c;
            box-shadow: 0 0 5px rgba(122, 206, 76, 0.3);
            outline: none;
        }

        .clear-search {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 5px;
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
                                    <input type="text" id="searchInput" placeholder="Buscar eventos activos...">
                                </div>
                            </div>
                            <?php if (!$result_eventos || $result_eventos->num_rows === 0): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> No se encontraron <b>eventos activos</b>.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
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
                                                    <td><?php echo generarEstadoEvento($evento['estado_evento']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Botón para generar PDF -->
                                <div class="text-right mt-3">
                                    <a href="generar_itinerario.php" class="btn btn-primary">
                                        <i class="fa fa-file-pdf-o"></i> Generar Itinerario PDF
                                    </a>
                                </div>

                                <!-- Paginación personalizada -->
                                <div class="custom-pagination">
                                    <?php
                                    $rango = 2; // Número de páginas a mostrar antes y después de la página actual

                                    // Mostrar primera página si estamos lejos de ella
                                    if ($paginaActual - $rango > 1) {
                                        echo "<a href='#' class='page-number' data-page='0'>1</a>";
                                        if ($paginaActual - $rango > 2) {
                                            echo "<span class='page-number'>...</span>";
                                        }
                                    }

                                    // Mostrar páginas alrededor de la página actual
                                    for ($i = max(1, $paginaActual - $rango); $i <= min($totalPaginas, $paginaActual + $rango); $i++) {
                                        if ($i == $paginaActual) {
                                            echo "<span class='page-number active'>$i</span>";
                                        } else {
                                            echo "<a href='#' class='page-number' data-page='" . ($i - 1) . "'>$i</a>";
                                        }
                                    }

                                    // Mostrar última página si estamos lejos de ella
                                    if ($paginaActual + $rango < $totalPaginas) {
                                        if ($paginaActual + $rango < $totalPaginas - 1) {
                                            echo "<span class='page-number'>...</span>";
                                        }
                                        echo "<a href='#' class='page-number' data-page='" . ($totalPaginas - 1) . "'>$totalPaginas</a>";
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
    <!-- Modal para cambiar estado -->
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
                                <option value="Finalizado">Finalizado</option>
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
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            const eventosTable = $('#eventosTable').DataTable({
                "dom": '<"top">rt<"bottom"><"clear">',
                "pageLength": 50,
                "ordering": true,
                "searching": true,
                "language": {
                    "zeroRecords": "No se encontraron resultados",
                    "info": "",
                    "infoEmpty": "",
                    "infoFiltered": ""
                },
                "columns": [{
                        "orderable": false
                    },
                    {
                        "orderable": true
                    },
                    {
                        "orderable": true
                    },
                    {
                        "orderable": true
                    },
                    {
                        "orderable": true
                    },
                    {
                        "orderable": true
                    },
                    {
                        "orderable": true
                    },
                    {
                        "orderable": true
                    }
                ],
                "drawCallback": function(settings) {
                    updateCustomPagination(this.api().page.info());
                }
            });

            // Implementar búsqueda en tiempo real
            $('#searchInput').on('keyup', function() {
                const searchText = $(this).val().toLowerCase();
                eventosTable.search(searchText).draw();
            });

            // Función para actualizar la paginación personalizada
            function updateCustomPagination(info) {
                let paginationHtml = '';
                const totalPages = info.pages;
                const currentPage = info.page + 1;
                const range = 2;

                if (currentPage - range > 1) {
                    paginationHtml += `<a href="#" class="page-number" data-page="0">1</a>`;
                    if (currentPage - range > 2) {
                        paginationHtml += `<span class="page-number">...</span>`;
                    }
                }

                for (let i = Math.max(0, currentPage - range - 1); i < Math.min(totalPages, currentPage + range); i++) {
                    if (i + 1 === currentPage) {
                        paginationHtml += `<span class="page-number active">${i + 1}</span>`;
                    } else {
                        paginationHtml += `<a href="#" class="page-number" data-page="${i}">${i + 1}</a>`;
                    }
                }

                if (currentPage + range < totalPages) {
                    if (currentPage + range < totalPages - 1) {
                        paginationHtml += `<span class="page-number">...</span>`;
                    }
                    paginationHtml += `<a href="#" class="page-number" data-page="${totalPages - 1}">${totalPages}</a>`;
                }

                $('.custom-pagination').html(paginationHtml);
            }

            // Manejar clics en la paginación personalizada
            $(document).on('click', '.custom-pagination .page-number', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page !== undefined) {
                    eventosTable.page(page).draw('page');
                }
            });

            // Función para limpiar el campo de búsqueda
            function clearSearch() {
                $('#searchInput').val('');
                eventosTable.search('').draw();
            }

            // Agregar botón de limpiar búsqueda si hay texto
            $('#searchInput').on('input', function() {
                const $this = $(this);
                const $clearButton = $('.clear-search');

                if ($this.val()) {
                    if (!$clearButton.length) {
                        $this.after('<button class="clear-search"><i class="fa fa-times"></i></button>');
                    }
                } else {
                    $clearButton.remove();
                }
            });

            // Manejar clic en botón de limpiar
            $(document).on('click', '.clear-search', function() {
                clearSearch();
                $(this).remove();
            });

            // Manejar cambio de estado
            $('.cambiar-estado').on('click', function() {
                const eventoId = $(this).data('id');
                $('#evento_id').val(eventoId);
                $('#nuevo_estado').val('Finalizado');
                $('#cambioEstadoModal').modal('show');
            });

            // Manejar guardar cambio de estado
            $('#guardarEstado').on('click', function() {
                const eventoId = $('#evento_id').val();
                const nuevoEstado = $('#nuevo_estado').val();
                const $boton = $(this);

                $boton.prop('disabled', true);

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
                            $('#cambioEstadoModal').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al cambiar el estado: ' + error
                        });
                    },
                    complete: function() {
                        $boton.prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>

</html>