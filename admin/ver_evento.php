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


// Obtener detalles del evento si se proporciona un ID
$evento = [];
$evento_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($evento_id > 0) {
    $evento = obtenerDetallesEvento($conn, $evento_id);
}

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();

// Definir el título de la página
$pageTitle = "Detalles del Evento";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
</head>

<body class="mini-sidebar">
    <!-- ===== Main-Wrapper ===== -->
    <div id="wrapper">
        <?php include 'includes/nav.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <!-- Page-Content -->
        <div class="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-info">
                            <div class="panel-heading">Detalles del Evento</div>
                            <div class="panel-wrapper collapse in" aria-expanded="true">
                                <div class="panel-body">
                                    <?php if (!empty($evento)): ?>
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
                                    <?php else: ?>
                                        <p>No se encontraron detalles del evento.</p>
                                    <?php endif; ?>
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

  


   

    <!-- Asegúrate de que estos scripts estén incluidos al final de tu archivo, justo antes de cerrar el tag </body> -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inicializar los dropdowns de Bootstrap
            $('.dropdown-toggle').dropdown();

            // Tu código existente para generar contrato
            $('#generar-contrato').on('click', function(e) {
                e.preventDefault();
                var eventoId = <?php echo json_encode($evento_id); ?>;
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