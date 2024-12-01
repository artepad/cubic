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

// Determinar si es edición o nuevo registro
$esEdicion = isset($_GET['id']);
$cliente = null;

// Inicializar variables para mantener los valores del formulario
$nombres = $apellidos = $rut = $email = $celular = $genero = $nombre_empresa = $rut_empresa = $direccion_empresa = '';

// Función para validar el formato del RUT
function validarRut($rut)
{
    return preg_match('/^[0-9]{1,2}\.[0-9]{3}\.[0-9]{3}-[0-9Kk]$/', $rut);
}

// Modificar la función formatearRut para mantener el formato
function formatearRut($rut)
{
    if (empty($rut)) {
        return '';
    }
    // Primero limpiamos el RUT de cualquier formato previo
    $rutLimpio = preg_replace('/[^0-9kK]/', '', $rut);

    if (strlen($rutLimpio) < 2) {
        return '';
    }

    // Separamos el dígito verificador
    $dv = substr($rutLimpio, -1);
    $numero = substr($rutLimpio, 0, -1);

    if (!is_numeric($numero)) {
        return '';
    }

    // Formateamos el número
    $numero = number_format($numero, 0, "", ".");

    // Retornamos el RUT formateado
    return $numero . "-" . $dv;
}

// Función de validación general
function validarCampo($valor, $longitud, $patron)
{
    $valor = trim($valor);
    return strlen($valor) <= $longitud && preg_match($patron, $valor);
}

if ($esEdicion) {
    // Obtener datos del cliente para edición
    $id = (int)$_GET['id'];
    $sql = "SELECT c.*, e.nombre as nombre_empresa, e.rut as rut_empresa, e.direccion as direccion_empresa 
            FROM clientes c 
            LEFT JOIN empresas e ON c.id = e.cliente_id 
            WHERE c.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc();

    if (!$cliente) {
        $_SESSION['mensaje'] = "Cliente no encontrado.";
        $_SESSION['mensaje_tipo'] = "danger";
        header("Location: listar_clientes.php");
        exit();
    }

    // Asignar valores existentes
    $nombres = $cliente['nombres'];
    $apellidos = $cliente['apellidos'];
    $rut = !empty($cliente['rut']) ? $cliente['rut'] : '';
    $email = $cliente['correo'];
    $celular = $cliente['celular'];
    $genero = $cliente['genero'];
    $nombre_empresa = $cliente['nombre_empresa'];
    $rut_empresa = !empty($cliente['rut_empresa']) ? $cliente['rut_empresa'] : '';
    $direccion_empresa = $cliente['direccion_empresa'];
}

