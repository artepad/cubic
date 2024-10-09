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

// Obtener clientes
$result_clientes = getClientes($conn);

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();

// Definir el título de la página
$pageTitle = "Lista de Clientes";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
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
                <div class="row">
                    <div class="col-md-12">
                        <div class="white-box">
                            <h3 class="box-title">Lista de Clientes</h3>
                            <div class="table-responsive">
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
                                        <?php
                                        if ($result_clientes && $result_clientes->num_rows > 0) {
                                            while ($row = $result_clientes->fetch_assoc()) {
                                                echo "<tr>
                                                    <td>
                                                        <a href='ver_cliente.php?id=" . $row['id'] . "' class='btn btn-info btn-sm' title='Ver Cliente'><i class='fa fa-eye'></i></a>
                                                        <a href='editar_cliente.php?id=" . $row['id'] . "' class='btn btn-warning btn-sm' title='Editar'><i class='fa fa-pencil'></i></a>
                                                    </td>
                                                    <td>" . htmlspecialchars($row['nombres'] . ' ' . $row['apellidos']) . "</td>
                                                    <td>" . htmlspecialchars($row['correo']) . "</td>
                                                    <td>" . htmlspecialchars($row['celular']) . "</td>
                                                    <td>" . htmlspecialchars($row['nombre_empresa']) . "</td>
                                                </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='5'>No se encontraron clientes.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="row m-t-20">
                                <div class="col-md-6 col-sm-6 col-xs-6 text-left">
                                    <a href="Ingreso-cliente.php" class="btn btn btn-info btn-rounded">Nuevo Cliente</a>
                                </div>
                                <div class="col-md-6 col-sm-6 col-xs-6 text-right">
                                    <!-- Espacio reservado para la futura paginación -->
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

    <?php include 'includes/scripts.php'; ?>

    <script>
        $(document).ready(function() {
            // Inicializar DataTables
            $('#clientesTable').DataTable();
        });
    </script>
</body>

</html>