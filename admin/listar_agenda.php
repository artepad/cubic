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
$totalEventosAnioActual = getTotalEventosAnioActual($conn);

// Configuración de la paginación
$registrosPorPagina = 8;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$busqueda = isset($_GET['search']) ? $_GET['search'] : '';
$offset = ($paginaActual - 1) * $registrosPorPagina;

// Construir la consulta base
$baseQuery = "
    SELECT e.*, c.nombres, c.apellidos 
    FROM eventos e
    LEFT JOIN clientes c ON e.cliente_id = c.id
    WHERE 1=1
";

// Agregar condiciones de búsqueda si existe
if (!empty($busqueda)) {
    $busqueda = $conn->real_escape_string($busqueda);
    $baseQuery .= " AND (
        e.nombre_evento LIKE '%$busqueda%' OR 
        e.ciudad_evento LIKE '%$busqueda%' OR 
        c.nombres LIKE '%$busqueda%' OR 
        c.apellidos LIKE '%$busqueda%' OR
        e.estado_evento LIKE '%$busqueda%'
    )";
}

// Obtener total de registros para la paginación
$sqlTotal = str_replace('e.*, c.nombres, c.apellidos', 'COUNT(*) as total', $baseQuery);
$resultTotal = $conn->query($sqlTotal);
$fila = $resultTotal->fetch_assoc();
$totalRegistros = $fila['total'];
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Agregar límites a la consulta principal
$baseQuery .= " ORDER BY e.fecha_evento DESC LIMIT $registrosPorPagina OFFSET $offset";
$result_eventos = $conn->query($baseQuery);

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();

// Definir el título de la página
$pageTitle = "Listar Agenda";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
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
    <div id="wrapper">
        <?php include 'includes/nav.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div class="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="white-box">
                            <div class="titulo-busqueda">
                                <h3 class="box-title">Agenda de Eventos</h3>
                                <div class="search-container">
                                    <input type="text" id="searchInput" placeholder="Buscar evento..."
                                        value="<?php echo htmlspecialchars($busqueda); ?>">
                                </div>
                            </div>

                            <?php if (!$result_eventos || $result_eventos->num_rows === 0): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> No se encontraron Eventos.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table id="eventosTable" class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Acciones</th>
                                                <th>Nombre del Evento</th>
                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th>Cliente</th>
                                                <th>Ciudad</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($evento = $result_eventos->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <a href="ver_evento.php?id=<?php echo $evento['id']; ?>"
                                                            class="btn btn-sm btn-info"
                                                            data-toggle="tooltip"
                                                            title="Ver Evento">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <button type="button"
                                                            class="btn btn-sm btn-warning cambiar-estado"
                                                            data-id="<?php echo $evento['id']; ?>"
                                                            data-toggle="tooltip"
                                                            title="Cambiar Estado">
                                                            <i class="fa fa-exchange"></i>
                                                        </button>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($evento['nombre_evento']); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($evento['fecha_evento'])); ?></td>
                                                    <td><?php echo date('H:i', strtotime($evento['hora_evento'])); ?></td>
                                                    <td><?php echo htmlspecialchars($evento['nombres'] . ' ' . $evento['apellidos']); ?></td>
                                                    <td><?php echo htmlspecialchars($evento['ciudad_evento']); ?></td>
                                                    <td><?php echo generarEstadoEvento($evento['estado_evento']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Paginación -->
                                <div class="custom-pagination">
                                    <?php
                                    $rango = 2; // Número de páginas a mostrar antes y después de la página actual

                                    // Mostrar primera página si estamos lejos de ella
                                    if ($paginaActual > $rango + 1) {
                                        echo "<a href='?pagina=1" . ($busqueda ? "&search=" . urlencode($busqueda) : "") .
                                            "' class='page-number'>1</a>";
                                        if ($paginaActual > $rango + 2) {
                                            echo "<span class='page-number'>...</span>";
                                        }
                                    }

                                    // Mostrar páginas alrededor de la página actual
                                    for (
                                        $i = max(1, $paginaActual - $rango);
                                        $i <= min($totalPaginas, $paginaActual + $rango);
                                        $i++
                                    ) {
                                        if ($i == $paginaActual) {
                                            echo "<span class='page-number active'>$i</span>";
                                        } else {
                                            echo "<a href='?pagina=$i" . ($busqueda ? "&search=" . urlencode($busqueda) : "") .
                                                "' class='page-number'>$i</a>";
                                        }
                                    }

                                    // Mostrar última página si estamos lejos de ella
                                    if ($paginaActual < $totalPaginas - $rango) {
                                        if ($paginaActual < $totalPaginas - $rango - 1) {
                                            echo "<span class='page-number'>...</span>";
                                        }
                                        echo "<a href='?pagina=$totalPaginas" .
                                            ($busqueda ? "&search=" . urlencode($busqueda) : "") .
                                            "' class='page-number'>$totalPaginas</a>";
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Scripts existentes... -->
    <script>
        $(document).ready(function() {
            var searchTimeout;

            $('#searchInput').on('keyup', function() {
                clearTimeout(searchTimeout);
                var searchValue = $(this).val();

                // Esperar 500ms después de que el usuario deje de escribir
                searchTimeout = setTimeout(function() {
                    // Actualizar la URL con el término de búsqueda y recargar la página
                    var newUrl = updateQueryStringParameter(window.location.href, 'search', searchValue);
                    newUrl = updateQueryStringParameter(newUrl, 'pagina', '1'); // Volver a la primera página
                    window.location.href = newUrl;
                }, 500);
            });

            // Función para actualizar parámetros en la URL
            function updateQueryStringParameter(uri, key, value) {
                var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
                var separator = uri.indexOf('?') !== -1 ? "&" : "?";

                if (uri.match(re)) {
                    return uri.replace(re, '$1' + key + "=" + encodeURIComponent(value) + '$2');
                } else {
                    return uri + separator + key + "=" + encodeURIComponent(value);
                }
            }
        });
    </script>
</body>

</html>