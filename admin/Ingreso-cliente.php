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

// Inicializar variables para mantener los valores del formulario
$nombres = $apellidos = $rut = $email = $celular = $genero = $nombre_empresa = $rut_empresa = $direccion_empresa = '';
$errores = [];

// Función de validación general
function validarCampo($valor, $longitud, $patron) {
    $valor = trim($valor);
    return strlen($valor) <= $longitud && preg_match($patron, $valor);
}

// Función de validación del RUT
function validarRut($rut) {
    return preg_match('/^[0-9]{1,2}\.[0-9]{3}\.[0-9]{3}-[0-9Kk]$/', $rut);
}

// Función para limpiar el RUT antes de guardarlo en la base de datos
function limpiarRut($rut) {
    return str_replace(['.', '-'], '', $rut);
}

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capturar los valores del formulario
    $nombres = $_POST['nombres'] ?? '';
    $apellidos = $_POST['apellidos'] ?? '';
    $rut = $_POST['rut'] ?? '';
    $email = $_POST['email'] ?? '';
    $celular = $_POST['celular'] ?? '';
    $genero = $_POST['genero'] ?? '';
    $nombre_empresa = $_POST['nombre_empresa'] ?? '';
    $rut_empresa = $_POST['rut_empresa'] ?? '';
    $direccion_empresa = $_POST['direccion_empresa'] ?? '';

    // Validar nombres y apellidos
    if (!validarCampo($nombres, 20, '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,20}$/')) {
        $errores['nombres'] = "Los nombres deben contener solo letras y no exceder 20 caracteres.";
    }
    if (!validarCampo($apellidos, 20, '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,20}$/')) {
        $errores['apellidos'] = "Los apellidos deben contener solo letras y no exceder 20 caracteres.";
    }

    // Validar RUT
    if (!validarRut($rut)) {
        $errores['rut'] = "Formato de RUT inválido. Debe ser como 17.398.463-4 o 7.398.463-K";
    } else {
        $rut = limpiarRut($rut); // Limpiar el RUT antes de usarlo
    }

    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = "Formato de correo electrónico inválido.";
    }

    // Validar celular (asumiendo que debe ser numérico y de longitud específica)
    if (!preg_match('/^[0-9]{9,12}$/', $celular)) {
        $errores['celular'] = "El número de celular debe contener entre 9 y 12 dígitos.";
    }

    // Validar género
    if (!in_array($genero, ['Masculino', 'Femenino', 'Otro'])) {
        $errores['genero'] = "Por favor, seleccione un género válido.";
    }

    // Validar campos de empresa si se proporcionaron
    if (!empty($nombre_empresa)) {
        if (strlen($nombre_empresa) > 100) {
            $errores['nombre_empresa'] = "El nombre de la empresa no debe exceder 100 caracteres.";
        }
        if (!empty($rut_empresa) && !validarRut($rut_empresa)) {
            $errores['rut_empresa'] = "Formato de RUT de empresa inválido.";
        } else {
            $rut_empresa = limpiarRut($rut_empresa);
        }
        if (strlen($direccion_empresa) > 250) {
            $errores['direccion_empresa'] = "La dirección de la empresa no debe exceder 250 caracteres.";
        }
    }

    if (empty($errores)) {
        // Verificar si el RUT ya existe
        $check_rut_sql = "SELECT id FROM clientes WHERE rut = ?";
        $check_stmt = $conn->prepare($check_rut_sql);
        $check_stmt->bind_param("s", $rut);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $errores['rut'] = "Ya existe un cliente con ese RUT.";
        } else {
            // Iniciar transacción
            $conn->begin_transaction();

            try {
                // Insertar cliente
                $sql_cliente = "INSERT INTO clientes (nombres, apellidos, rut, correo, celular, genero) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_cliente = $conn->prepare($sql_cliente);
                $stmt_cliente->bind_param("ssssss", $nombres, $apellidos, $rut, $email, $celular, $genero);
                $stmt_cliente->execute();
                $cliente_id = $conn->insert_id;

                // Insertar empresa si se proporcionaron datos
                if (!empty($nombre_empresa)) {
                    $sql_empresa = "INSERT INTO empresas (nombre, rut, direccion, cliente_id) VALUES (?, ?, ?, ?)";
                    $stmt_empresa = $conn->prepare($sql_empresa);
                    $stmt_empresa->bind_param("sssi", $nombre_empresa, $rut_empresa, $direccion_empresa, $cliente_id);
                    $stmt_empresa->execute();
                }

                // Confirmar transacción
                $conn->commit();
                $mensaje = "Cliente agregado con éxito.";
                $mensaje_tipo = "success";

                // Limpiar los campos del formulario después de una inserción exitosa
                $nombres = $apellidos = $rut = $email = $celular = $genero = $nombre_empresa = $rut_empresa = $direccion_empresa = '';
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $conn->rollback();
                $mensaje = "Error al agregar el cliente: " . $e->getMessage();
                $mensaje_tipo = "danger";
            }
        }
    }
}

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();

