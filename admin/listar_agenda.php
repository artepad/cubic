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
$result_eventos = getAllEventos($conn);

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
                            <h3 class="box-title">Agenda de Eventos</h3>
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

    <!-- Modal para cambiar estado -->
    <div class="modal fade" id="cambiarEstadoModal" tabindex="-1" role="dialog" aria-labelledby="cambiarEstadoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cambiarEstadoModalLabel">Cambiar Estado del Evento</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="cambiarEstadoForm">
                    <div class="modal-body">
                        <input type="hidden" id="eventoId" name="eventoId">
                        <div class="form-group">
                            <label for="nuevoEstado">Nuevo Estado:</label>
                            <select class="form-control" id="nuevoEstado" name="nuevoEstado">
                                <option value="Propuesta">Propuesta</option>
                                <option value="Confirmado">Confirmado</option>
                                <option value="Documentación">Documentación</option>
                                <option value="En Producción">En Producción</option>
                                <option value="Finalizado">Finalizado</option>
                                <option value="Reagendado">Reagendado</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Toggle para el menú de Clientes
            $('#side-menu').on('click', 'a[data-toggle="collapse"]', function(e) {
                e.preventDefault();
                $($(this).data('target')).toggleClass('in');
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Manejador para el botón de cambiar estado
            $(".cambiar-estado").click(function() {
                var eventoId = $(this).data('id');
                $("#eventoId").val(eventoId);
                $("#cambiarEstadoModal").modal('show');
            });

            // Manejador para el envío del formulario
            $("#cambiarEstadoForm").submit(function(e) {
                e.preventDefault();
                var eventoId = $("#eventoId").val();
                var nuevoEstado = $("#nuevoEstado").val();

                $.ajax({
                    url: 'functions/cambiar_estado_evento.php',
                    type: 'POST',
                    data: {
                        eventoId: eventoId,
                        nuevoEstado: nuevoEstado
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Actualizar la interfaz de usuario
                            var $fila = $(".cambiar-estado[data-id='" + eventoId + "']").closest('tr');
                            $fila.find('td:last').html(generarEstadoEvento(nuevoEstado));
                            $("#cambiarEstadoModal").modal('hide');
                            // La alerta de éxito ha sido removida
                        } else {
                            alert('Error al actualizar el estado: ' + response.message);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Error AJAX:", textStatus, errorThrown);
                        alert('Error de conexión al actualizar el estado');
                    }
                });
            });

            // Función para generar el HTML del estado del evento
            function generarEstadoEvento(estado) {
                var estadosInfo = {
                    'Propuesta': {
                        class: 'warning',
                        icon: 'fa-clock-o'
                    },
                    'Confirmado': {
                        class: 'success',
                        icon: 'fa-check'
                    },
                    'Documentación': {
                        class: 'info',
                        icon: 'fa-file-text-o'
                    },
                    'En Producción': {
                        class: 'primary',
                        icon: 'fa-cogs'
                    },
                    'Finalizado': {
                        class: 'default',
                        icon: 'fa-flag-checkered'
                    },
                    'Reagendado': {
                        class: 'warning',
                        icon: 'fa-calendar'
                    },
                    'Cancelado': {
                        class: 'danger',
                        icon: 'fa-times'
                    }
                };

                var info = estadosInfo[estado] || {
                    class: 'default',
                    icon: 'fa-question'
                };
                return '<span class="label label-' + info.class + '"><i class="fa ' + info.icon + '"></i> ' + estado + '</span>';
            }
        });
    </script>

</body>

</html>