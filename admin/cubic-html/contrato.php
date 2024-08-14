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

    // Obtener el ID del cliente de la URL
    $cliente_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($cliente_id > 0) {
        // Consulta para obtener los datos del cliente y su empresa
        $sql_cliente = "SELECT c.*, e.nombre as nombre_empresa, e.rut as rut_empresa, e.direccion as direccion_empresa
                        FROM clientes c 
                        LEFT JOIN empresas e ON c.id = e.cliente_id 
                        WHERE c.id = ?";

        $stmt = $conn->prepare($sql_cliente);
        $stmt->bind_param("i", $cliente_id);
        $stmt->execute();
        $result_cliente = $stmt->get_result();

        if ($result_cliente->num_rows > 0) {
            $cliente = $result_cliente->fetch_assoc();

            // Consulta para obtener todos los eventos del cliente, ordenados por fecha descendente
            $sql_eventos = "SELECT id, nombre_evento, fecha_evento, hora_evento, lugar, valor, tipo_evento
                            FROM eventos 
                            WHERE cliente_id = ? 
                            ORDER BY fecha_evento DESC";

            $stmt_eventos = $conn->prepare($sql_eventos);
            $stmt_eventos->bind_param("i", $cliente_id);
            $stmt_eventos->execute();
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
                <a class="navbar-toggle font-20 hidden-sm hidden-md hidden-lg " href="javascript:void(0)"
                    data-toggle="collapse" data-target=".navbar-collapse">
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
                        <a href="javascript:void(0)" class="sidebartoggler font-20 waves-effect waves-light"><i
                                class="icon-arrow-left-circle"></i></a>
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
                        <p class="profile-text m-t-15 font-16"><a href="javascript:void(0);"> Schaaf Producciones</a>
                        </p>
                    </div>
                </div>
                <nav class="sidebar-nav">
                    <ul id="side-menu">
                        <li>
                            <a class="active waves-effect" href="javascript:void(0);" aria-expanded="false"><i
                                    class="icon-screen-desktop fa-fw"></i> <span class="hide-menu"> Clientes <span
                                        class="label label-rounded label-info pull-right">
                                        <?php
                                        if (isset($total_clientes)) {
                                            echo htmlspecialchars($total_clientes);
                                        } else {
                                            echo '0'; // O cualquier otro valor predeterminado
                                        }
                                        ?>
                                    </span></span></a>
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
                    <div class="col-md-12">
                        <div class="panel panel-info">
                            <div class="panel-heading">Generador de Contrato</div>
                            <div class="panel-wrapper collapse in" aria-expanded="true">
                                <div class="panel-body">
                                    <form class="form-horizontal" role="form" method="post"
                                        action="generar_contrato.php">
                                        <!-- Añade este campo oculto justo después del campo cliente_id -->
                                        <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
                                        <input type="hidden" name="evento_id" id="evento_id" value="0">
                                        <div class="form-body">
                                            <h3 class="box-title">Cliente</h3>
                                            <hr class="m-t-0 m-b-40">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Nombres:</label>
                                                        <div class="col-md-9">
                                                            <p class="form-control-static">
                                                                <?php echo htmlspecialchars($cliente['nombres']); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Apellidos:</label>
                                                        <div class="col-md-9">
                                                            <p class="form-control-static">
                                                                <?php echo htmlspecialchars($cliente['apellidos']); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">RUT:</label>
                                                        <div class="col-md-9">
                                                            <p class="form-control-static">
                                                                <?php echo htmlspecialchars($cliente['rut']); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Correo:</label>
                                                        <div class="col-md-9">
                                                            <p class="form-control-static">
                                                                <?php echo htmlspecialchars($cliente['correo']); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Celular:</label>
                                                        <div class="col-md-9">
                                                            <p class="form-control-static">
                                                                <?php echo htmlspecialchars($cliente['celular']); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <h3 class="box-title">Empresa</h3>
                                            <hr class="m-t-0 m-b-40">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Nombre:</label>
                                                        <div class="col-md-9">
                                                            <p class="form-control-static">
                                                                <?php echo htmlspecialchars($cliente['nombre_empresa'] ?? 'N/A'); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">RUT:</label>
                                                        <div class="col-md-9">
                                                            <p class="form-control-static">
                                                                <?php echo htmlspecialchars($cliente['rut_empresa'] ?? 'N/A'); ?>
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
                                                                        <option value="<?php echo $evento['id']; ?>"
                                                                            data-nombre="<?php echo htmlspecialchars($evento['nombre_evento']); ?>"
                                                                            data-fecha="<?php echo $evento['fecha_evento']; ?>"
                                                                            data-hora="<?php echo $evento['hora_evento']; ?>"
                                                                            data-lugar="<?php echo htmlspecialchars($evento['lugar']); ?>"
                                                                            data-valor="<?php echo $evento['valor']; ?>"
                                                                            data-tipo="<?php echo htmlspecialchars($evento['tipo_evento']); ?>">
                                                                            <?php echo htmlspecialchars($evento['nombre_evento']) . ' - ' . $evento['fecha_evento']; ?>
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
                                                        <label class="control-label col-md-3">Dirección</label>
                                                        <div class="col-md-9">
                                                            <input type="text" class="form-control" id="lugar"
                                                                name="lugar" required>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Fecha</label>
                                                        <div class="col-md-9">
                                                            <input type="date" class="form-control" id="fecha_evento"
                                                                name="fecha_evento" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Hora</label>
                                                        <div class="col-md-9">
                                                            <div class="input-group clockpicker">
                                                                <input type="text" class="form-control" id="hora_evento"
                                                                    name="hora_evento" required>
                                                                <span class="input-group-addon">
                                                                    <span class="glyphicon glyphicon-time"></span>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Valor</label>
                                                        <div class="col-md-9">
                                                            <input type="text" class="form-control" id="valor_formatted" name="valor_formatted" required>
                                                            <input type="hidden" id="valor" name="valor">
                                                            <span id="valor_error" class="text-danger"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Tipo de Evento</label>
                                                        <div class="col-md-9">
                                                            <input type="text" class="form-control" id="tipo_evento"
                                                                name="tipo_evento" required>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">IVA</label>
                                                        <div class="col-md-9">
                                                            <div class="radio-list">
                                                                <label class="radio-inline">
                                                                    <input type="radio" name="iva" value="1"> Sí
                                                                </label>
                                                                <label class="radio-inline">
                                                                    <input type="radio" name="iva" value="0" checked> No
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Hotel</label>
                                                        <div class="col-md-9">
                                                            <select class="form-control" id="hotel" name="hotel" required>
                                                                <option value="Si">Sí</option>
                                                                <option value="No">No</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Traslados</label>
                                                        <div class="col-md-9">
                                                            <select class="form-control" id="traslados" name="traslados" required>
                                                                <option value="Si">Sí</option>
                                                                <option value="No">No</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label col-md-3">Viáticos</label>
                                                        <div class="col-md-9">
                                                            <select class="form-control" id="viaticos" name="viaticos" required>
                                                                <option value="Si">Sí</option>
                                                                <option value="No">No</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-actions">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="row">
                                                            <div class="col-md-offset-3 col-md-9">
                                                                <button type="submit" class="btn btn-success" onclick="if(document.getElementById('evento_id').value === '') document.getElementById('evento_id').value = '0';">Crear Contrato</button>
                                                                <button type="button"
                                                                    class="btn btn-default">Cancelar</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6"></div>
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
    <!-- ===== Plugin CSS ===== -->
    <link href="../plugins/components/clockpicker/dist/jquery-clockpicker.min.css" rel="stylesheet">
    <link href="../plugins/components/jquery-asColorPicker-master/css/asColorPicker.css" rel="stylesheet">
    <link href="../plugins/components/bootstrap-datepicker/bootstrap-datepicker.min.css" rel="stylesheet"
        type="text/css" />
    <link href="../plugins/components/timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
    <link href="../plugins/components/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <!-- ===== Plugin JS ===== -->
    <script src="../plugins/components/chartist-js/dist/chartist.min.js"></script>
    <script src="../plugins/components/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.min.js"></script>
    <script src='../plugins/components/moment/moment.js'></script>
    <script src='../plugins/components/fullcalendar/fullcalendar.js'></script>
    <script src="js/db2.js"></script>
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
    <script src="../plugins/components/styleswitcher/jQuery.style.switcher.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var selectEventos = document.getElementById('eventos_pasados');
            if (selectEventos) {
                selectEventos.addEventListener('change', function() {
                    var selectedOption = this.options[this.selectedIndex];
                    if (selectedOption.value !== "") {
                        document.getElementById('evento_id').value = selectedOption.value; // Añade esta línea
                        document.getElementById('nombre_evento').value = selectedOption.dataset.nombre;
                        document.getElementById('lugar').value = selectedOption.dataset.lugar;
                        document.getElementById('fecha_evento').value = selectedOption.dataset.fecha;
                        document.getElementById('hora_evento').value = selectedOption.dataset.hora;
                        document.getElementById('valor').value = selectedOption.dataset.valor;
                        document.getElementById('tipo_evento').value = selectedOption.dataset.tipo;
                    } else {
                        // Limpiar los campos si se selecciona la opción por defecto
                        ['evento_id', 'nombre_evento', 'lugar', 'fecha_evento', 'hora_evento', 'valor', 'tipo_evento'].forEach(function(id) {
                            document.getElementById(id).value = '';
                        });
                    }
                });
            }
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
</body>

</html>