$errores = [];

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

    // Validaciones básicas
    if (!validarCampo($nombres, 20, '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,20}$/')) {
        $errores['nombres'] = "Nombre inválido. Solo letras, máximo 20 caracteres.";
    }
    if (!validarCampo($apellidos, 20, '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,20}$/')) {
        $errores['apellidos'] = "Apellidos inválidos. Solo letras, máximo 20 caracteres.";
    }

    // Validar y formatear RUT si se proporciona
    if (!empty($rut)) {
        $rutFormateado = formatearRut($rut);
        if (!validarRut($rutFormateado)) {
            $errores['rut'] = "Formato de RUT inválido.";
        }
    } else {
        $rutFormateado = null;
    }

    // Validar y formatear RUT de empresa si se proporciona
    if (!empty($rut_empresa)) {
        $rutEmpresaFormateado = formatearRut($rut_empresa);
        if (!validarRut($rutEmpresaFormateado)) {
            $errores['rut_empresa'] = "Formato de RUT de empresa inválido.";
        }
    } else {
        $rutEmpresaFormateado = null;
    }

    if (empty($errores)) {
        // Verificar si el RUT ya existe (solo si se proporcionó un RUT)
        if (!empty($rutFormateado)) {
            $check_rut_sql = "SELECT id FROM clientes WHERE rut = ? AND id != ?";
            $check_stmt = $conn->prepare($check_rut_sql);
            $idCheck = $esEdicion ? $id : 0;
            $check_stmt->bind_param("si", $rutFormateado, $idCheck);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $errores['rut'] = "Ya existe un cliente con ese RUT.";
            }
        }

        if (empty($errores)) {
            // Iniciar transacción
            $conn->begin_transaction();

            try {
                if ($esEdicion) {
                    // Actualizar cliente existente
                    $sql_cliente = "UPDATE clientes SET nombres=?, apellidos=?, rut=?, correo=?, celular=?, genero=? WHERE id=?";
                    $stmt_cliente = $conn->prepare($sql_cliente);
                    if ($stmt_cliente === false) {
                        throw new Exception("Error en la preparación de la consulta de actualización: " . $conn->error);
                    }
                    $stmt_cliente->bind_param("ssssssi", $nombres, $apellidos, $rutFormateado, $email, $celular, $genero, $id);
                    if (!$stmt_cliente->execute()) {
                        throw new Exception("Error al actualizar cliente: " . $stmt_cliente->error);
                    }

                    // Actualizar o insertar empresa
                    if (!empty($nombre_empresa)) {
                        // Verificar si ya existe una empresa para este cliente
                        $check_empresa_sql = "SELECT id FROM empresas WHERE cliente_id = ?";
                        $check_empresa_stmt = $conn->prepare($check_empresa_sql);
                        $check_empresa_stmt->bind_param("i", $id);
                        $check_empresa_stmt->execute();
                        $empresa_result = $check_empresa_stmt->get_result();

                        if ($empresa_result->num_rows > 0) {
                            // Actualizar empresa existente
                            $sql_empresa = "UPDATE empresas SET nombre=?, rut=?, direccion=? WHERE cliente_id=?";
                        } else {
                            // Insertar nueva empresa
                            $sql_empresa = "INSERT INTO empresas (nombre, rut, direccion, cliente_id) VALUES (?, ?, ?, ?)";
                        }

                        $stmt_empresa = $conn->prepare($sql_empresa);
                        if ($stmt_empresa === false) {
                            throw new Exception("Error en la preparación de la consulta de empresa: " . $conn->error);
                        }
                        $stmt_empresa->bind_param("sssi", $nombre_empresa, $rutEmpresaFormateado, $direccion_empresa, $id);
                        if (!$stmt_empresa->execute()) {
                            throw new Exception("Error al actualizar/insertar empresa: " . $stmt_empresa->error);
                        }
                    }
                } else {
                    // Insertar nuevo cliente
                    $sql_cliente = "INSERT INTO clientes (nombres, apellidos, rut, correo, celular, genero) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt_cliente = $conn->prepare($sql_cliente);
                    if ($stmt_cliente === false) {
                        throw new Exception("Error en la preparación de la consulta de cliente: " . $conn->error);
                    }
                    $stmt_cliente->bind_param("ssssss", $nombres, $apellidos, $rutFormateado, $email, $celular, $genero);
                    if (!$stmt_cliente->execute()) {
                        throw new Exception("Error al insertar cliente: " . $stmt_cliente->error);
                    }
                    $cliente_id = $conn->insert_id;

                    // Insertar empresa si se proporcionaron datos
                    if (!empty($nombre_empresa)) {
                        $sql_empresa = "INSERT INTO empresas (nombre, rut, direccion, cliente_id) VALUES (?, ?, ?, ?)";
                        $stmt_empresa = $conn->prepare($sql_empresa);
                        if ($stmt_empresa === false) {
                            throw new Exception("Error en la preparación de la consulta de empresa: " . $conn->error);
                        }
                        $stmt_empresa->bind_param("sssi", $nombre_empresa, $rutEmpresaFormateado, $direccion_empresa, $cliente_id);
                        if (!$stmt_empresa->execute()) {
                            throw new Exception("Error al insertar empresa: " . $stmt_empresa->error);
                        }
                    }
                }

                // Confirmar transacción
                $conn->commit();

                $_SESSION['mensaje'] = $esEdicion ? "Cliente actualizado con éxito." : "Cliente agregado con éxito.";
                $_SESSION['mensaje_tipo'] = "success";
                header("Location: listar_clientes.php");
                exit();
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $conn->rollback();
                $mensaje = "Error al " . ($esEdicion ? "actualizar" : "agregar") . " el cliente: " . $e->getMessage();
                $mensaje_tipo = "danger";
                error_log("Error en ingreso_cliente.php: " . $e->getMessage());
            }
        }
    }
}

