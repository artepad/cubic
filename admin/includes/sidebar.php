<?php
// Obtener el nombre del archivo actual
$current_page = basename($_SERVER['PHP_SELF']);

// Función para verificar si la página actual coincide con la del menú
function is_active($page_name) {
    global $current_page;
    return ($current_page == $page_name) ? 'active' : '';
}

// Función para generar la clase de estilo para el elemento del menú activo
function active_class($page_name) {
    return is_active($page_name) ? 'class="active"' : '';
}
?>

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
                <li <?php echo active_class('index.php'); ?>>
                    <a class="waves-effect" href="index.php" aria-expanded="false">
                        <i class="icon-screen-desktop fa-fw"></i>
                        <span class="hide-menu <?php echo is_active('index.php') ? 'font-bold' : ''; ?>"> Dashboard
                            <span class="label label-rounded label-success pull-right"><?php echo $totalEventosActivos; ?></span>
                        </span>
                    </a>
                </li>
                <li <?php echo active_class('listar_agenda.php'); ?>>
                    <a href="listar_agenda.php" aria-expanded="false">
                        <i class="icon-notebook fa-fw"></i>
                        <span class="hide-menu <?php echo is_active('listar_agenda.php') ? 'font-bold' : ''; ?>">Agenda
                            <span class="label label-rounded label-warning pull-right"><?php echo $totalEventosAnioActual; ?></span>
                        </span>
                    </a>
                </li>
                <li <?php echo active_class('listar_clientes.php'); ?>>
                    <a class="waves-effect" href="listar_clientes.php" aria-expanded="false">
                        <i class="icon-user fa-fw"></i>
                        <span class="hide-menu <?php echo is_active('listar_clientes.php') ? 'font-bold' : ''; ?>"> Clientes
                            <span class="label label-rounded label-info pull-right"><?php echo $totalClientes; ?></span>
                        </span>
                    </a>
                </li>
                <li <?php echo active_class('listar_artistas.php'); ?>>
                    <a class="waves-effect" href="listar_artistas.php" aria-expanded="false">
                        <i class="icon-microphone fa-fw"></i>
                        <span class="hide-menu <?php echo is_active('listar_artistas.php') ? 'font-bold' : ''; ?>">Artistas
                            <span class="label label-rounded label-primary pull-right"><?php echo isset($totalArtistas) ? $totalArtistas : '0'; ?></span>
                        </span>
                    </a>
                </li>
                <li <?php echo active_class('listar_calendario.php'); ?>>
                    <a href="listar_calendario.php" aria-expanded="false">
                        <i class="icon-calender fa-fw"></i>
                        <span class="hide-menu <?php echo is_active('listar_calendario.php') ? 'font-bold' : ''; ?>">Calendario</span>
                    </a>
                </li>
                <li <?php echo active_class('configuracion.php'); ?>>
                    <a href="configuracion.php" aria-expanded="false">
                        <i class="icon-settings fa-fw"></i>
                        <span class="hide-menu <?php echo is_active('configuracion.php') ? 'font-bold' : ''; ?>">Configuración</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="p-30">
            <span class="hide-menu">
                <a href="ingreso_cliente.php" class="btn btn-info m-b-10 btn-block">Nuevo Cliente</a>
                <a href="ingreso_evento.php" class="btn btn-success btn-block">Nuevo Evento</a>
                <a href="logout.php" class="btn btn-default m-t-15 btn-block">Cerrar Sesión</a>
            </span>
        </div>
    </div>
</aside>
<!-- ===== Left-Sidebar-End ===== -->