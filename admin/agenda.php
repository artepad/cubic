<?php
// Iniciar sesión
session_start();

// Incluir el archivo de configuración
require_once 'config.php';
require_once 'utilities.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Función para ejecutar consultas seguras
function executeQuery($conn, $sql, $params = [])
{
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        die("Error en la ejecución de la consulta: " . $stmt->error);
    }

    return $stmt->get_result();
}

// Consulta para obtener el número total de clientes
$sql_total_clientes = "SELECT COUNT(*) as total FROM clientes";
$result_total_clientes = executeQuery($conn, $sql_total_clientes);
$total_clientes = $result_total_clientes->fetch_assoc()['total'];

// Consulta para obtener el número total de eventos activos
$sql_count_eventos_activos = "SELECT COUNT(*) as total FROM eventos WHERE fecha_evento >= CURDATE()";
$result_count_eventos_activos = executeQuery($conn, $sql_count_eventos_activos);
$total_eventos_activos = $result_count_eventos_activos->fetch_assoc()['total'];

// Consulta para obtener todos los eventos ordenados del más reciente al más antiguo
$sql_eventos = "SELECT e.*, c.nombres, c.apellidos 
                FROM eventos e 
                LEFT JOIN clientes c ON e.cliente_id = c.id 
                ORDER BY e.fecha_creacion DESC";
$result_eventos = executeQuery($conn, $sql_eventos);

