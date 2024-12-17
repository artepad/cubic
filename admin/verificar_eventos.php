<?php
require_once 'config/config.php';
require_once 'functions/functions.php';

$conn = getDbConnection();

$sql = "SELECT COUNT(*) as total FROM eventos";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo "Total de eventos en la base de datos: " . $row['total'];

$sql = "SELECT * FROM eventos LIMIT 5";
$result = $conn->query($sql);

echo "\n\nPrimeros 5 eventos:\n";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

$conn->close();