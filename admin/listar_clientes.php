<?php
// Iniciar sesión y configuración
session_start();
require_once 'config/config.php';
require_once 'functions/functions.php';

// Verificar autenticación
checkAuthentication();

// Obtener datos comunes
$totalClientes = getTotalClientes($conn);
$totalEventosActivos = getTotalEventosConfirmadosActivos($conn);
$totalEventosAnioActual = getTotalEventos($conn);
$totalArtistas = getTotalArtistas($conn);

// Configuración de la paginación
$registrosPorPagina = 50;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;

// Obtener el total de registros
$sqlTotal = "SELECT COUNT(*) as total FROM clientes";
$resultTotal = $conn->query($sqlTotal);
$fila = $resultTotal->fetch_assoc();
$totalRegistros = $fila['total'];

// Calcular el total de páginas
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Modificar la consulta para incluir LIMIT y OFFSET
$sql = "SELECT c.*, e.nombre as nombre_empresa 
        FROM clientes c 
        LEFT JOIN empresas e ON c.id = e.cliente_id 
        ORDER BY c.id DESC 
        LIMIT $registrosPorPagina OFFSET $offset";

$result_clientes = $conn->query($sql);

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();

// Definir el título de la página
$pageTitle = "Lista de Clientes";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <style>
        .titulo-busqueda {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .titulo-busqueda h3 {
            margin: 0;
        }

        .search-container {
            flex-grow: 1;
            max-width: 300px;
            margin-left: 20px;
        }

        #searchInput {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e4e7ea;
            border-radius: 3px;
            box-shadow: none;
            color: #565656;
            height: 38px;
            transition: all 300ms linear 0s;
        }

        #searchInput:focus {
            border-color: #7ace4c;
            box-shadow: none;
            outline: 0 none;
        }

        .custom-pagination {
            text-align: center;
            margin-top: 20px;
        }

        .custom-pagination .page-number {
            display: inline-block;
            padding: 5px 10px;
            margin: 0 5px;
            border: 1px solid #ddd;
            color: #333;
            text-decoration: none;
            border-radius: 3px;
        }

        .custom-pagination .page-number.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        .custom-pagination .page-number:hover:not(.active) {
            background-color: #f8f9fa;
        }

        @media (max-width: 767px) {
            .titulo-busqueda {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-container {
                margin-left: 0;
                margin-top: 10px;
                max-width: none;
            }
        }
    </style>
    <style>
        .alert {
            padding: 15px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-info {
            color: #31708f;
            background-color: #d9edf7;
            border-color: #bce8f1;
        }

        .alert i {
            margin-right: 8px;
        }
    </style>
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
                <?php
                // Mostrar mensaje de éxito si existe
                if (isset($_SESSION['mensaje'])) {
                    echo "<div class='alert alert-" . $_SESSION['mensaje_tipo'] . "'>" . $_SESSION['mensaje'] . "</div>";
                    unset($_SESSION['mensaje']);
                    unset($_SESSION['mensaje_tipo']);
                }
                ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="white-box">
                            <div class="titulo-busqueda">
                                <h3 class="box-title">Base de Datos de Clientes</h3>
                                <div class="search-container">
                                    <input type="text" id="searchInput" placeholder="Buscar cliente...">
                                </div>
                            </div>
                            <div class="table-responsive">
                                <?php if ($result_clientes && $result_clientes->num_rows > 0): ?>
                                    <table id="clientesTable" class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Acción</th>
                                                <th>Nombre Completo</th>
                                                <th>Correo</th>
                                                <th>Celular</th>
                                                <th>Empresa</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $result_clientes->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <a href='ver_cliente.php?id=<?php echo $row['id']; ?>' class='btn btn-info btn-sm' title='Ver Cliente'><i class='fa fa-eye'></i></a>
                                                        <a href='ingreso_cliente.php?id=<?php echo $row['id']; ?>' class='btn btn-warning btn-sm' title='Editar'><i class='fa fa-pencil'></i></a>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['correo']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['celular']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['nombre_empresa']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i> No se encontraron <b>clientes</b>.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <!-- Añadir paginación personalizada -->
                            <div class="custom-pagination">
                                <?php
                                $rango = 2; // Número de páginas a mostrar antes y después de la página actual

                                for ($i = max(1, $paginaActual - $rango); $i <= min($totalPaginas, $paginaActual + $rango); $i++) {
                                    if ($i == $paginaActual) {
                                        echo "<span class='page-number active'>$i</span>";
                                    } else {
                                        echo "<a href='?pagina=$i' class='page-number'>$i</a>";
                                    }
                                }
                                ?>
                            </div>
                            <div class="row m-t-20">
                                <div class="col-md-6 col-sm-6 col-xs-6">
                                    <a href="ingreso_cliente.php" class="btn btn-info btn-rounded">Nuevo Cliente</a>
                                </div>
                                <div class="col-md-6 col-sm-6 col-xs-6 text-right">
                                    <form action="exportar_clientes.php" method="POST" style="display: inline;">
                                        <?php echo getCSRFTokenField(); ?>
                                        <button type="submit" class="btn btn-success btn-rounded">
                                            <i class="fa fa-file-excel-o"></i> Exportar CSV
                                        </button>
                                    </form>
                                </div>
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


    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#clientesTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json",
                    "zeroRecords": "No se encontraron registros coincidentes",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": ""
                },
                "pageLength": <?php echo $registrosPorPagina; ?>,
                "ordering": true,
                "responsive": true,
                "dom": 'rt<"bottom"<"custom-pagination">><"clear">',
                "lengthChange": false,
                "info": false,
                "searching": true,
                "paging": false // Desactivamos la paginación de DataTables
            });

            // Implementamos nuestra propia función de búsqueda
            $('#searchInput').on('keyup', function() {
                table.search(this.value).draw();
            });

            // Aseguramos que DataTables use nuestro campo de búsqueda
            $('.dataTables_filter').hide();
            $('#searchInput').attr('type', 'search');
        });
    </script>
</body>

</html>