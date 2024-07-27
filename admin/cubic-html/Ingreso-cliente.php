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

// Consulta para obtener el número total de clientes
$sql_total_clientes = "SELECT COUNT(*) as total FROM clientes";
$result_total_clientes = $conn->query($sql_total_clientes);
$total_clientes = $result_total_clientes->fetch_assoc()['total'];

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Insertar cliente
        $sql_cliente = "INSERT INTO clientes (nombres, apellidos, rut, correo, celular, genero) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_cliente = $conn->prepare($sql_cliente);
        $stmt_cliente->bind_param("ssssss", $_POST['nombres'], $_POST['apellidos'], $_POST['rut'], $_POST['email'], $_POST['celular'], $_POST['genero']);
        $stmt_cliente->execute();
        $cliente_id = $conn->insert_id;

        // Insertar empresa si se proporcionaron datos
        if (!empty($_POST['nombre_empresa'])) {
            $sql_empresa = "INSERT INTO empresas (nombre, rut, direccion, cliente_id) VALUES (?, ?, ?, ?)";
            $stmt_empresa = $conn->prepare($sql_empresa);
            $stmt_empresa->bind_param("sssi", $_POST['nombre_empresa'], $_POST['rut_empresa'], $_POST['direccion_empresa'], $cliente_id);
            $stmt_empresa->execute();
        }

        // Confirmar transacción
        $conn->commit();
        $mensaje = "Cliente agregado con éxito.";
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        $mensaje = "Error al agregar el cliente: " . $e->getMessage();
    }
}
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
        <!-- Page Content -->
        <div class="page-wrapper">
            <div class="container-fluid">
                <?php
                if (isset($mensaje)) {
                    echo "<div class='alert alert-info'>$mensaje</div>";
                }
                ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="white-box">
                            <h3 class="box-title m-b-0">Ingresar Nuevo Cliente</h3>
                            <p class="text-muted m-b-30 font-13">Información del Cliente y Empresa</p>
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <h4>Información del Cliente</h4>
                                <div class="form-group">
                                    <label for="nombres">Nombres</label>
                                    <input type="text" class="form-control" id="nombres" name="nombres" required>
                                </div>
                                <div class="form-group">
                                    <label for="apellidos">Apellidos</label>
                                    <input type="text" class="form-control" id="apellidos" name="apellidos" required>
                                </div>
                                <div class="form-group">
                                    <label for="rut">RUT</label>
                                    <input type="text" class="form-control" id="rut" name="rut" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label for="celular">Celular</label>
                                    <input type="text" class="form-control" id="celular" name="celular" required>
                                </div>
                                <div class="form-group">
                                    <label for="genero">Género</label>
                                    <select class="form-control" id="genero" name="genero" required>
                                        <option value="">Seleccione un género</option>
                                        <option value="Masculino">Masculino</option>
                                        <option value="Femenino">Femenino</option>
                                    </select>
                                </div>

                                <h4>Información de la Empresa (Opcional)</h4>
                                <div class="form-group">
                                    <label for="nombre_empresa">Nombre de la Empresa</label>
                                    <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa">
                                </div>
                                <div class="form-group">
                                    <label for="rut_empresa">RUT de la Empresa</label>
                                    <input type="text" class="form-control" id="rut_empresa" name="rut_empresa">
                                </div>
                                <div class="form-group">
                                    <label for="direccion_empresa">Dirección de la Empresa</label>
                                    <input type="text" class="form-control" id="direccion_empresa" name="direccion_empresa">
                                </div>

                                <button type="submit" class="btn btn-success waves-effect waves-light m-r-10">Guardar</button>
                                <button type="reset" class="btn btn-inverse waves-effect waves-light">Cancelar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
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