<?php
$servername = "localhost";
$username = "schaafpr_admin";
$password = "entrar-03";
//$dbname = "schaaf_producciones";
$dbname = "schaafpr_bd";


// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Datos del cliente
    function validarCampo($campo, $nombreCampo) {
        $valor = trim($_POST[$campo]);
        if (empty($valor) || strlen($valor) > 20 || !preg_match("/^[A-Za-zñÑáéíóúÁÉÍÓÚ\s]+$/u", $valor)) {
            die("Error: El campo $nombreCampo debe contener solo letras y espacios, máximo 30 caracteres.");
        }
        return $valor;
    }
    // Validación del nombre y apellido
    $nombres = validarCampo('nombres', 'Nombres');
    $apellidos = validarCampo('apellidos', 'Apellidos');
 
    $apellidos = $_POST['apellidos'];
    $rut = $_POST['rut'];
    $correo = $_POST['email'];
    $celular = $_POST['celular'];
    $genero = $_POST['genero'];

    // Datos de la empresa
    $nombre_empresa = $_POST['company_name'];
    $rut_empresa = $_POST['company_rut'];
    $direccion_empresa = $_POST['company_address'];

    // Datos del evento
    $nombre_evento = $_POST['nombre_evento'];
    $lugar_evento = $_POST['lugar_evento'];
    $fecha_evento = $_POST['fecha_evento'];
    $hora_evento = $_POST['hora_evento'];

    // Insertar cliente
    $stmt = $conn->prepare("INSERT INTO clientes (nombres, apellidos, rut, correo, celular, genero) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nombres, $apellidos, $rut, $correo, $celular, $genero);

    if ($stmt->execute()) {
        $cliente_id = $conn->insert_id;

        // Insertar empresa
        $stmt = $conn->prepare("INSERT INTO empresas (nombre, rut, direccion, cliente_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $nombre_empresa, $rut_empresa, $direccion_empresa, $cliente_id);
        $stmt->execute();

        // Insertar evento
        $stmt = $conn->prepare("INSERT INTO eventos (cliente_id, nombre_evento, fecha_evento, hora_evento, lugar) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $cliente_id, $nombre_evento, $fecha_evento, $hora_evento, $lugar_evento);
        
        if ($stmt->execute()) {
            header("Location: gracias.html");
            exit();
        } else {
            echo "Error al insertar el evento: " . $stmt->error;
        }
    } else {
        echo "Error al insertar el cliente: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Método de solicitud no válido";
}

$conn->close();
?>