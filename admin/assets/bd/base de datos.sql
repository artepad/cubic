-- Eliminar tablas existentes si es necesario
DROP TABLE IF EXISTS eventos;
DROP TABLE IF EXISTS empresas;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS giras;

-- Crear tabla clientes (sin cambios)
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    rut VARCHAR(12) NOT NULL UNIQUE,
    correo VARCHAR(100) NOT NULL,
    celular VARCHAR(15) NOT NULL,
    genero ENUM('Masculino', 'Femenino', 'Otro') NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla empresas (sin cambios)
CREATE TABLE empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    rut VARCHAR(12) NOT NULL UNIQUE,
    direccion VARCHAR(255) NOT NULL,
    cliente_id INT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

-- Crear tabla usuarios (sin cambios)
CREATE TABLE usuarios (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- Nueva tabla: giras
CREATE TABLE giras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    fecha_inicio DATE,
    fecha_fin DATE,
    descripcion TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Modificar tabla eventos para incluir referencia a giras
CREATE TABLE eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    gira_id INT,
    nombre_evento VARCHAR(255) NOT NULL,
    fecha_evento DATE NOT NULL,
    hora_evento TIME NOT NULL,
    lugar VARCHAR(255) NOT NULL,
    valor INT,
    tipo_evento VARCHAR(100),
    descripcion TEXT,
    estado VARCHAR(20) DEFAULT 'Pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    hotel ENUM('Si', 'No') DEFAULT 'No',
    traslados ENUM('Si', 'No') DEFAULT 'No',
    viaticos ENUM('Si', 'No') DEFAULT 'No',
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (gira_id) REFERENCES giras(id) ON DELETE SET NULL
);

-- Índices adicionales para mejorar el rendimiento
CREATE INDEX idx_cliente_rut ON clientes(rut);
CREATE INDEX idx_empresa_rut ON empresas(rut);
CREATE INDEX idx_evento_fecha ON eventos(fecha_evento);
CREATE INDEX idx_evento_fecha_hora ON eventos(fecha_evento, hora_evento);
CREATE INDEX idx_gira_fechas ON giras(fecha_inicio, fecha_fin);