<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Conectar a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "schaaf_producciones";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Configuración de paginación
$resultados_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $resultados_por_pagina;

// Procesar búsqueda
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where = '';
if (!empty($search)) {
    $where = "WHERE c.nombres LIKE '%$search%' OR c.apellidos LIKE '%$search%' OR c.rut LIKE '%$search%' OR e.nombre LIKE '%$search%'";
}

// Consulta para obtener el número total de registros
$sql_count = "SELECT COUNT(*) as total FROM clientes c LEFT JOIN empresas e ON c.id = e.cliente_id $where";
$result_count = $conn->query($sql_count);
$row_count = $result_count->fetch_assoc();
$total_registros = $row_count['total'];
$total_paginas = ceil($total_registros / $resultados_por_pagina);

// Consulta para obtener los clientes de la página actual
$sql = "SELECT c.*, e.nombre as nombre_empresa FROM clientes c 
        LEFT JOIN empresas e ON c.id = e.cliente_id 
        $where 
        LIMIT $offset, $resultados_por_pagina";
$result = $conn->query($sql);

// Consulta para obtener el número total de clientes
$sql_total_clientes = "SELECT COUNT(*) as total FROM clientes";
$result_total_clientes = $conn->query($sql_total_clientes);
$total_clientes = $result_total_clientes->fetch_assoc()['total'];


//*************** */ Consulta para obtener los eventos activos
// Configuración de paginación para eventos
$eventos_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $eventos_por_pagina;

// Consulta para obtener el número total de eventos activos
$sql_count_eventos = "SELECT COUNT(*) as total FROM eventos WHERE fecha_evento >= CURDATE()";
$result_count_eventos = $conn->query($sql_count_eventos);

if ($result_count_eventos === false) {
    die("Error en la consulta de conteo: " . $conn->error);
}

$row_count_eventos = $result_count_eventos->fetch_assoc();
$total_eventos = $row_count_eventos['total'];
$total_paginas = ceil($total_eventos / $eventos_por_pagina);

// Consulta para obtener los eventos activos de la página actual
$sql_eventos = "SELECT e.id, e.nombre_evento, e.fecha_evento, e.lugar_evento, c.nombres, c.apellidos, g.nombre as nombre_gira, e.estado_evento 
                FROM eventos e 
                LEFT JOIN clientes c ON e.cliente_id = c.id 
                LEFT JOIN giras g ON e.gira_id = g.id 
                WHERE e.fecha_evento >= CURDATE() 
                ORDER BY e.fecha_evento ASC 
                LIMIT ?, ?";

// Preparar la declaración
$stmt = $conn->prepare($sql_eventos);
if ($stmt === false) {
    die("Error en la preparación de la consulta: " . $conn->error);
}

// Vincular parámetros
$stmt->bind_param("ii", $offset, $eventos_por_pagina);

// Ejecutar la consulta
$stmt->execute();

// Obtener resultados
$result_eventos = $stmt->get_result();

if ($result_eventos === false) {
    die("Error al obtener resultados: " . $stmt->error);
}


// Agregar esta consulta al principio del archivo, junto con las otras consultas
$sql_count_eventos_activos = "SELECT COUNT(*) as total FROM eventos WHERE fecha_evento >= CURDATE()";
$result_count_eventos_activos = $conn->query($sql_count_eventos_activos);
$total_eventos_activos = $result_count_eventos_activos->fetch_assoc()['total'];

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
                <!-- row -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="white-box cal-event">
                            <h3 class="box-title">Drag and drop your event</h3>
                            <div class="m-t-20">
                                <div class="calendar-event" data-class="bg-primary">My Event One <a href="javascript:void(0);" class="remove-calendar-event"><i class="ti-close"></i></a></div>
                                <div class="calendar-event" data-class="bg-success">My Event Two <a href="javascript:void(0);" class="remove-calendar-event"><i class="ti-close"></i></a></div>
                                <div class="calendar-event" data-class="bg-warning">My Event Three <a href="javascript:void(0);" class="remove-calendar-event"><i class="ti-close"></i></a></div>
                                <div class="calendar-event" data-class="bg-custom">My Event Four <a href="javascript:void(0);" class="remove-calendar-event"><i class="ti-close"></i></a></div>
                                <input type="text" placeholder="Add Event and hit enter" class="form-control add-event m-t-20"> </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="white-box">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
                <!-- /.row -->
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
                                    <a href="javascript:void(0)"><img src="../plugins/images/users/1.jpg" alt="user-img" class="img-circle"> <span>Varun Dhavan <small class="text-success">online</small></span></a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)"><img src="../plugins/images/users/2.jpg" alt="user-img" class="img-circle"> <span>Genelia Deshmukh <small class="text-warning">Away</small></span></a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)"><img src="../plugins/images/users/3.jpg" alt="user-img" class="img-circle"> <span>Ritesh Deshmukh <small class="text-danger">Busy</small></span></a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)"><img src="../plugins/images/users/4.jpg" alt="user-img" class="img-circle"> <span>Arijit Sinh <small class="text-muted">Offline</small></span></a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)"><img src="../plugins/images/users/5.jpg" alt="user-img" class="img-circle"> <span>Govinda Star <small class="text-success">online</small></span></a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)"><img src="../plugins/images/users/6.jpg" alt="user-img" class="img-circle"> <span>John Abraham<small class="text-success">online</small></span></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- ===== Right-Sidebar-End ===== -->
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

     <!-- Calendar JavaScript -->
     <script src="assets/plugins/components/calendar/jquery-ui.min.js"></script>
    <script src="assets/plugins/components/moment/moment.js"></script>
    <script src='assets/plugins/components/calendar/dist/fullcalendar.min.js'></script>
    <script src="assets/plugins/components/calendar/dist/jquery.fullcalendar.js"></script>
    <!--Style Switcher -->
    <script src="assets/plugins/components/styleswitcher/jQuery.style.switcher.js"></script>
</body>
</html>