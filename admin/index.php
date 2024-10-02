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

// Consulta para obtener el número total de eventos activos
$sql_count_eventos = "SELECT COUNT(*) as total FROM eventos WHERE fecha_evento >= CURDATE()";
$result_count_eventos = $conn->query($sql_count_eventos);

if ($result_count_eventos === false) {
    die("Error en la consulta de conteo: " . $conn->error);
}

$row_count_eventos = $result_count_eventos->fetch_assoc();
$total_eventos = $row_count_eventos['total'];
$total_paginas = ceil($total_eventos / $resultados_por_pagina);

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
$stmt->bind_param("ii", $offset, $resultados_por_pagina);

// Ejecutar la consulta
$stmt->execute();

// Obtener resultados
$result_eventos = $stmt->get_result();

if ($result_eventos === false) {
    die("Error al obtener resultados: " . $stmt->error);
}

// Consulta para obtener el número total de eventos activos
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
                                <span class="hide-menu"> Clientes</span>
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
            <!-- ===== Page-Container ===== -->
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="white-box">
                            <div class="row">
                                <div class="col-sm-6">
                                    <h4 class="box-title">Eventos Activos</h4>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Acciones</th>
                                            <th>Nombre del Evento</th>
                                            <th>Fecha</th>
                                            <th>Lugar</th>
                                            <th>Cliente</th>
                                            <th>Gira</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($result_eventos->num_rows > 0) {
                                            while($row = $result_eventos->fetch_assoc()) {
                                                $fecha_formateada = date("d M Y", strtotime($row['fecha_evento']));
                                                echo "<tr>
                                                    <td>
                                                        <a href='editar_evento.php?id={$row['id']}' class='btn btn-warning btn-sm' title='Editar'><i class='fa fa-pencil'></i></a>
                                                        <a href='bajar_evento.php?id={$row['id']}' class='btn btn-danger btn-sm' title='Bajar Evento' onclick='return confirm(\"¿Está seguro de que desea dar de baja este evento?\")'><i class='fa fa-times'></i></a>
                                                        <form action='generar_cotizacion.php' method='post' style='display:inline;'>
                                                            <input type='hidden' name='evento_id' value='{$row['id']}'>
                                                            <button type='submit' class='btn btn-info btn-sm' title='Generar Cotización'><i class='fa fa-file-text-o'></i></button>
                                                        </form>
                                                    </td>
                                                    <td>" . htmlspecialchars($row['nombre_evento']) . "</td>
                                                    <td>" . htmlspecialchars($fecha_formateada) . "</td>
                                                    <td>" . htmlspecialchars($row['lugar_evento']) . "</td>
                                                    <td>" . htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) . "</td>
                                                    <td>" . htmlspecialchars($row['nombre_gira']) . "</td>
                                                    <td>" . htmlspecialchars($row['estado_evento']) . "</td>
                                                </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='7'>No se encontraron eventos activos.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Paginación -->
                            <ul class="pagination">
                                <?php
                                $rango = 2;

                                if ($pagina_actual > 1) {
                                    echo "<li><a href='?pagina=".($pagina_actual-1)."'>«</a></li>";
                                } else {
                                    echo "<li class='disabled'><span>«</span></li>";
                                }

                                for ($i = max(1, $pagina_actual - $rango); $i <= min($total_paginas, $pagina_actual + $rango); $i++) {
                                    if ($i == $pagina_actual) {
                                        echo "<li class='active'><span>$i</span></li>";
                                    } else {
                                        echo "<li><a href='?pagina=$i'>$i</a></li>";
                                    }
                                }

                                if ($pagina_actual < $total_paginas) {
                                    echo "<li><a href='?pagina=".($pagina_actual+1)."'>»</a></li>";
                                } else {
                                    echo "<li class='disabled'><span>»</span></li>";
                                }
                                ?>
                            </ul>
                        </div>

                        <?php
                        $stmt->close();
                        ?>
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
        });
    </script>
</body>
</html>