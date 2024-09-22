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

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el ID del cliente de la URL
$cliente_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($cliente_id > 0) {
    // Consulta para obtener los datos del cliente y su empresa
    $sql_cliente = "SELECT c.*, e.nombre as nombre_empresa, e.rut as rut_empresa, e.direccion as direccion_empresa
                    FROM clientes c 
                    LEFT JOIN empresas e ON c.id = e.cliente_id 
                    WHERE c.id = ?";

    $stmt = $conn->prepare($sql_cliente);
    if ($stmt === false) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("i", $cliente_id);
    if (!$stmt->execute()) {
        die("Error al ejecutar la consulta: " . $stmt->error);
    }

    $result_cliente = $stmt->get_result();

    if ($result_cliente->num_rows > 0) {
        $cliente = $result_cliente->fetch_assoc();

        // Consulta para obtener todos los eventos del cliente, ordenados por fecha descendente
        $sql_eventos = "SELECT id, nombre_evento, fecha_evento, hora_evento, lugar_evento, ciudad_evento, valor_evento, tipo_evento, hotel, traslados, viaticos
                        FROM eventos 
                        WHERE cliente_id = ? 
                        ORDER BY fecha_evento DESC";

        $stmt_eventos = $conn->prepare($sql_eventos);
        if ($stmt_eventos === false) {
            die("Error en la preparación de la consulta de eventos: " . $conn->error);
        }

        $stmt_eventos->bind_param("i", $cliente_id);
        if (!$stmt_eventos->execute()) {
            die("Error al ejecutar la consulta de eventos: " . $stmt_eventos->error);
        }

        $result_eventos = $stmt_eventos->get_result();

        $eventos = [];
        while ($row = $result_eventos->fetch_assoc()) {
            $eventos[] = $row;
        }
    } else {
        die("Cliente no encontrado");
    }
} else {
    die("ID de cliente no válido");
}

// Consulta para obtener el número total de clientes (para el menú lateral)
$sql_total_clientes = "SELECT COUNT(*) as total FROM clientes";
$result_total_clientes = $conn->query($sql_total_clientes);
if ($result_total_clientes === false) {
    die("Error al obtener el total de clientes: " . $conn->error);
}
$total_clientes = $result_total_clientes->fetch_assoc()['total'];

