<?php
// Iniciar sesión
session_start();

// Incluir el archivo de configuración
require_once 'config.php';

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

// Función para obtener los detalles del evento
function obtenerDetallesEvento($conn, $evento_id)
{
    $sql = "SELECT e.*, c.nombres, c.apellidos, c.correo, c.celular, 
                   em.nombre as nombre_empresa, g.nombre as nombre_gira
            FROM eventos e
            LEFT JOIN clientes c ON e.cliente_id = c.id
            LEFT JOIN empresas em ON c.id = em.cliente_id
            LEFT JOIN giras g ON e.gira_id = g.id
            WHERE e.id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("i", $evento_id);
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }

    $result = $stmt->get_result();
    return ($result->num_rows > 0) ? $result->fetch_assoc() : null;
}

// Procesar la solicitud
try {
    $evento_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($evento_id <= 0) {
        throw new Exception("ID de evento no válido");
    }

    $evento = obtenerDetallesEvento($conn, $evento_id);
    if (!$evento) {
        throw new Exception("Evento no encontrado");
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

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
                        <div class="panel panel-info">
                            <div class="panel-heading">Detalles del Evento</div>
                            <div class="panel-wrapper collapse in" aria-expanded="true">
                                <div class="panel-body">
                                    <form class="form-horizontal" role="form">
                                        <!-- Sección de información del cliente -->
                                        <h3 class="box-title">Cliente</h3>
                                        <hr class="m-t-0 m-b-40">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Nombre:</label>
                                                    <div class="col-md-9">
                                                        <p class="form-control-static"><strong><?php echo htmlspecialchars($evento['nombres'] . ' ' . $evento['apellidos']); ?></strong></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Empresa:</label>
                                                    <div class="col-md-9">
                                                        <p class="form-control-static"><strong><?php echo htmlspecialchars($evento['nombre_empresa'] ?? 'N/A'); ?></strong></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Correo:</label>
                                                    <div class="col-md-9">
                                                        <p class="form-control-static"><strong><?php echo htmlspecialchars($evento['correo']); ?></strong></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label col-md-3">Celular:</label>
                                                    <div class="col-md-9">
                                                        <p class="form-control-static"><strong><?php echo htmlspecialchars($evento['celular']); ?></strong></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Sección de detalles del evento -->
                                        <h3 class="box-title">Detalles del Evento</h3>
                                        <hr class="m-t-0 m-b-40">

                                        <?php
                                        $event_fields = [
                                            ['name' => 'nombre_evento', 'label' => 'Nombre Evento'],
                                            ['name' => 'encabezado_evento', 'label' => 'Encabezado'],
                                            ['name' => 'fecha_evento', 'label' => 'Fecha'],
                                            ['name' => 'hora_evento', 'label' => 'Hora'],
                                            ['name' => 'ciudad_evento', 'label' => 'Ciudad'],
                                            ['name' => 'lugar_evento', 'label' => 'Lugar'],
                                            ['name' => 'valor_evento', 'label' => 'Valor'],
                                            ['name' => 'tipo_evento', 'label' => 'Tipo de Evento'],
                                        ];

                                        foreach (array_chunk($event_fields, 2) as $row): ?>
                                            <div class="row">
                                                <?php foreach ($row as $field): ?>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label col-md-3"><?php echo $field['label']; ?>:</label>
                                                            <div class="col-md-9">
                                                                <p class="form-control-static">
                                                                    <strong>
                                                                        <?php
                                                                        $value = $evento[$field['name']] ?? 'N/A';
                                                                        if ($field['name'] === 'fecha_evento') {
                                                                            $value = date('d/m/Y', strtotime($value));
                                                                        } elseif ($field['name'] === 'hora_evento') {
                                                                            $value = date('H:i', strtotime($value));
                                                                        } elseif ($field['name'] === 'valor_evento') {
                                                                            $value = '$' . number_format($value, 0, ',', '.');
                                                                        }
                                                                        echo htmlspecialchars($value);
                                                                        ?>
                                                                    </strong>
                                                                </p>
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
                                                            <label class="control-label col-md-3"><?php echo ucfirst($option); ?>:</label>
                                                            <div class="col-md-9">
                                                                <p class="form-control-static"><strong><?php echo $evento[$option] ?? 'No'; ?></strong></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>

                                        <div class="form-actions">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <div class="btn-group dropup m-r-10">
                                                        <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info dropdown-toggle waves-effect waves-light" type="button">Documentos <span class="caret"></span></button>
                                                        <ul role="menu" class="dropdown-menu">
                                                            <li><a href="generar_cotizacion.php?id=<?php echo $evento_id; ?>">Cotización</a></li>
                                                            <li><a href="#" id="generar-contrato">Contrato</a></li>
                                                            <li class="divider"></li>
                                                            <li><a href="crear_contrato.php?id=<?php echo $evento_id; ?>">Adjuntar</a></li>
                                                        </ul>
                                                    </div>
                                                    <div class="btn-group dropup m-r-10">
                                                        <button aria-expanded="false" data-toggle="dropdown" class="btn btn-warning dropdown-toggle waves-effect waves-light" type="button">Opciones <span class="caret"></span></button>
                                                        <ul role="menu" class="dropdown-menu">
                                                            <li><a href="eventos.php?id=<?php echo $evento_id; ?>">Editar</a></li>
                                                            <li><a href="eliminar_evento.php?id=<?php echo $evento_id; ?>">Eliminar</a></li>
                                                            <li class="divider"></li>
                                                            <li><a href="agenda.php">Volver</a></li>
                                                        </ul>
                                                    </div>
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
            <!-- Footer -->
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#generar-contrato').on('click', function(e) {
                e.preventDefault();
                console.log('Botón de generar contrato clickeado');

                var eventoId = <?php echo json_encode($evento_id); ?>;
                console.log('ID del evento:', eventoId);

                $.ajax({
                    url: 'generar_contrato.php',
                    method: 'GET',
                    data: {
                        id: eventoId
                    },
                    xhrFields: {
                        responseType: 'blob'
                    },
                    success: function(response) {
                        console.log('Respuesta recibida del servidor');
                        var blob = new Blob([response], {
                            type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                        });
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = "Contrato_Evento_" + eventoId + ".docx";
                        link.click();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error al generar el contrato:', error);
                        alert('Hubo un error al generar el contrato. Por favor, inténtelo de nuevo.');
                    }
                });
            });
        });
    </script>
</body>

</html>