// Cerrar la conexión
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="keywords" content="">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/plugins/images/favicon.png">
    <title>Panel de Control - Schaaf Producciones</title>
    <!-- ===== Bootstrap CSS ===== -->
    <link href="assets/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- ===== Plugin CSS ===== -->
    <link href="assets/plugins/components/chartist-js/dist/chartist.min.css" rel="stylesheet">
    <link href="assets/plugins/components/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.css" rel="stylesheet">
    <link href='assets/plugins/components/fullcalendar/fullcalendar.css' rel='stylesheet'>
    <!-- ===== Animation CSS ===== -->
    <link href="assets/css/animate.css" rel="stylesheet">
    <!-- ===== Custom CSS ===== -->
    <link href="assets/css/style.css" rel="stylesheet">
    <!-- ===== Color CSS ===== -->
    <link href="assets/css/colors/default.css" id="theme" rel="stylesheet">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body class="mini-sidebar">
    <!-- ===== Main-Wrapper ===== -->
    <div id="wrapper">
        <div class="preloader">
            <div class="cssload-speeding-wheel"></div>
        </div>
        <!-- ===== Top-Navigation ===== -->
        <nav class="navbar navbar-default navbar-static-top m-b-0">
            <div class="navbar-header">
                <a class="navbar-toggle font-20 hidden-sm hidden-md hidden-lg " href="javascript:void(0)" data-toggle="collapse" data-target=".navbar-collapse">
                    <i class="fa fa-bars"></i>
                </a>
                <div class="top-left-part">
                    <a class="logo" href="index.php">
                        <b>
                            <img src="assets/plugins/images/logo.png" alt="home" />
                        </b>
                        <span>
                            <img src="assets/plugins/images/logo-text.png" alt="homepage" class="dark-logo" />
                        </span>
                    </a>
                </div>
                <ul class="nav navbar-top-links navbar-left hidden-xs">
                    <li>
                        <a href="javascript:void(0)" class="sidebartoggler font-20 waves-effect waves-light"><i class="icon-arrow-left-circle"></i></a>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- ===== Top-Navigation-End ===== -->
        <!-- ===== Left-Sidebar ===== -->
        <aside class="sidebar">
            <div class="scroll-sidebar">
                <div class="user-profile">
                    <div class="dropdown user-pro-body">
                        <div class="profile-image">
                            <img src="assets/plugins/images/users/logo.png" alt="user-img" class="img-circle">
                        </div>
                        <p class="profile-text m-t-15 font-16"><a href="javascript:void(0);"> Schaaf Producciones</a></p>
                    </div>
                </div>
                <nav class="sidebar-nav">
                    <ul id="side-menu">
                        <li>
                            <a class="waves-effect" href="index.php" aria-expanded="false">
                                <i class="icon-screen-desktop fa-fw"></i>
                                <span class="hide-menu"> Dashboard
                                    <span class="label label-rounded label-info pull-right"><?php echo $total_eventos_activos; ?></span>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a class="waves-effect" href="clientes.php" aria-expanded="false">
                                <i class="icon-user fa-fw"></i>
                                <span class="hide-menu"> Clientes
                                    <span class="label label-rounded label-success pull-right"><?php echo $total_clientes; ?></span>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a href="agenda.php" aria-expanded="false">
                                <i class="icon-notebook fa-fw"></i> <span class="hide-menu">Agenda</span>
                            </a>
                        </li>
                        <li>
                            <a href="calendario.php" aria-expanded="false">
                                <i class="icon-calender fa-fw"></i> <span class="hide-menu">Calendario</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <div class="p-30">
                    <span class="hide-menu">
                        <a href="eventos.php" target="_blank" class="btn btn-success">Nuevo Evento</a>
                        <a href="logout.php" target="_blank" class="btn btn-default m-t-15">Cerrar Sesión</a>
                    </span>
                </div>
            </div>
        </aside>
        <!-- ===== Left-Sidebar-End ===== -->
        <!-- ===== Page-Content ===== -->
        <div class="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="white-box p-0">
                            <div class="page-aside">
                                <div class="right-aside">
                                    <div class="right-page-header">
                                        <div class="pull-right">
                                            <input type="text" id="searchInput" placeholder="Buscar eventos" class="form-control">
                                        </div>
                                        <h3 class="box-title">Lista de Eventos</h3>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="scrollable">
                                        <div class="table-responsive">
                                            <table id="eventosTable" class="table m-t-30 table-hover contact-list">
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
                                                                <a href="ver_evento.php?id=<?php echo $evento['id']; ?>" class="btn btn-sm btn-icon btn-pure btn-outline" data-toggle="tooltip" data-original-title="Ver Evento">
                                                                    <i class="ti-search" aria-hidden="true"></i>
                                                                </a>
                                                                <button type="button" class="btn btn-sm btn-icon btn-pure btn-outline cambiar-estado" data-id="<?php echo $evento['id']; ?>" data-toggle="tooltip" data-original-title="Cambiar Estado">
                                                                    <i class="ti-exchange-vertical" aria-hidden="true"></i>
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
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="7">
                                                            <a href="eventos.php" class="btn btn-info btn-rounded">Nuevo Evento</a>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                        function getEstadoClass($estado)
                        {
                            switch ($estado) {
                                case 'Pendiente':
                                    return 'warning';
                                case 'Confirmado':
                                    return 'success';
                                case 'Cancelado':
                                    return 'danger';
                                default:
                                    return 'default';
                            }
                        }
                        ?>

                        <script>
                            $(document).ready(function() {
                                // Función de búsqueda
                                $("#searchInput").on("keyup", function() {
                                    var value = $(this).val().toLowerCase();
                                    $("#eventosTable tbody tr").filter(function() {
                                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                                    });
                                });

                                // Confirmación para eliminar evento
                                $(".delete-evento").click(function() {
                                    var id = $(this).data('id');
                                    if (confirm('¿Está seguro de que desea eliminar este evento?')) {
                                        // Aquí puedes agregar la lógica para eliminar el evento
                                        console.log('Eliminar evento con ID: ' + id);
                                    }
                                });
                            });
                        </script>
                    </div>
                </div>
                <!-- ===== Right-Sidebar ===== -->
                <div class="right-sidebar">
                    <div class="slimscrollright">
                        <div class="rpanel-title"> Service Panel <span><i class="icon-close right-side-toggler"></i></span> </div>
                        <div class="r-panel-body">
                            <ul class="hidden-xs">
                                <li><b>Layout Options</b></li>
                                <li>
                                    <div class="checkbox checkbox-danger">
                                        <input id="headcheck" type="checkbox" class="fxhdr">
                                        <label for="headcheck"> Fix Header </label>
                                    </div>
                                </li>
                                <li>
                                    <div class="checkbox checkbox-warning">
                                        <input id="sidecheck" type="checkbox" class="fxsdr">
                                        <label for="sidecheck"> Fix Sidebar </label>
                                    </div>
                                </li>
                            </ul>
                            <ul id="themecolors" class="m-t-20">
                                <li><b>With Light sidebar</b></li>
                                <li><a href="javascript:void(0)" data-theme="default" class="default-theme working">1</a></li>
                                <li><a href="javascript:void(0)" data-theme="green" class="green-theme">2</a></li>
                                <li><a href="javascript:void(0)" data-theme="yellow" class="yellow-theme">3</a></li>
                                <li><a href="javascript:void(0)" data-theme="red" class="red-theme">4</a></li>
                                <li><a href="javascript:void(0)" data-theme="purple" class="purple-theme">5</a></li>
                                <li><a href="javascript:void(0)" data-theme="black" class="black-theme">6</a></li>
                                <li class="db"><b>With Dark sidebar</b></li>
                                <li><a href="javascript:void(0)" data-theme="default-dark" class="default-dark-theme">7</a></li>
                                <li><a href="javascript:void(0)" data-theme="green-dark" class="green-dark-theme">8</a></li>
                                <li><a href="javascript:void(0)" data-theme="yellow-dark" class="yellow-dark-theme">9</a></li>
                                <li><a href="javascript:void(0)" data-theme="red-dark" class="red-dark-theme">10</a></li>
                                <li><a href="javascript:void(0)" data-theme="purple-dark" class="purple-dark-theme">11</a></li>
                                <li><a href="javascript:void(0)" data-theme="black-dark" class="black-dark-theme">12</a></li>
                            </ul>
                            <ul class="m-t-20 chatonline">
                                <li><b>Chat option</b></li>
                                <li>
                                    <a href="javascript:void(0)"><img src="assets/plugins/images/users/1.jpg" alt="user-img" class="img-circle"> <span>Varun Dhavan <small class="text-success">online</small></span></a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)"><img src="assets/plugins/images/users/2.jpg" alt="user-img" class="img-circle"> <span>Genelia Deshmukh <small class="text-warning">Away</small></span></a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)"><img src="assets/plugins/images/users/3.jpg" alt="user-img" class="img-circle"> <span>Ritesh Deshmukh <small class="text-danger">Busy</small></span></a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)"><img src="assets/plugins/images/users/4.jpg" alt="user-img" class="img-circle"> <span>Arijit Sinh <small class="text-muted">Offline</small></span></a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)"><img src="assets/plugins/images/users/5.jpg" alt="user-img" class="img-circle"> <span>Govinda Star <small class="text-success">online</small></span></a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)"><img src="assets/plugins/images/users/6.jpg" alt="user-img" class="img-circle"> <span>John Abraham<small class="text-success">online</small></span></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- ===== Right-Sidebar-End ===== -->
            </div>


            <!-- Modal -->
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

            <!-- ===== Page-Container-End ===== -->
            <footer class="footer t-a-c">
                © 2024 Schaaf Producciones
            </footer>
        </div>
        <!-- ===== Page-Content-End ===== -->
    </div>
    <!-- ===== Main-Wrapper-End ===== -->
    <!-- ==============================
        Required JS Files
    =============================== -->
    <!-- ===== jQuery ===== -->

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
                    url: 'cambiar_estado_evento.php',
                    type: 'POST',
                    data: {
                        eventoId: eventoId,
                        nuevoEstado: nuevoEstado
                    },
                    success: function(response) {
                        if (response.success) {
                            // Actualizar la interfaz de usuario
                            var $fila = $(".cambiar-estado[data-id='" + eventoId + "']").closest('tr');
                            $fila.find('.estado-evento').html(generarEstadoEvento(nuevoEstado));
                            $("#cambiarEstadoModal").modal('hide');
                            alert('Estado actualizado con éxito');
                        } else {
                            alert('Error al actualizar el estado: ' + response.message);
                        }
                    },
                    error: function() {
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

    <!-- ===== jQuery ===== -->
    <script src="assets/plugins/components/jquery/dist/jquery.min.js"></script>
    <!-- ===== Bootstrap JavaScript ===== -->
    <script src="assets/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- ===== Slimscroll JavaScript ===== -->
    <script src="assets/js/jquery.slimscroll.js"></script>
    <!-- ===== Wave Effects JavaScript ===== -->
    <script src="assets/js/waves.js"></script>
    <!-- ===== Menu Plugin JavaScript ===== -->
    <script src="assets/js/sidebarmenu.js"></script>
    <!-- ===== Custom JavaScript ===== -->
    <script src="assets/js/custom.js"></script>
    <!-- ===== Plugin JS ===== -->
    <script src="assets/plugins/components/chartist-js/dist/chartist.min.js"></script>
    <script src="assets/plugins/components/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.min.js"></script>
    <script src='assets/plugins/components/moment/moment.js'></script>
    <script src='assets/plugins/components/fullcalendar/fullcalendar.js'></script>
    <script src="assets/js/db2.js"></script>
    <!-- ===== Style Switcher JS ===== -->
    <script src="assets/plugins/components/styleswitcher/jQuery.style.switcher.js"></script>

    <script>
        $(document).ready(function() {
            // Toggle para el menú de Clientes
            $('#side-menu').on('click', 'a[data-toggle="collapse"]', function(e) {
                e.preventDefault();
                $($(this).data('target')).toggleClass('in');
            });

            // Función de búsqueda
            $("#searchInput").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#eventosTable tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // Confirmación para eliminar evento
            $(".delete-evento").click(function() {
                var id = $(this).data('id');
                if (confirm('¿Está seguro de que desea eliminar este evento?')) {
                    // Aquí puedes agregar la lógica para eliminar el evento
                    console.log('Eliminar evento con ID: ' + id);
                }
            });

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
                    url: 'cambiar_estado_evento.php',
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
                            alert('Estado actualizado con éxito');
                        } else {
                            alert('Error al actualizar el estado: ' + response.message);
                        }
                    },
                    error: function() {
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