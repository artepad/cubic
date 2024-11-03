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

// Obtener detalles del cliente si se proporciona un ID
$cliente = [];
$cliente_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($cliente_id > 0) {
    // Consulta para obtener datos del cliente, empresa y eventos asociados
    $sql = "SELECT c.*, e.nombre as nombre_empresa, e.rut as rut_empresa, 
            e.direccion as direccion_empresa,
            COUNT(ev.id) as total_eventos,
            SUM(CASE WHEN ev.fecha_evento >= CURDATE() THEN 1 ELSE 0 END) as eventos_activos
            FROM clientes c
            LEFT JOIN empresas e ON c.id = e.cliente_id
            LEFT JOIN eventos ev ON c.id = ev.cliente_id
            WHERE c.id = ?
            GROUP BY c.id";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc();

    // Obtener los últimos 5 eventos del cliente
    if ($cliente) {
        $sql_eventos = "SELECT id, nombre_evento, fecha_evento, estado_evento, valor_evento 
                       FROM eventos 
                       WHERE cliente_id = ? 
                       ORDER BY fecha_evento DESC 
                       LIMIT 5";
        $stmt_eventos = $conn->prepare($sql_eventos);
        $stmt_eventos->bind_param("i", $cliente_id);
        $stmt_eventos->execute();
        $eventos_cliente = $stmt_eventos->get_result();
    }
}

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();

// Definir el título de la página
$pageTitle = "Detalles del Cliente";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .info-box {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        }
        .eventos-list {
            margin-top: 20px;
        }
        .evento-item {
            border-left: 3px solid #7ace4c;
            padding: 10px 15px;
            margin-bottom: 10px;
            background: #f9f9f9;
        }
        .stats-box {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .stats-number {
            font-size: 24px;
            font-weight: bold;
            color: #2196F3;
        }
    </style>
</head>

<body class="mini-sidebar">
    <!-- Main-Wrapper -->
    <div id="wrapper">
        <?php include 'includes/nav.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <!-- Page-Content -->
        <div class="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-info">
                            <div class="panel-heading"><?php echo $pageTitle; ?></div>
                            <div class="panel-wrapper collapse in" aria-expanded="true">
                                <div class="panel-body">
                                    <?php if (!empty($cliente)): ?>
                                        <!-- Estadísticas Rápidas -->
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="stats-box">
                                                    <div class="stats-number"><?php echo $cliente['total_eventos']; ?></div>
                                                    <div>Total de Eventos</div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="stats-box">
                                                    <div class="stats-number"><?php echo $cliente['eventos_activos']; ?></div>
                                                    <div>Eventos Activos</div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="stats-box">
                                                    <div class="stats-number">
                                                        <?php 
                                                            $antiguedad = floor((time() - strtotime($cliente['fecha_creacion'])) / (60 * 60 * 24 * 30));
                                                            echo $antiguedad;
                                                        ?>
                                                    </div>
                                                    <div>Meses como Cliente</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Información Personal -->
                                        <div class="info-box">
                                            <h3 class="box-title">Información Personal</h3>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label">Nombre Completo:</label>
                                                        <p class="form-control-static"><strong><?php echo htmlspecialchars($cliente['nombres'] . ' ' . $cliente['apellidos']); ?></strong></p>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label">RUT:</label>
                                                        <p class="form-control-static"><strong><?php echo htmlspecialchars($cliente['rut']); ?></strong></p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label">Correo:</label>
                                                        <p class="form-control-static"><strong><?php echo htmlspecialchars($cliente['correo']); ?></strong></p>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label">Celular:</label>
                                                        <p class="form-control-static"><strong><?php echo htmlspecialchars($cliente['celular']); ?></strong></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Información de la Empresa -->
                                        <?php if (!empty($cliente['nombre_empresa'])): ?>
                                        <div class="info-box">
                                            <h3 class="box-title">Información de la Empresa</h3>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label">Nombre Empresa:</label>
                                                        <p class="form-control-static"><strong><?php echo htmlspecialchars($cliente['nombre_empresa']); ?></strong></p>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="control-label">RUT Empresa:</label>
                                                        <p class="form-control-static"><strong><?php echo htmlspecialchars($cliente['rut_empresa']); ?></strong></p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="control-label">Dirección:</label>
                                                        <p class="form-control-static"><strong><?php echo htmlspecialchars($cliente['direccion_empresa']); ?></strong></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Últimos Eventos -->
                                        <div class="info-box">
                                            <h3 class="box-title">Últimos Eventos</h3>
                                            <div class="eventos-list">
                                                <?php if (isset($eventos_cliente) && $eventos_cliente->num_rows > 0): ?>
                                                    <?php while ($evento = $eventos_cliente->fetch_assoc()): ?>
                                                        <div class="evento-item">
                                                            <div class="row">
                                                                <div class="col-md-4">
                                                                    <strong><?php echo htmlspecialchars($evento['nombre_evento']); ?></strong>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <?php echo formatearFecha($evento['fecha_evento']); ?>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <?php echo generarEstadoEvento($evento['estado_evento']); ?>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <a href="ver_evento.php?id=<?php echo $evento['id']; ?>" class="btn btn-info btn-sm">Ver Detalles</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <p>No hay eventos registrados para este cliente.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Botones de Acción -->
                                        <div class="form-actions">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <a href="eventos.php?cliente_id=<?php echo $cliente_id; ?>" class="btn btn-info m-r-10">
                                                        <i class="fa fa-calendar"></i> Nuevo Evento
                                                    </a>
                                                    <a href="editar_cliente.php?id=<?php echo $cliente_id; ?>" class="btn btn-warning m-r-10">
                                                        <i class="fa fa-pencil"></i> Editar Cliente
                                                    </a>
                                                    <a href="listar_clientes.php" class="btn btn-default">
                                                        <i class="fa fa-arrow-left"></i> Volver
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-danger">
                                            <p>No se encontró información del cliente.</p>
                                            <a href="listar_clientes.php" class="btn btn-default m-t-10">Volver al listado</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Scripts básicos -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>