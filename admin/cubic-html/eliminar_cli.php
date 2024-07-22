<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Conectar a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "schaaf_producciones";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "DELETE FROM clientes WHERE id = $id";
    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = "Cliente eliminado correctamente.";
    } else {
        $_SESSION['message'] = "Error al eliminar el cliente: " . $conn->error;
    }
}

$conn->close();
header("location: index.php");
exit;
?>