// Definir el título de la página y contenido específico
$pageTitle = "Ingresar Nuevo Cliente";

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/head.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        .error-message {
            color: red;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .is-invalid {
            border-color: red !important;
        }
    </style>
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
                <?php
                if (isset($mensaje)) {
                    echo "<div class='alert alert-{$mensaje_tipo}'>{$mensaje}</div>";
                }
                ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="white-box">
                            <h3 class="box-title m-b-0">Ingresar Nuevo Cliente</h3>
                            <p class="text-muted m-b-30 font-13">Información del Cliente y Empresa o Municipalidad</p>
                            <form id="clienteForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="form-horizontal">
                                <div class="form-body">
                                    <h3 class="box-title">Información Personal</h3>
                                    <hr class="m-t-0 m-b-40">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">Nombres</label>
                                                <div class="col-md-9">
                                                    <input type="text" class="form-control" name="nombres" id="nombres" maxlength="20" required>
                                                    <span class="error-message" id="nombresError"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">Apellidos</label>
                                                <div class="col-md-9">
                                                    <input type="text" class="form-control" name="apellidos" id="apellidos" maxlength="20" required>
                                                    <span class="error-message" id="apellidosError"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">RUT</label>
                                                <div class="col-md-9">
                                                    <input type="text" class="form-control" name="rut" id="rut" maxlength="12" required>
                                                    <span class="error-message" id="rutError"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">Correo Electrónico</label>
                                                <div class="col-md-9">
                                                    <input type="email" class="form-control" name="email" maxlength="60" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">Celular</label>
                                                <div class="col-md-9">
                                                    <input type="tel" class="form-control" name="celular" maxlength="16" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">Género</label>
                                                <div class="col-md-9">
                                                    <select class="form-control" name="genero" required>
                                                        <option value="">Seleccione un género</option>
                                                        <option value="Masculino">Masculino</option>
                                                        <option value="Femenino">Femenino</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <h3 class="box-title">Información de la Empresa o Municipalidad (Opcional)</h3>
                                    <hr class="m-t-0 m-b-40">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">Nombre Empresa o Muni</label>
                                                <div class="col-md-9">
                                                    <input type="text" class="form-control" name="nombre_empresa" maxlength="100">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">RUT Empresa o Muni</label>
                                                <div class="col-md-9">
                                                    <input type="text" class="form-control" name="rut_empresa" maxlength="12">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">Dirección Empresa o Muni</label>
                                                <div class="col-md-9">
                                                    <input type="text" class="form-control" name="direccion_empresa" maxlength="250">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <div class="row">
                                        <div class="col-md-12 text-center">
                                            <button type="submit" class="btn btn-success" id="submitBtn">
                                                <i class="fa fa-check"></i> Guardar
                                            </button>
                                            <a href="index.php" class="btn btn-default">Cancelar</a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'includes/footer.php'; ?>
        </div>
        <!-- Page-Content-End -->
    </div>
    <!-- ===== Main-Wrapper-End ===== -->



    <script>
    $(document).ready(function() {
        function validarCampo(campo, regex, errorMsg) {
            var valor = $(campo).val().trim();
            var esValido = regex.test(valor);
            var errorSpan = $(campo + 'Error');
            
            if (!esValido) {
                errorSpan.text(errorMsg).show();
                $(campo).addClass('is-invalid');
            } else {
                errorSpan.text('').hide();
                $(campo).removeClass('is-invalid');
            }
            
            return esValido;
        }

        function formatearRut(rut) {
            // Eliminar caracteres no permitidos
            var valor = rut.replace(/[^0-9kK\-\.]/g, '');
            
            // Aplicar formato
            var resultado = valor.replace(/\./g, '').replace('-', '');
            if(resultado.length > 1) {
                resultado = resultado.substring(0, resultado.length - 1) + '-' + resultado.substring(resultado.length - 1);
            }
            if(resultado.length > 5) {
                resultado = resultado.substring(0, resultado.length - 5) + '.' + resultado.substring(resultado.length - 5);
            }
            if(resultado.length > 9) {
                resultado = resultado.substring(0, resultado.length - 9) + '.' + resultado.substring(resultado.length - 9);
            }
            
            return resultado;
        }

        $('#rut').on('input', function(e) {
            var start = this.selectionStart,
                end = this.selectionEnd;
            
            var $this = $(this);
            var valor = $this.val();
            var valorFormateado = formatearRut(valor);
            
            $this.val(valorFormateado);
            
            // Ajustar la posición del cursor
            if (valor !== valorFormateado) {
                var diff = valorFormateado.length - valor.length;
                start += diff;
                end += diff;
            }
            
            this.setSelectionRange(start, end);
            
            validarCampo('#rut', /^[0-9]{1,2}\.[0-9]{3}\.[0-9]{3}-[0-9Kk]$/, 'Formato de RUT inválido. Debe ser como 17.398.463-4 o 7.398.463-K');
        });

        $('#clienteForm').on('submit', function(e) {
            var nombreValido = validarCampo('#nombres', /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,20}$/, 'Ingrese solo letras (máximo 20 caracteres)');
            var apellidoValido = validarCampo('#apellidos', /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,20}$/, 'Ingrese solo letras (máximo 20 caracteres)');
            var rutValido = validarCampo('#rut', /^[0-9]{1,2}\.[0-9]{3}\.[0-9]{3}-[0-9Kk]$/, 'Formato de RUT inválido. Debe ser como 17.398.463-4 o 7.398.463-K');

            if (!nombreValido || !apellidoValido || !rutValido) {
                e.preventDefault();
            }
        });

        $('#nombres, #apellidos').on('input', function() {
            var campo = '#' + $(this).attr('id');
            validarCampo(campo, /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,20}$/, 'Ingrese solo letras (máximo 20 caracteres)');
        });
    });
    </script>

</body>

</html>