// Cerrar la conexión después de obtener los datos necesarios
$conn->close();
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

        /* Agregar dentro de la sección <style> existente */
        input[name="celular"].is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .error-message {
            display: none;
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
    </style>
</head>

<body class="mini-sidebar">
    <!-- Main-Wrapper -->
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
                            <h3 class="box-title m-b-0"><?php echo $esEdicion ? 'Editar Cliente' : 'Ingresar Nuevo Cliente'; ?></h3>
                            <p class="text-muted m-b-30 font-13">Información del Cliente y Empresa o Municipalidad</p>
                            <form id="clienteForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($esEdicion ? "?id=" . $id : "")); ?>" class="form-horizontal">
                                <div class="form-body">
                                    <h3 class="box-title">Información Personal</h3>
                                    <hr class="m-t-0 m-b-40">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">Nombres</label>
                                                <div class="col-md-9">
                                                    <input type="text" class="form-control" name="nombres" id="nombres"
                                                        value="<?php echo htmlspecialchars($nombres); ?>" maxlength="20" required>
                                                    <span class="error-message" id="nombresError">
                                                        <?php echo isset($errores['nombres']) ? $errores['nombres'] : ''; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">Apellidos</label>
                                                <div class="col-md-9">
                                                    <input type="text" class="form-control" name="apellidos" id="apellidos"
                                                        value="<?php echo htmlspecialchars($apellidos); ?>" maxlength="20" required>
                                                    <span class="error-message" id="apellidosError">
                                                        <?php echo isset($errores['apellidos']) ? $errores['apellidos'] : ''; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">RUT</label>
                                                <div class="col-md-9">
                                                    <input type="text" class="form-control" name="rut" id="rut"
                                                        value="<?php echo htmlspecialchars($rut); ?>" maxlength="12">
                                                    <span class="error-message" id="rutError">
                                                        <?php echo isset($errores['rut']) ? $errores['rut'] : ''; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">Correo Electrónico</label>
                                                <div class="col-md-9">
                                                    <input type="email" class="form-control" name="email"
                                                        value="<?php echo htmlspecialchars($email); ?>" maxlength="60">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">Celular</label>
                                                <div class="col-md-9">
                                                    <input type="tel" class="form-control" name="celular"
                                                        value="<?php echo htmlspecialchars($celular); ?>" maxlength="16">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">Género</label>
                                                <div class="col-md-9">
                                                    <select class="form-control" name="genero" required>
                                                        <option value="">Seleccione un género</option>
                                                        <option value="Masculino" <?php echo $genero === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                                                        <option value="Femenino" <?php echo $genero === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
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
                                                    <input type="text" class="form-control" name="nombre_empresa"
                                                        value="<?php echo htmlspecialchars($nombre_empresa); ?>" maxlength="100">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">RUT Empresa o Muni</label>
                                                <div class="col-md-9">
                                                    <input type="text" class="form-control" name="rut_empresa" id="rut_empresa"
                                                        value="<?php echo htmlspecialchars($rut_empresa); ?>" maxlength="12">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label col-md-3">Dirección Empresa o Muni</label>
                                                <div class="col-md-9">
                                                    <input type="text" class="form-control" name="direccion_empresa"
                                                        value="<?php echo htmlspecialchars($direccion_empresa); ?>" maxlength="250">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <div class="row">
                                        <div class="col-md-12 text-center">
                                            <button type="submit" class="btn btn-success" id="submitBtn">
                                                <i class="fa fa-check"></i> <?php echo $esEdicion ? 'Actualizar' : 'Guardar'; ?>
                                            </button>
                                            <a href="listar_clientes.php" class="btn btn-default">Cancelar</a>
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
    </div>

    <script>
        $(document).ready(function() {
            // Constantes para las expresiones regulares
            const REGEX = {
                nombres: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,20}$/,
                rut: /^[0-9]{1,2}\.[0-9]{3}\.[0-9]{3}-[0-9Kk]$/
            };

            // Función para validar campos
            function validarCampo(campo, regex, errorMsg) {
                const $campo = $(campo);
                const $errorSpan = $(`${campo}Error`);
                const valor = $campo.val().trim();
                const esValido = valor === '' || regex.test(valor);

                $errorSpan.text(esValido ? '' : errorMsg)[esValido ? 'hide' : 'show']();
                $campo[esValido ? 'removeClass' : 'addClass']('is-invalid');

                return esValido;
            }

            // Función mejorada para formatear RUT
            function formatearRut(rut) {
                // Limpiar el RUT de caracteres no deseados y convertir a minúsculas
                let valor = rut.replace(/[^0-9kK\-\.]/g, '').toLowerCase();

                // Si está vacío, retornar vacío
                if (!valor) return '';

                // Obtener números y dígito verificador
                let rutLimpio = valor.replace(/[\.-]/g, '');
                if (rutLimpio.length < 2) return valor;

                const dv = rutLimpio.slice(-1);
                let rutNumeros = rutLimpio.slice(0, -1);

                // Formatear el RUT con puntos
                let rutFormateado = '';
                while (rutNumeros.length > 3) {
                    rutFormateado = '.' + rutNumeros.slice(-3) + rutFormateado;
                    rutNumeros = rutNumeros.slice(0, -3);
                }
                rutFormateado = rutNumeros + rutFormateado + '-' + dv;

                return rutFormateado;
            }

            // Manejar el formato del RUT mientras se escribe
            $('#rut, #rut_empresa').on('input', function(e) {
                const $this = $(this);
                const cursorPos = this.selectionStart;
                const valorAnterior = $this.val();
                const valorFormateado = formatearRut(valorAnterior);

                if (valorAnterior !== valorFormateado) {
                    $this.val(valorFormateado);

                    // Calcular la nueva posición del cursor
                    const diff = valorFormateado.length - valorAnterior.length;
                    const newPos = cursorPos + diff;

                    // Establecer la nueva posición del cursor
                    if (this.setSelectionRange) {
                        setTimeout(() => this.setSelectionRange(newPos, newPos), 0);
                    }
                }

                // Validar el formato si es el campo de RUT principal
                if ($this.attr('id') === 'rut') {
                    validarCampo('#rut', REGEX.rut, 'Formato de RUT inválido (ej: 12.345.678-9)');
                }
            });

            // Validar el formulario antes de enviar
            $('#clienteForm').on('submit', function(e) {
                let isValid = true;

                // Validar nombres y apellidos
                const nombreValido = validarCampo('#nombres', REGEX.nombres, 'Ingrese solo letras (máximo 20 caracteres)');
                const apellidoValido = validarCampo('#apellidos', REGEX.nombres, 'Ingrese solo letras (máximo 20 caracteres)');

                // Validar RUT solo si se ha ingresado
                const rutValor = $('#rut').val().trim();
                const rutValido = rutValor === '' || validarCampo('#rut', REGEX.rut, 'Formato de RUT inválido (ej: 12.345.678-9)');

                // Validar RUT de empresa solo si se ha ingresado nombre de empresa
                const nombreEmpresa = $('#nombre_empresa').val().trim();
                const rutEmpresa = $('#rut_empresa').val().trim();
                let rutEmpresaValido = true;

                if (nombreEmpresa && rutEmpresa) {
                    rutEmpresaValido = validarCampo('#rut_empresa', REGEX.rut, 'Formato de RUT de empresa inválido');
                }

                isValid = nombreValido && apellidoValido && rutValido && rutEmpresaValido;

                if (!isValid) {
                    e.preventDefault();
                    // Mostrar mensaje de error general
                    mostrarMensajeError('Por favor, corrija los errores en el formulario.');
                }
            });

            // Validación en tiempo real para nombres y apellidos
            $('#nombres, #apellidos').on('input', function() {
                const campo = '#' + $(this).attr('id');
                validarCampo(campo, REGEX.nombres, 'Ingrese solo letras (máximo 20 caracteres)');
            });

            // Función para mostrar mensaje de error general
            function mostrarMensajeError(mensaje) {
                const $alertaError = $('<div>')
                    .addClass('alert alert-danger alert-dismissible fade show mt-3')
                    .attr('role', 'alert')
                    .html(`
                ${mensaje}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            `);

                // Insertar el mensaje al principio del formulario
                $('#clienteForm').prepend($alertaError);

                // Autodesaparecer después de 5 segundos
                setTimeout(() => {
                    $alertaError.alert('close');
                }, 5000);
            }
            // Agregar dentro del $(document).ready(function() { ... })

            // Constante para el formato del teléfono
            const PHONE_REGEX = /^\+569\d{8}$/;

            // Función para formatear número de teléfono
            function formatPhoneNumber(phone) {
                // Eliminar todos los caracteres no numéricos
                let cleaned = phone.replace(/\D/g, '');

                // Si está vacío, retornar vacío
                if (!cleaned) return '';

                // Si no empieza con 56, agregarlo
                if (!cleaned.startsWith('56')) {
                    cleaned = '56' + cleaned;
                }

                // Asegurarse de que tenga el formato correcto
                if (cleaned.length >= 11) {
                    return '+' + cleaned.substring(0, 11);
                }

                // Si no tiene suficientes números, solo agregar el '+'
                return '+' + cleaned;
            }

            // Manejar el campo de teléfono
            $('input[name="celular"]').on('input', function(e) {
                const $input = $(this);
                const cursorPos = this.selectionStart;
                const valorAnterior = $input.val();
                const valorFormateado = formatPhoneNumber(valorAnterior);

                // Solo actualizar si el valor ha cambiado
                if (valorAnterior !== valorFormateado) {
                    $input.val(valorFormateado);

                    // Mantener el cursor en la posición correcta
                    if (this.setSelectionRange) {
                        const newPos = cursorPos + (valorFormateado.length - valorAnterior.length);
                        this.setSelectionRange(newPos, newPos);
                    }
                }

                // Validar el formato
                validarTelefono($input);
            });

            // Función para validar el teléfono
            function validarTelefono($input) {
                const valor = $input.val().trim();
                const esValido = valor === '' || PHONE_REGEX.test(valor);

                // Agregar o remover clase de error
                $input.toggleClass('is-invalid', !esValido);

                // Mostrar u ocultar mensaje de error
                let $errorSpan = $input.siblings('.error-message');
                if ($errorSpan.length === 0) {
                    $errorSpan = $('<span class="error-message">').insertAfter($input);
                }

                $errorSpan.text(esValido ? '' : 'Ingrese un número válido en formato +56912345678')
                    .toggle(!esValido);

                return esValido;
            }

            // Agregar validación del teléfono al envío del formulario
            const formValidationOriginal = $('#clienteForm').prop('onsubmit');
            $('#clienteForm').on('submit', function(e) {
                const telefonoValido = validarTelefono($('input[name="celular"]'));
                if (!telefonoValido) {
                    e.preventDefault();
                    mostrarMensajeError('Por favor, corrija el formato del número de teléfono.');
                } else if (formValidationOriginal) {
                    // Llamar a la validación original si existe
                    formValidationOriginal.call(this, e);
                }
            });
        });
    </script>

</body>

</html>