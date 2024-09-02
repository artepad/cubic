<?php
session_start();
require_once 'config/config.php';
require_once 'helpers/auth_helper.php';
require_once 'controllers/ClienteController.php';

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    header("location: login.php");
    exit;
}

// Enrutamiento básico
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

$controller = new ClienteController();

switch ($action) {
    case 'list':
        $controller->listClientes();
        break;
    case 'create':
        $controller->createCliente();
        break;
    case 'edit':
        $controller->editCliente();
        break;
    case 'delete':
        $controller->deleteCliente();
        break;
    default:
        $controller->listClientes();
        break;
}