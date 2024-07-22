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
    <link rel="icon" type="image/png" sizes="16x16" href="../plugins/images/favicon.png">
    <title>Panel de Control - Schaaf Producciones</title>
    <!-- ===== Bootstrap CSS ===== -->
    <link href="bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- ===== Plugin CSS ===== -->
    <link href="../plugins/components/chartist-js/dist/chartist.min.css" rel="stylesheet">
    <link href="../plugins/components/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.css" rel="stylesheet">
    <link href='../plugins/components/fullcalendar/fullcalendar.css' rel='stylesheet'>
    <!-- ===== Animation CSS ===== -->
    <link href="css/animate.css" rel="stylesheet">
    <!-- ===== Custom CSS ===== -->
    <link href="css/style.css" rel="stylesheet">
    <!-- ===== Color CSS ===== -->
    <link href="css/colors/default.css" id="theme" rel="stylesheet">
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
                            <img src="../plugins/images/logo.png" alt="home" />
                        </b>
                        <span>
                            <img src="../plugins/images/logo-text.png" alt="homepage" class="dark-logo" />
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
                            <img src="../plugins/images/users/logo.png" alt="user-img" class="img-circle">
                        </div>
                        <p class="profile-text m-t-15 font-16"><a href="javascript:void(0);"> Schaaf Producciones</a></p>
                    </div>
                </div>
                <nav class="sidebar-nav">
                    <ul id="side-menu">
                        <li>
                            <a class="active waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="icon-screen-desktop fa-fw"></i> <span class="hide-menu"> Clientes <span class="label label-rounded label-info pull-right"><?php echo $total_clientes; ?></span></span></a>
                            <ul aria-expanded="false" class="collapse">
                                <li> <a href="index.php">Listar Clientes</a> </li>
                                <li> <a href="ingreso-cliente.php">Ingresar Nuevo</a> </li>
                            </ul>
                        </li>
                     </ul>
                </nav>
                <div class="p-30">
                    <span class="hide-menu">
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
                                    <h4 class="box-title">Clientes</h4>
                                </div>
                                <div class="col-sm-6">
                                    <form action="" method="GET" class="form-inline pull-right">
                                        <div class="input-group">
                                            <input type="text" name="search" class="form-control" placeholder="Buscar clientes..." value="<?php echo htmlspecialchars($search); ?>">
                                            <span class="input-group-btn">
                                                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i></button>
                                            </span>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nombres</th>
                                        <th>Apellidos</th>
                                        <th>RUT</th>
                                        <th>Empresa</th>
                                        <th>Correo</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($result->num_rows > 0) {
                                        while($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                                <td>{$row['nombres']}</td>
                                                <td>{$row['apellidos']}</td>
                                                <td>{$row['rut']}</td>
                                                <td>{$row['nombre_empresa']}</td>
                                                <td>{$row['correo']}</td>
                                                <td>
                                                    <a href='contrato.php?id={$row['id']}' class='btn btn-primary btn-sm' title='Generar Contrato'><i class='fa fa-file-text-o'></i></a>
                                                    <a href='editar_cliente.php?id={$row['id']}' class='btn btn-info btn-sm' title='Editar'><i class='fa fa-pencil'></i></a>
                                                    <a href='eliminar_cli.php?id={$row['id']}' class='btn btn-danger btn-sm' title='Eliminar' onclick='return confirm(\"¿Está seguro de que desea eliminar este cliente?\")'><i class='fa fa-trash-o'></i></a>
                                                </td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='7'>No se encontraron clientes.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                                </table>
                            </div>
                            <!-- Paginación -->
                            <ul class="pagination">
                                <?php
                                $rango = 2; // Número de páginas a mostrar antes y después de la página actual

                                // Botón "Anterior"
                                if ($pagina_actual > 1) {
                                    echo "<li><a href='?pagina=".($pagina_actual-1)."&search=$search'>«</a></li>";
                                } else {
                                    echo "<li class='disabled'><span>«</span></li>";
                                }

                                // Páginas numeradas
                                for ($i = max(1, $pagina_actual - $rango); $i <= min($total_paginas, $pagina_actual + $rango); $i++) {
                                    if ($i == $pagina_actual) {
                                        echo "<li class='active'><span>$i</span></li>";
                                    } else {
                                        echo "<li><a href='?pagina=$i&search=$search'>$i</a></li>";
                                    }
                                }

                                // Botón "Siguiente"
                                if ($pagina_actual < $total_paginas) {
                                    echo "<li><a href='?pagina=".($pagina_actual+1)."&search=$search'>»</a></li>";
                                } else {
                                    echo "<li class='disabled'><span>»</span></li>";
                                }
                                ?>
                            </ul>
                        </div>
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
    <script src="../plugins/components/jquery/dist/jquery.min.js"></script>
    <!-- ===== Bootstrap JavaScript ===== -->
    <script src="bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- ===== Slimscroll JavaScript ===== -->
    <script src="js/jquery.slimscroll.js"></script>
    <!-- ===== Wave Effects JavaScript ===== -->
    <script src="js/waves.js"></script>
    <!-- ===== Menu Plugin JavaScript ===== -->
    <script src="js/sidebarmenu.js"></script>
    <!-- ===== Custom JavaScript ===== -->
    <script src="js/custom.js"></script>
    <!-- ===== Plugin JS ===== -->
    <script src="../plugins/components/chartist-js/dist/chartist.min.js"></script>
    <script src="../plugins/components/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.min.js"></script>
    <script src='../plugins/components/moment/moment.js'></script>
    <script src='../plugins/components/fullcalendar/fullcalendar.js'></script>
    <script src="js/db2.js"></script>
    <!-- ===== Style Switcher JS ===== -->
    <script src="../plugins/components/styleswitcher/jQuery.style.switcher.js"></script>
</body>

</html>