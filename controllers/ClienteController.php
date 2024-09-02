<?php
require_once 'models/Cliente.php';

class ClienteController {
    private $clienteModel;

    public function __construct() {
        $this->clienteModel = new Cliente();
    }

    public function listClientes() {
        $page = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        $clientes = $this->clienteModel->getClientes($page, $search);
        $totalClientes = $this->clienteModel->getTotalClientes();
        $totalPaginas = ceil($totalClientes / RESULTADOS_POR_PAGINA);

        require 'views/cliente/list.php';
    }

    public function createCliente() {
        // Lógica para crear un nuevo cliente
    }

    public function editCliente() {
        // Lógica para editar un cliente
    }

    public function deleteCliente() {
        // Lógica para eliminar un cliente
    }
}