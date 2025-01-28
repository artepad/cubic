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

// Procesar eliminación si se solicita
if (isset($_POST['eliminar_gira']) && isset($_POST['gira_id'])) {
    $gira_id = (int)$_POST['gira_id'];
    
    // Verificar si la gira tiene eventos asociados
    $sql_check = "SELECT COUNT(*) as total FROM eventos WHERE gira_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $gira_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $eventos_asociados = $result_check->fetch_assoc()['total'];

    if ($eventos_asociados > 0) {
        $_SESSION['mensaje'] = "No se puede eliminar la gira porque tiene eventos asociados.";
        $_SESSION['mensaje_tipo'] = "danger";
    } else {
        // Eliminar la gira
        $sql_delete = "DELETE FROM giras WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $gira_id);
        
        if ($stmt_delete->execute()) {
            $_SESSION['mensaje'] = "Gira eliminada exitosamente.";
            $_SESSION['mensaje_tipo'] = "success";
        } else {
            $_SESSION['mensaje'] = "Error al eliminar la gira.";
            $_SESSION['mensaje_tipo'] = "danger";
        }
    }
    header("Location: ingreso_giras.php");
    exit;
}

// Procesar la actualización si se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nombre'])) {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    
    if (isset($_POST['editar_gira_id'])) {
        // Actualizar gira existente
        $gira_id = (int)$_POST['editar_gira_id'];
        $sql = "UPDATE giras SET nombre = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $nombre, $gira_id);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Gira actualizada exitosamente.";
            $_SESSION['mensaje_tipo'] = "success";
        } else {
            $_SESSION['mensaje'] = "Error al actualizar la gira: " . $conn->error;
            $_SESSION['mensaje_tipo'] = "danger";
        }
    } else {
        // Insertar nueva gira
        $sql = "INSERT INTO giras (nombre) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nombre);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Gira creada exitosamente.";
            $_SESSION['mensaje_tipo'] = "success";
        } else {
            $_SESSION['mensaje'] = "Error al guardar la gira: " . $conn->error;
            $_SESSION['mensaje_tipo'] = "danger";
        }
    }
    header("Location: ingreso_giras.php");
    exit;
}

// Obtener el total de giras
$sqlTotal = "SELECT COUNT(*) as total FROM giras";
$resultTotal = $conn->query($sqlTotal);
$fila = $resultTotal->fetch_assoc();
$totalRegistros = $fila['total'];

// Calcular el total de páginas
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Obtener todas las giras con paginación
$sql = "SELECT g.*, 
               (SELECT COUNT(*) FROM eventos WHERE gira_id = g.id) as total_eventos
        FROM giras g 
        ORDER BY g.fecha_creacion DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $registrosPorPagina, $offset);
$stmt->execute();
$result_giras = $stmt->get_result();

// Definir el título de la página
$pageTitle = "Gestión de Giras - Schaaf Producciones";

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .modal-title {
            margin: 0;
            line-height: 1.42857143;
        }
        .search-container {
            margin-bottom: 20px;
        }
        #searchInput {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e4e7ea;
            border-radius: 3px;
        }
        .actions-column {
            white-space: nowrap;
            width: 1%;
        }
        .badge-eventos {
            background-color: #5bc0de;
            color: white;
            padding: 3px 7px;
            border-radius: 10px;
            font-size: 12px;
        }
    </style>
</head>

<body class="mini-sidebar">
    <div id="wrapper">
        <div class="preloader">
            <div class="cssload-speeding-wheel"></div>
        </div>

        <?php include 'includes/nav.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div class="page-wrapper">
            <div class="container-fluid">
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['mensaje_tipo']; ?> alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <?php 
                            echo $_SESSION['mensaje'];
                            unset($_SESSION['mensaje']);
                            unset($_SESSION['mensaje_tipo']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="white-box">
                            <div class="row">
                                <div class="col-md-6">
                                    <h3 class="box-title">Nueva Gira</h3>
                                </div>
                                <div class="col-md-6">
                                    <div class="search-container">
                                        <input type="text" id="searchInput" placeholder="Buscar gira...">
                                    </div>
                                </div>
                            </div>

                            <!-- Formulario para nueva gira -->
                            <form method="post" class="form-horizontal form-material mb-4">
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <input type="text" class="form-control form-control-line" 
                                               id="nombre" name="nombre" required 
                                               placeholder="Nombre de la Gira">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-success">Guardar Gira</button>
                                    </div>
                                </div>
                            </form>

                            <h3 class="box-title mt-4">Giras Existentes</h3>
                            <div class="table-responsive">
                                <table id="girasTable" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Acciones</th>
                                            <th>Nombre de la Gira</th>
                                            <th>Eventos</th>
                                            <th>Fecha de Creación</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result_giras && $result_giras->num_rows > 0): ?>
                                            <?php while ($row = $result_giras->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="actions-column">
                                                        <button class="btn btn-warning btn-sm edit-gira" 
                                                                data-id="<?php echo $row['id']; ?>"
                                                                data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                                                title="Editar">
                                                            <i class="fa fa-pencil"></i>
                                                        </button>
                                                        <?php if ($row['total_eventos'] == 0): ?>
                                                            <form method="post" style="display: inline;">
                                                                <input type="hidden" name="gira_id" value="<?php echo $row['id']; ?>">
                                                                <button type="submit" name="eliminar_gira" 
                                                                        class="btn btn-danger btn-sm"
                                                                        onclick="return confirm('¿Está seguro de eliminar esta gira?')"
                                                                        title="Eliminar">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                                    <td>
                                                        <span class="badge badge-eventos">
                                                            <?php echo $row['total_eventos']; ?> eventos
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $fecha = new DateTime($row['fecha_creacion']);
                                                            echo $fecha->format('d/m/Y H:i'); 
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">
                                                    No hay giras registradas.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <div class="text-center">
                                <ul class="pagination">
                                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                        <li class="<?php echo ($i == $paginaActual) ? 'active' : ''; ?>">
                                            <a href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <?php include 'includes/scripts.php'; ?>

    <!-- Modal para editar gira -->
    <div class="modal fade" id="editarGiraModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Editar Gira</h4>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="editar_gira_id" id="editar_gira_id">
                        <div class="form-group">
                            <label for="nombre_editar">Nombre de la Gira</label>
                            <input type="text" class="form-control" id="nombre_editar" 
                                   name="nombre" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Funcionalidad de búsqueda
            $("#searchInput").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#girasTable tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // Manejar clic en botón de editar
            $('.edit-gira').click(function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                $('#editar_gira_id').val(id);
                $('#nombre_editar').val(nombre);
                $('#editarGiraModal').modal('show');
            });
        });
    </script>
</body>
</html>