<?php
require_once __DIR__ . '/../config/config.php';

class Cliente {
    private $db;

    public function __construct() {
        $this->db = getDBConnection();
    }

    public function getClientes($page = 1, $search = '') {
        $limit = RESULTADOS_POR_PAGINA;
        $offset = ($page - 1) * $limit;
        
        $where = '';
        if (!empty($search)) {
            $search = $this->db->real_escape_string($search);
            $where = "WHERE c.nombres LIKE '%$search%' OR c.apellidos LIKE '%$search%' OR c.rut LIKE '%$search%' OR e.nombre LIKE '%$search%'";
        }

        $sql = "SELECT c.*, e.nombre as nombre_empresa 
                FROM clientes c 
                LEFT JOIN empresas e ON c.id = e.cliente_id 
                $where 
                LIMIT $offset, $limit";
        
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getTotalClientes() {
        $sql = "SELECT COUNT(*) as total FROM clientes";
        $result = $this->db->query($sql);
        return $result->fetch_assoc()['total'];
    }

    public function getClienteById($id) {
        $id = $this->db->real_escape_string($id);
        $sql = "SELECT * FROM clientes WHERE id = $id";
        $result = $this->db->query($sql);
        return $result->fetch_assoc();
    }

    public function createCliente($data) {
        $nombres = $this->db->real_escape_string($data['nombres']);
        $apellidos = $this->db->real_escape_string($data['apellidos']);
        $rut = $this->db->real_escape_string($data['rut']);
        $correo = $this->db->real_escape_string($data['correo']);
        $celular = $this->db->real_escape_string($data['celular']);
        $genero = $this->db->real_escape_string($data['genero']);

        $sql = "INSERT INTO clientes (nombres, apellidos, rut, correo, celular, genero) 
                VALUES ('$nombres', '$apellidos', '$rut', '$correo', '$celular', '$genero')";
        
        if ($this->db->query($sql)) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updateCliente($id, $data) {
        $id = $this->db->real_escape_string($id);
        $nombres = $this->db->real_escape_string($data['nombres']);
        $apellidos = $this->db->real_escape_string($data['apellidos']);
        $rut = $this->db->real_escape_string($data['rut']);
        $correo = $this->db->real_escape_string($data['correo']);
        $celular = $this->db->real_escape_string($data['celular']);
        $genero = $this->db->real_escape_string($data['genero']);

        $sql = "UPDATE clientes 
                SET nombres = '$nombres', apellidos = '$apellidos', rut = '$rut', 
                    correo = '$correo', celular = '$celular', genero = '$genero' 
                WHERE id = $id";
        
        return $this->db->query($sql);
    }

    public function deleteCliente($id) {
        $id = $this->db->real_escape_string($id);
        $sql = "DELETE FROM clientes WHERE id = $id";
        return $this->db->query($sql);
    }

    public function getEmpresaByClienteId($clienteId) {
        $clienteId = $this->db->real_escape_string($clienteId);
        $sql = "SELECT * FROM empresas WHERE cliente_id = $clienteId";
        $result = $this->db->query($sql);
        return $result->fetch_assoc();
    }

    public function getEventosByClienteId($clienteId) {
        $clienteId = $this->db->real_escape_string($clienteId);
        $sql = "SELECT * FROM eventos WHERE cliente_id = $clienteId ORDER BY fecha_evento DESC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}