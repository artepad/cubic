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
            margin: 2px 0;
            border-radius: 3px;
        }

        .evento-confirmado {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
        }

        .evento-propuesta {
            background-color: #ffc107 !important;
            border-color: #ffc107 !important;
            color: #000 !important;
        }

        .evento-cancelado {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
        }

        .evento-reagendado {
            background-color: #17a2b8 !important;
            border-color: #17a2b8 !important;
        }

        .calendar-container {
            padding: 20px;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
            margin: 15px;
        }

        .fc-today {
            background: #f8f9fa !important;
        }

        .evento-solicitado {
            background-color: #5bc0de !important;
            border-color: #5bc0de !important;
            color: #fff !important;
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
                events: 'functions/obtener_eventos_calendario.php', // Asegúrate que esta ruta es correcta
                eventClick: function(event) {
                    window.location.href = 'ver_evento.php?id=' + event.id;
                },
                loading: function(isLoading, view) {
                    if (isLoading) {
                        console.log('Cargando eventos...');
                    } else {
                        console.log('Eventos cargados');
                        // Verificar si hay eventos
                        var events = $('#calendario').fullCalendar('clientEvents');
                        console.log('Número de eventos cargados:', events.length);
                    }
                },
                eventRender: function(event, element) {
                    // Agregar clases según el estado
                    element.addClass('evento-' + event.estado.toLowerCase());

                    // Personalizar el tooltip
                    var tooltipContent = event.title;
                    if (event.estado) {
                        tooltipContent += '\nEstado: ' + event.estado;
                    }

                    element.attr('title', tooltipContent);
                },
                // Manejo de errores
                error: function(error) {
                    console.log('Error en FullCalendar:', error);
                }
            });

            // Agregar debug adicional
            $.ajax({
                url: 'functions/obtener_eventos_calendario.php',
                method: 'GET',
                success: function(response) {
                    console.log('Respuesta del servidor:', response);
                },
                error: function(xhr, status, error) {
                    console.log('Error al obtener eventos:');
                    console.log('Status:', status);
                    console.log('Error:', error);
                    console.log('Respuesta:', xhr.responseText);
                }
            });
        });
    </script>

</body>

</html>