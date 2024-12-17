<?php
// Iniciar sesión y configuración
session_start();
require_once 'config/config.php';
require_once 'functions/functions.php';


// Obtener datos comunes
$totalClientes = getTotalClientes($conn);
$totalEventosActivos = getTotalEventosConfirmadosActivos($conn);
$totalEventosAnioActual = getTotalEventos($conn);

// Verificar autenticación
checkAuthentication();

// Definir el título de la página
$pageTitle = "Calendario";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
    <!-- FullCalendar CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" rel="stylesheet">
    <!-- Estilos personalizados para el calendario -->
    <style>
        .fc-event {
            cursor: pointer;
            padding: 2px 5px;
        }
        .calendar-container {
            padding: 20px;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            margin: 15px;
        }
        .fc-today {
            background: #f8f9fa !important;
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

        <!-- Content Page -->
        <div class="page-wrapper">
            <div class="container-fluid">
                <!-- Breadcrumb -->
                <div class="row bg-title">
                    <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                        <h4 class="page-title">Calendario de Eventos</h4>
                    </div>
                    <div class="col-lg-9 col-sm-8 col-md-8 col-xs-12">
                        <ol class="breadcrumb">
                            <li><a href="index.php">Dashboard</a></li>
                            <li class="active">Calendario</li>
                        </ol>
                    </div>
                </div>
                
                <!-- Calendario -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="white-box">
                            <div id="calendario"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>

    <!-- FullCalendar Dependencies -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/locale/es.js"></script>

   
<script>
$(document).ready(function() {
    $('#calendario').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        defaultView: 'month',
        locale: 'es',
        timeFormat: 'HH:mm',
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            day: 'Día'
        },
        events: {
            url: 'functions/obtener_eventos_calendario.php',
            error: function(xhr, status, error) {
                console.log('Error al cargar eventos:');
                console.log('Status:', status);
                console.log('Error:', error);
                console.log('Response:', xhr.responseText);
            }
        },
        loading: function(isLoading) {
            if (isLoading) {
                console.log('Cargando eventos...');
            } else {
                console.log('Eventos cargados');
            }
        },
        eventRender: function(event, element) {
            element.find('.fc-title').html(event.title);
        }
    });
});
</script>

</body>
</html>