// Consulta para obtener el número total de eventos activos
$sql_count_eventos_activos = "SELECT COUNT(*) as total FROM eventos WHERE fecha_evento >= CURDATE()";
$result_count_eventos_activos = $conn->query($sql_count_eventos_activos);
if ($result_count_eventos_activos === false) {
    die("Error al obtener el total de eventos activos: " . $conn->error);
}
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
                            <a href="index.php">
                                <i class="icon-screen-desktop fa-fw"></i> 
                                <span class="hide-menu">Dashboard 
                                    <span class="label label-rounded label-info pull-right"><?php echo $total_eventos_activos; ?></span>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="waves-effect" data-toggle="collapse" data-target="#menuClientes">
                                <i class="icon-user fa-fw"></i> 
                                <span class="hide-menu">Clientes 
                                    <span class="label label-rounded label-info pull-right"><?php echo $total_clientes; ?></span>
                                </span>
                            </a>
                            <ul id="menuClientes" class="collapse">
                                <li><a href="listar_clientes.php">Listar</a></li>
                                <li><a href="ingreso-cliente.php">Ingresar</a></li>
                                <li><a href="modificar-cliente.php">Modificar</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="agenda.php">
                                <i class="icon-notebook fa-fw"></i> <span class="hide-menu">Agenda</span>
                            </a>
                        </li>
                        <li>
                            <a href="calendario.php">
                                <i class="icon-calender fa-fw"></i> <span class="hide-menu">Calendario</span>
                            </a>
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
                    <div class="col-md-12">
                        <div class="panel panel-info">
                            <div class="panel-heading">Generador de Eventos</div>
                            <div class="panel-wrapper collapse in" aria-expanded="true">
                                <div class="panel-body">
                                    <form id="eventoForm" class="form-horizontal" role="form" method="post" onsubmit="return false;">
                                        <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
                                        <input type="hidden" name="evento_id" id="evento_id" value="0">
                                        <div class="form-body">
                                            <h3 class="box-title">Cliente</h3>
                                            <hr class="m-t-0 m-b-40">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Nombre:</label>
                                                        <div class="col-md-9">
                                                            <p class="form-control-static">
                                                                <?php echo htmlspecialchars($cliente['nombres']); ?>
                                                                <?php echo htmlspecialchars($cliente['apellidos']); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Empresa:</label>
                                                        <div class="col-md-9">
                                                            <p class="form-control-static">
                                                            <?php echo htmlspecialchars($cliente['nombre_empresa'] ?? 'N/A'); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <h3 class="box-title">Detalles del Evento</h3>
                                            <hr class="m-t-0 m-b-40">
                                            <?php if (!empty($eventos)): ?>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label col-md-3">Eventos Pasados:</label>
                                                            <div class="col-md-9">
                                                                <select class="form-control" id="eventos_pasados">
                                                                    <option value="">Seleccione un evento pasado</option>
                                                                    <?php foreach ($eventos as $evento): ?>
                                                                        <option value="<?php echo htmlspecialchars($evento['id']); ?>"
                                                                            data-nombre="<?php echo htmlspecialchars($evento['nombre_evento']); ?>"
                                                                            data-fecha="<?php echo htmlspecialchars($evento['fecha_evento']); ?>"
                                                                            data-hora="<?php echo htmlspecialchars($evento['hora_evento']); ?>"
                                                                            data-lugar="<?php echo htmlspecialchars($evento['lugar_evento']); ?>"
                                                                            data-ciudad="<?php echo htmlspecialchars($evento['ciudad_evento'] ?? ''); ?>"
                                                                            data-valor="<?php echo htmlspecialchars($evento['valor_evento']); ?>"
                                                                            data-tipo="<?php echo htmlspecialchars($evento['tipo_evento']); ?>"
                                                                            data-hotel="<?php echo htmlspecialchars($evento['hotel'] ?? 'No'); ?>"
                                                                            data-traslados="<?php echo htmlspecialchars($evento['traslados'] ?? 'No'); ?>"
                                                                            data-viaticos="<?php echo htmlspecialchars($evento['viaticos'] ?? 'No'); ?>">
                                                                            <?php echo htmlspecialchars($evento['nombre_evento']) . ' - ' . htmlspecialchars($evento['fecha_evento']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Nombre Evento</label>
                                                        <div class="col-md-9">
                                                            <input type="text" class="form-control" id="nombre_evento" name="nombre_evento" required maxlength="60">
                                                            <span id="nombre_evento_error" class="text-danger"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Encabezado</label>
                                                        <div class="col-md-9">
                                                            <input type="text" class="form-control" id="encabezado_evento" name="encabezado_evento" maxlength="100">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Fecha</label>
                                                        <div class="col-md-9">
                                                            <input type="date" class="form-control" id="fecha_evento" name="fecha_evento" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Hora</label>
                                                        <div class="col-md-9">
                                                            <input type="time" class="form-control" id="hora_evento" name="hora_evento" required step="1800">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Ciudad</label>
                                                        <div class="col-md-9">
                                                            <input type="text" class="form-control" id="ciudad_evento" name="ciudad_evento" required maxlength="100">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Lugar</label>
                                                        <div class="col-md-9">
                                                            <input type="text" class="form-control" id="lugar" name="lugar" required maxlength="150">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                                                                        
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Valor</label>
                                                        <div class="col-md-9">
                                                            <input type="number" class="form-control" id="valor" name="valor" required min="1000000" max="100000000">
                                                            <span id="valor_error" class="text-danger"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Tipo de Evento</label>
                                                        <div class="col-md-9">
                                                            <input type="text" class="form-control" id="tipo_evento" name="tipo_evento" required maxlength="100">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Gira</label>
                                                        <div class="col-md-9">
                                                            <select class="form-control" id="gira_id" name="gira_id">
                                                                <option value="">Seleccione una gira</option>
                                                                <?php
                                                                // Obtener las últimas 4 giras
                                                                $sql_giras = "SELECT id, nombre FROM giras ORDER BY fecha_creacion DESC LIMIT 4";
                                                                $result_giras = $conn->query($sql_giras);
                                                                while ($gira = $result_giras->fetch_assoc()) {
                                                                    echo "<option value='" . $gira['id'] . "'>" . htmlspecialchars($gira['nombre']) . "</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3"></label>
                                                        <div class="col-md-9">
                                                            <a href="ingreso-giras.php" class="btn btn-info text-white">
                                                                <i class="fa fa-plus"></i> Nueva Gira
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label col-md-3">Estado:</label>
                                                            <div class="col-md-9">
                                                                <select class="form-control" id="estado_evento" name="estado_evento">
                                                                    <option value="Pendiente">Pendiente</option>
                                                                    <option value="Confirmado">Confirmado</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Hotel</label>
                                                        <div class="col-md-9">
                                                            <div class="radio-list">
                                                                <label class="radio-inline">
                                                                    <input type="radio" name="hotel" value="Si"> Sí
                                                                </label>
                                                                <label class="radio-inline">
                                                                    <input type="radio" name="hotel" value="No" checked> No
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Traslados</label>
                                                        <div class="col-md-9">
                                                            <div class="radio-list">
                                                                <label class="radio-inline">
                                                                    <input type="radio" name="traslados" value="Si"> Sí
                                                                </label>
                                                                <label class="radio-inline">
                                                                    <input type="radio" name="traslados" value="No" checked> No
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Viáticos</label>
                                                        <div class="col-md-9">
                                                            <div class="radio-list">
                                                                <label class="radio-inline">
                                                                    <input type="radio" name="viaticos" value="Si"> Sí
                                                                </label>
                                                                <label class="radio-inline">
                                                                    <input type="radio" name="viaticos" value="No" checked> No
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-actions">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="row">
                                                        <div class="col-md-offset-3 col-md-9">
                                                            <button type="button" id="crearEventoBtn" class="btn btn-primary text-white" onclick="crearEvento()">
                                                                <i class="fa fa-file-text"></i> Crear Evento
                                                            </button>
                                                            <button type="button" class="btn btn-default">Cancelar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6"></div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
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
    <!-- Aquí van los scripts adicionales -->

    <script>
        function crearEvento() {
        if (!validateForm()) {
            return;
        }

        var formData = new FormData(document.getElementById('eventoForm'));

        $.ajax({
            url: 'crear_evento.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    var result = JSON.parse(response);
                    if (result.success) {
                        alert('Evento creado con éxito');
                        // Opcionalmente, limpiar el formulario o redirigir
                        // window.location.href = 'lista_eventos.php';
                    } else {
                        alert('Error al crear el evento: ' + result.message);
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    alert('Error en la respuesta del servidor');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', status, error);
                alert('Error en la conexión: ' + error);
            }
        });
    }

    function validateForm() {
        var nombre = document.getElementById('nombre_evento').value;
        var fecha = document.getElementById('fecha_evento').value;
        var hora = document.getElementById('hora_evento').value;
        var valor = document.getElementById('valor').value;

        if (nombre.trim() === '' || fecha.trim() === '' || hora.trim() === '' || valor.trim() === '') {
            alert('Por favor, complete todos los campos obligatorios.');
            return false;
        }

        if (parseInt(valor) < 1000000 || parseInt(valor) > 100000000) {
            alert('El valor debe estar entre $1.000.000 y $100.000.000');
            return false;
        }

        return true;
    }
    </script>

    <script>
        $(document).ready(function() {
        // Toggle para el menú de Clientes
        $('#side-menu').on('click', 'a[data-toggle="collapse"]', function(e) {
            e.preventDefault();
            var $this = $(this);
            var $parent = $this.parent('li');
            var $target = $($this.data('target'));

            // Toggle la clase 'in' en el submenú
            $target.toggleClass('in');

            // Toggle la clase 'active' en el elemento padre
            $parent.toggleClass('active');

            // Si el submenú está abierto, cierra otros submenús abiertos
            if ($target.hasClass('in')) {
                $parent.siblings('li').children('ul.in').collapse('hide');
                $parent.siblings('li').removeClass('active');
            }
        });
    });
    </script>
    
    <script>
        // Clock pickers
        $('#single-input').clockpicker({
            placement: 'bottom',
            align: 'left',
            autoclose: true,
            'default': 'now'
        });
        $('.clockpicker').clockpicker({
            donetext: 'Done',
        }).find('input').change(function() {
            console.log(this.value);
        });
        $('#check-minutes').click(function(e) {
            // Have to stop propagation here
            e.stopPropagation();
            input.clockpicker('show').clockpicker('toggleView', 'minutes');
        });
        if (/mobile/i.test(navigator.userAgent)) {
            $('input').prop('readOnly', true);
        }
        // Colorpicker
        $(".colorpicker").asColorPicker();
        $(".complex-colorpicker").asColorPicker({
            mode: 'complex'
        });
        $(".gradient-colorpicker").asColorPicker({
            mode: 'gradient'
        });
        // Date Picker
        jQuery('.mydatepicker, #datepicker').datepicker();
        jQuery('#datepicker-autoclose').datepicker({
            autoclose: true,
            todayHighlight: true
        });
        jQuery('#date-range').datepicker({
            toggleActive: true
        });
        jQuery('#datepicker-inline').datepicker({
            todayHighlight: true
        });
        // Daterange picker
        $('.input-daterange-datepicker').daterangepicker({
            buttonClasses: ['btn', 'btn-sm'],
            applyClass: 'btn-danger',
            cancelClass: 'btn-inverse'
        });
        $('.input-daterange-timepicker').daterangepicker({
            timePicker: true,
            format: 'MM/DD/YYYY h:mm A',
            timePickerIncrement: 30,
            timePicker12Hour: true,
            timePickerSeconds: false,
            buttonClasses: ['btn', 'btn-sm'],
            applyClass: 'btn-danger',
            cancelClass: 'btn-inverse'
        });
        $('.input-limit-datepicker').daterangepicker({
            format: 'MM/DD/YYYY',
            minDate: '06/01/2015',
            maxDate: '06/30/2015',
            buttonClasses: ['btn', 'btn-sm'],
            applyClass: 'btn-danger',
            cancelClass: 'btn-inverse',
            dateLimit: {
                days: 6
            }
        });
    </script>
    <!-- ===== Style Switcher JS ===== -->
    <script src="assets/plugins/components/styleswitcher/jQuery.style.switcher.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var valorInput = document.getElementById('valor');
            var valorError = document.getElementById('valor_error');

            valorInput.addEventListener('input', function() {
                var valor = parseInt(this.value);
                if (valor < 1000000) {
                    valorError.textContent = "El valor mínimo es $1.000.000";
                } else if (valor > 100000000) {
                    valorError.textContent = "El valor máximo es $100.000.000";
                } else {
                    valorError.textContent = "";
                }
            });

            document.querySelector('form').addEventListener('submit', function(e) {
                var valor = parseInt(valorInput.value);
                if (valor < 1000000 || valor > 100000000) {
                    e.preventDefault();
                    valorError.textContent = "El valor debe estar entre $1.000.000 y $100.000.000";
                }
            });
        });
    </script>


    <script>
        document.getElementById('hora_evento').addEventListener('change', function(e) {
            var time = this.value;
            var [hours, minutes] = time.split(':');
            minutes = parseInt(minutes) < 30 ? '00' : '30';
            this.value = `${hours}:${minutes}`;
        });
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var form = document.getElementById('contratoForm');
            var nombreEventoInput = document.getElementById('nombre_evento');
            var nombreEventoError = document.getElementById('nombre_evento_error');

            form.addEventListener('submit', function(event) {
                if (nombreEventoInput.value.length > 60) {
                    event.preventDefault();
                    nombreEventoError.textContent = 'El nombre del evento no puede exceder los 60 caracteres.';
                } else {
                    nombreEventoError.textContent = '';
                }
            });

            nombreEventoInput.addEventListener('input', function() {
                if (this.value.length > 60) {
                    nombreEventoError.textContent = 'El nombre del evento no puede exceder los 60 caracteres.';
                } else {
                    nombreEventoError.textContent = '';
                }
            });
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            function formatNumber(n) {
                return n.replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

            function formatCurrency(input, blur) {
                var input_val = input.val();
                if (input_val === "") {
                    return;
                }
                var original_len = input_val.length;
                var caret_pos = input.prop("selectionStart");
                if (input_val.indexOf("$") === 0) {
                    input_val = input_val.substring(1);
                }
                input_val = formatNumber(input_val);
                input_val = "$" + input_val;
                input.val(input_val);
                var updated_len = input_val.length;
                caret_pos = updated_len - original_len + caret_pos;
                input[0].setSelectionRange(caret_pos, caret_pos);
            }

            $("#valor_formatted").on({
                keyup: function() {
                    formatCurrency($(this));
                },
                blur: function() {
                    formatCurrency($(this), "blur");
                    validateValor();
                }
            });

            function validateValor() {
                var valorFormatted = $("#valor_formatted").val();
                var valorNumerico = parseInt(valorFormatted.replace(/[$.]/g, ''));
                var errorElement = $("#valor_error");

                if (isNaN(valorNumerico) || valorNumerico < 1000000) {
                    errorElement.text("El valor mínimo es $1.000.000");
                    return false;
                } else if (valorNumerico > 100000000) {
                    errorElement.text("El valor máximo es $100.000.000");
                    return false;
                } else {
                    errorElement.text("");
                    return true;
                }
            }

            $("#contratoForm").submit(function(e) {
                var valorFormatted = $("#valor_formatted").val();
                var valorNumerico = valorFormatted.replace(/[$.]/g, '');
                $("#valor").val(valorNumerico);

                if (!validateValor()) {
                    e.preventDefault(); // Previene el envío del formulario si la validación falla
                }
            });
        });
    </script>

    <script>
     $(document).ready(function() {
    $('#eventos_pasados').change(function() {
        var selectedOption = $(this).find('option:selected');
        if (selectedOption.val() !== "") {
            $('#evento_id').val(selectedOption.val());
            $('#nombre_evento').val(selectedOption.data('nombre'));
            $('#fecha_evento').val(selectedOption.data('fecha'));
            $('#hora_evento').val(selectedOption.data('hora'));
            $('#lugar').val(selectedOption.data('lugar'));
            $('#ciudad_evento').val(selectedOption.data('ciudad'));
            $('#valor').val(selectedOption.data('valor'));
            $('#tipo_evento').val(selectedOption.data('tipo'));

            // Actualizar los radio buttons
            $('input[name="hotel"][value="' + selectedOption.data('hotel') + '"]').prop('checked', true);
            $('input[name="traslados"][value="' + selectedOption.data('traslados') + '"]').prop('checked', true);
            $('input[name="viaticos"][value="' + selectedOption.data('viaticos') + '"]').prop('checked', true);

            // Limpiar el encabezado ya que es un campo nuevo
            $('#encabezado_evento').val('');
        } else {
            // Limpiar los campos si no se selecciona ningún evento
            $('#evento_id').val('0');
            $('#nombre_evento').val('');
            $('#fecha_evento').val('');
            $('#hora_evento').val('');
            $('#lugar').val('');
            $('#ciudad_evento').val('');
            $('#valor').val('');
            $('#tipo_evento').val('');
            $('#encabezado_evento').val('');

            // Restablecer los radio buttons a "No"
            $('input[name="hotel"][value="No"]').prop('checked', true);
            $('input[name="traslados"][value="No"]').prop('checked', true);
            $('input[name="viaticos"][value="No"]').prop('checked', true);
        }
    });
});
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM fully loaded and parsed');
            var selectEventos = document.getElementById('eventos_pasados');
            console.log('Select element:', selectEventos);

            if (selectEventos) {
                selectEventos.addEventListener('change', function() {
                    console.log('Select changed');
                    var selectedOption = this.options[this.selectedIndex];
                    console.log('Selected option:', selectedOption);

                    if (selectedOption.value !== "") {
                        console.log('Filling form fields');
                        fillFormFields(selectedOption);
                    } else {
                        console.log('Clearing form fields');
                        clearFormFields();
                    }
                });
            } else {
                console.error('Select element not found');
            }

            function fillFormFields(option) {
                setInputValue('evento_id', option.value);
                setInputValue('nombre_evento', option.dataset.nombre);
                setInputValue('lugar', option.dataset.lugar);
                setInputValue('fecha_evento', option.dataset.fecha);
                setInputValue('hora_evento', option.dataset.hora);
                setInputValue('valor', option.dataset.valor);
                setInputValue('tipo_evento', option.dataset.tipo);

                setRadioValue('hotel', option.dataset.hotel);
                setRadioValue('traslados', option.dataset.traslados);
                setRadioValue('viaticos', option.dataset.viaticos);
            }

            function setInputValue(id, value) {
                var element = document.getElementById(id);
                if (element) {
                    element.value = value;
                    console.log(`Set ${id} to ${value}`);
                } else {
                    console.error(`Element with id ${id} not found`);
                }
            }

            function setRadioValue(name, value) {
                var radio = document.querySelector(`input[name="${name}"][value="${value}"]`);
                if (radio) {
                    radio.checked = true;
                    console.log(`Set ${name} radio to ${value}`);
                } else {
                    console.error(`Radio button ${name} with value ${value} not found`);
                }
            }

            function clearFormFields() {
                ['evento_id', 'nombre_evento', 'lugar', 'fecha_evento', 'hora_evento', 'valor', 'tipo_evento'].forEach(function(id) {
                    setInputValue(id, '');
                });
                ['hotel', 'traslados', 'viaticos'].forEach(function(name) {
                    setRadioValue(name, 'No');
                });
            }
        });
    </script>
</body>
</html>