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

// Obtener datos de eventos
$result_eventos = getEventos($conn);

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();

// Definir el título de la página
$pageTitle = "Listar Agenda";
$contentFile = __FILE__;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .table-responsive {
            overflow-x: auto;
        }

        #eventosTable {
            width: 100%;
            white-space: nowrap;
        }

        #eventosTable th,
        #eventosTable td {
            padding: 10px;
        }
    </style>
</head>

<body class="mini-sidebar">
    <div id="wrapper">
        <?php include 'includes/nav.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div class="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="white-box">
                            <h3 class="box-title">Lista de Eventos</h3>
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
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#eventosTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
                }
            });

            $(".cambiar-estado").click(function() {
                var eventoId = $(this).data('id');
                // Aquí iría la lógica para abrir un modal o redireccionar a una página de cambio de estado
                console.log("Cambiar estado del evento: " + eventoId);
            });
        });
    </script>
</body>

</html>