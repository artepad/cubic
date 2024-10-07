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
                    <a class="waves-effect" href="index.php" aria-expanded="false">
                        <i class="icon-screen-desktop fa-fw"></i> 
                        <span class="hide-menu"> Dashboard 
                            <span class="label label-rounded label-success pull-right"><?php echo $totalEventosActivos; ?></span>
                        </span>
                    </a>
                </li>
                <li>
                    <a class="waves-effect" href="clientes.php" aria-expanded="false">
                        <i class="icon-user fa-fw"></i> 
                        <span class="hide-menu"> Clientes 
                            <span class="label label-rounded label-info pull-right"><?php echo $totalClientes; ?></span>
                        </span>
                    </a>
                </li>
                <li>
                    <a href="agenda.php" aria-expanded="false">
                        <i class="icon-notebook fa-fw"></i> 
                        <span class="hide-menu">Agenda
                            <span class="label label-rounded label-warning pull-right"><?php echo $totalEventosAnioActual; ?></span>
                        </span>
                    </a>
                </li>
                <li>
                    <a href="calendario.php" aria-expanded="false">
                        <i class="icon-calender fa-fw"></i> <span class="hide-menu">Calendario</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="p-30">
            <span class="hide-menu">
                <a href="eventos.php" class="btn btn-success">Nuevo Evento</a>
                <a href="logout.php" class="btn btn-default m-t-15">Cerrar Sesión</a>
            </span>
        </div>
    </div>
</aside>
<!-- ===== Left-Sidebar-End ===== -->