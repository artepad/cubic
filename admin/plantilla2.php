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

// Obtener eventos
$result_eventos = getEventos($conn);

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();

// Definir el título de la página
$pageTitle = "Lista de Eventos";

// Definir el título de la página y contenido específico
// Estos valores deberían ser establecidos antes de incluir este archivo
// $pageTitle = "Título de la Página";
// $contentFile = "ruta/al/contenido/especifico.php";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
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
        <!-- Page-Content-End -->
    </div>
    <!-- ===== Main-Wrapper-End ===== -->

    <!-- Modal para cambiar estado -->
    <div class="modal fade" id="cambiarEstadoModal" tabindex="-1" role="dialog" aria-labelledby="cambiarEstadoModalLabel">
        <!-- ... (código del modal) ... -->
    </div>

    
    <script>
        $(document).ready(function() {
            // Inicializar DataTables
            $('#eventosTable').DataTable();

            // Manejador para el botón de cambiar estado
            $(".cambiar-estado").click(function() {
                var eventoId = $(this).data('id');
                $("#eventoId").val(eventoId);
                $("#cambiarEstadoModal").modal('show');
            });

            // Manejador para el envío del formulario de cambio de estado
            $("#cambiarEstadoForm").submit(function(e) {
                e.preventDefault();
                // ... (código para manejar el cambio de estado) ...
            });
        });
    </script>
</body>

</html>