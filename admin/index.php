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
$totalEventosAnioActual = getTotalEventosAnioActual($conn);

// Obtener eventos
$result_eventos = getEventosConfirmados($conn);

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();

// Definir el título de la página
$pageTitle = "Lista de Eventos";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
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

        .alert {
            padding: 15px;
            margin-bottom: 20px;
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
                                                        <a href="ver_evento.php?id=<?php echo $evento['id']; ?>" class="btn btn-sm btn-info" data-toggle="tooltip" title="Ver Evento">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-warning cambiar-estado" data-id="<?php echo $evento['id']; ?>" data-toggle="tooltip" title="Cambiar Estado">
                                                            <i class="fa fa-exchange"></i>
                                                        </button>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($evento['nombre_evento']); ?></td>
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
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Modal para cambiar estado -->
    <div class="modal fade" id="cambiarEstadoModal" tabindex="-1" role="dialog" aria-labelledby="cambiarEstadoModalLabel">
        <!-- ... (código del modal) ... -->
    </div>

    <script>
        $(document).ready(function() {
            // Inicializar DataTables con configuración en español
            var table = $('#eventosTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json",
                    "zeroRecords": "No se encontraron registros coincidentes",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": ""
                },
                "dom": 'rt<"bottom"<"custom-pagination">><"clear">',
                "lengthChange": false,
                "info": false,
                "searching": true,
                "paging": false
            });

            // Implementar búsqueda personalizada
            $('#searchInput').on('keyup', function() {
                table.search(this.value).draw();
            });

            // Ocultar la búsqueda predeterminada de DataTables
            $('.dataTables_filter').hide();

            // Manejador para el botón de cambiar estado
            $(".cambiar-estado").click(function() {
                var eventoId = $(this).data('id');
                $("#eventoId").val(eventoId);
                $("#cambiarEstadoModal").modal('show');
            });

            // Inicializar tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>

</html>