<?php
$password = '8787';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "Hash de la contraseña: " . $hashed_password . "\n";

$sql = "INSERT INTO usuarios (username, password, nombre, email) VALUES ('admin', '" . $hashed_password . "', 'miguel', 'nuevo_admin@example.com');";

echo "SQL para insertar el nuevo usuario:\n" . $sql;
?>