<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "schaaf_producciones";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Función de validación mejorada
    function validarCampo($campo, $nombreCampo, $maxLength, $regex) {
        $valor = trim($_POST[$campo]);
        if (empty($valor) || strlen($valor) > $maxLength || !preg_match($regex, $valor)) {
            die("Error: El campo $nombreCampo no cumple con los requisitos.");
        }
        return $valor;
    }

    // Función específica para validar RUT
    function validarRut($rut) {
        $rut = preg_replace('/[^k0-9]/i', '', $rut);
        $dv  = substr($rut, -1);
        $numero = substr($rut, 0, strlen($rut)-1);
        $i = 2;
        $suma = 0;
        foreach(array_reverse(str_split($numero)) as $v) {
            if($i==8) $i = 2;
            $suma += $v * $i;
            ++$i;
        }
        $dvr = 11 - ($suma % 11);
        
        if($dvr == 11) $dvr = 0;
        if($dvr == 10) $dvr = 'K';
        if($dvr == strtoupper($dv))
            return true;
        else
            return false;
    }

    // Validación de los campos
    $nombres = validarCampo('nombres', 'Nombres', 20, "/^[A-Za-zñÑáéíóúÁÉÍÓÚ\s]+$/u");
    $apellidos = validarCampo('apellidos', 'Apellidos', 20, "/^[A-Za-zñÑáéíóúÁÉÍÓÚ\s]+$/u");
    
    // Validación del RUT
    $rut = $_POST['rut'];
    if (!validarRut($rut)) {
        die("Error: El RUT ingresado no es válido.");
    }
    
    $correo = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if (!$correo) {
        die("Error: El correo electrónico no es válido.");
    }
    $celular = validarCampo('celular', 'Celular', 15, "/^\+56 9 \d{4} \d{4}$/");
    $genero = $_POST['genero'];

    // Validación de los datos de la empresa
    $nombre_empresa = validarCampo('company_name', 'Nombre de la Empresa', 40, "/^[A-Za-z0-9\s]+$/");
    
    // Validación del RUT de la empresa
    $rut_empresa = $_POST['company_rut'];
    if (!validarRut($rut_empresa)) {
        die("Error: El RUT de la empresa ingresado no es válido.");
    }
    
    $direccion_empresa = validarCampo('company_address', 'Dirección de la Empresa', 150, "/^[A-Za-z0-9\s]+$/");

    // Validación de los datos del evento
    $nombre_evento = validarCampo('nombre_evento', 'Nombre del Evento', 100, "/^[A-Za-z0-9\s]+$/");
    $lugar_evento = validarCampo('lugar_evento', 'Lugar del Evento', 100, "/^[A-Za-z0-9\s]+$/");
    $fecha_evento = $_POST['fecha_evento'];
    $hora_evento = $_POST['hora_evento'];


    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Insertar cliente
        $stmt = $conn->prepare("INSERT INTO clientes (nombres, apellidos, rut, correo, celular, genero) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $nombres, $apellidos, $rut, $correo, $celular, $genero);
        $stmt->execute();
        $cliente_id = $conn->insert_id;

        // Insertar empresa
        $stmt = $conn->prepare("INSERT INTO empresas (nombre, rut, direccion, cliente_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $nombre_empresa, $rut_empresa, $direccion_empresa, $cliente_id);
        $stmt->execute();

        // Insertar evento
        $stmt = $conn->prepare("INSERT INTO eventos (cliente_id, nombre_evento, fecha_evento, hora_evento, lugar) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $cliente_id, $nombre_evento, $fecha_evento, $hora_evento, $lugar_evento);
        $stmt->execute();

        // Confirmar transacción
        $conn->commit();

        // Redireccionar a la página de agradecimiento
        header("Location: gracias.html");
        exit();
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }

    $stmt->close();
} else {
    echo "Método de solicitud no válido";
}

$conn->close();
?>