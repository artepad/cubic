<?php require_once 'views/partials/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="white-box">
                <div class="row">
                    <div class="col-sm-6">
                        <h4 class="box-title">Clientes</h4>
                    </div>
                    <div class="col-sm-6">
                        <form action="<?php echo APP_URL; ?>" method="GET" class="form-inline pull-right">
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
                            <?php if (!empty($clientes)): ?>
                                <?php foreach ($clientes as $cliente): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cliente['nombres']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['apellidos']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['rut']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['nombre_empresa'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['correo']); ?></td>
                                        <td>
                                            <a href="<?php echo APP_URL; ?>?action=contrato&id=<?php echo $cliente['id']; ?>" class="btn btn-primary btn-sm" title="Generar Contrato"><i class="fa fa-file-text-o"></i></a>
                                            <a href="<?php echo APP_URL; ?>?action=edit&id=<?php echo $cliente['id']; ?>" class="btn btn-info btn-sm" title="Editar"><i class="fa fa-pencil"></i></a>
                                            <a href="<?php echo APP_URL; ?>?action=delete&id=<?php echo $cliente['id']; ?>" class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Está seguro de que desea eliminar este cliente?')"><i class="fa fa-trash-o"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6">No se encontraron clientes.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Paginación -->
                <ul class="pagination">
                    <?php
                    $rango = 2;
                    if ($pagina_actual > 1): ?>
                        <li><a href="<?php echo APP_URL; ?>?pagina=<?php echo $pagina_actual - 1; ?>&search=<?php echo urlencode($search); ?>">«</a></li>
                    <?php else: ?>
                        <li class="disabled"><span>«</span></li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $pagina_actual - $rango); $i <= min($totalPaginas, $pagina_actual + $rango); $i++): ?>
                        <?php if ($i == $pagina_actual): ?>
                            <li class="active"><span><?php echo $i; ?></span></li>
                        <?php else: ?>
                            <li><a href="<?php echo APP_URL; ?>?pagina=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a></li>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($pagina_actual < $totalPaginas): ?>
                        <li><a href="<?php echo APP_URL; ?>?pagina=<?php echo $pagina_actual + 1; ?>&search=<?php echo urlencode($search); ?>">»</a></li>
                    <?php else: ?>
                        <li class="disabled"><span>»</span></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'views/partials/footer.php'; ?>