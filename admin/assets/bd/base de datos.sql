-- Eliminar tablas existentes si es necesario (en orden inverso a las dependencias)
DROP TABLE IF EXISTS eventos;
DROP TABLE IF EXISTS artistas;
DROP TABLE IF EXISTS empresas;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS giras;
DROP TABLE IF EXISTS usuarios;

-- --------------------------------------------------------
-- Tabla: usuarios
-- Descripción: Almacena los usuarios del sistema con sus credenciales
-- --------------------------------------------------------
CREATE TABLE usuarios (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT 'Nombre de usuario para login',
    password VARCHAR(255) NOT NULL COMMENT 'Contraseña encriptada',
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre completo del usuario',
    email VARCHAR(100) NOT NULL UNIQUE COMMENT 'Correo electrónico del usuario',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del usuario',
    PRIMARY KEY (id)
) ENGINE=InnoDB COMMENT='Tabla de usuarios del sistema';

-- --------------------------------------------------------
-- Tabla: clientes
-- Descripción: Almacena la información de los clientes
-- --------------------------------------------------------
CREATE TABLE clientes (
    id INT AUTO_INCREMENT,
    nombres VARCHAR(100) NOT NULL COMMENT 'Nombres del cliente',
    apellidos VARCHAR(100) NOT NULL COMMENT 'Apellidos del cliente',
    rut VARCHAR(12) NOT NULL UNIQUE COMMENT 'RUT del cliente en formato XX.XXX.XXX-X',
    correo VARCHAR(100) NOT NULL COMMENT 'Correo electrónico del cliente',
    celular VARCHAR(15) NOT NULL COMMENT 'Número de celular del cliente',
    genero ENUM('Masculino', 'Femenino', 'Otro') NOT NULL COMMENT 'Género del cliente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de registro del cliente',
    PRIMARY KEY (id),
    INDEX idx_cliente_rut (rut) COMMENT 'Índice para búsquedas por RUT'
) ENGINE=InnoDB COMMENT='Tabla de información de clientes';

-- --------------------------------------------------------
-- Tabla: empresas
-- Descripción: Almacena la información de las empresas asociadas a los clientes
-- --------------------------------------------------------
CREATE TABLE empresas (
    id INT AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre de la empresa',
    rut VARCHAR(12) NOT NULL UNIQUE COMMENT 'RUT de la empresa en formato XX.XXX.XXX-X',
    direccion VARCHAR(255) NOT NULL COMMENT 'Dirección de la empresa',
    cliente_id INT COMMENT 'ID del cliente asociado',
    PRIMARY KEY (id),
    INDEX idx_empresa_rut (rut) COMMENT 'Índice para búsquedas por RUT',
    CONSTRAINT fk_empresa_cliente 
        FOREIGN KEY (cliente_id) 
        REFERENCES clientes(id) 
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Tabla de empresas asociadas a clientes';

-- --------------------------------------------------------
-- Tabla: giras
-- Descripción: Almacena información de las giras artísticas
-- --------------------------------------------------------
CREATE TABLE giras (
    id INT AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL COMMENT 'Nombre de la gira',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación de la gira',
    PRIMARY KEY (id),
    INDEX idx_gira_nombre (nombre) COMMENT 'Índice para búsquedas por nombre de gira'
) ENGINE=InnoDB COMMENT='Tabla de giras artísticas';

-- --------------------------------------------------------
-- Tabla: artistas
-- Descripción: Almacena información de los artistas y sus materiales
-- --------------------------------------------------------
CREATE TABLE artistas (
    id INT AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre del artista',
    descripcion TEXT COMMENT 'Descripción detallada del artista',
    genero_musical VARCHAR(50) NOT NULL COMMENT 'Género musical principal del artista',
    presentacion TEXT COMMENT 'Descripción de la presentación del artista',
    logo MEDIUMBLOB COMMENT 'Logo del artista (imagen hasta 16MB)',
    logo_tipo VARCHAR(50) COMMENT 'Tipo MIME del logo (ej: image/jpeg)',
    imagen_presentacion MEDIUMBLOB COMMENT 'Imagen de presentación del artista',
    imagen_presentacion_tipo VARCHAR(50) COMMENT 'Tipo MIME de la imagen de presentación',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de registro del artista',
    PRIMARY KEY (id),
    INDEX idx_artista_nombre (nombre) COMMENT 'Índice para búsquedas por nombre de artista'
) ENGINE=InnoDB COMMENT='Tabla de información de artistas';

-- --------------------------------------------------------
-- Tabla: eventos
-- Descripción: Almacena la información de los eventos
-- --------------------------------------------------------
CREATE TABLE eventos (
    id INT AUTO_INCREMENT,
    cliente_id INT COMMENT 'ID del cliente que solicita el evento',
    gira_id INT COMMENT 'ID de la gira asociada',
    artista_id INT COMMENT 'ID del artista que se presenta',
    nombre_evento VARCHAR(255) NOT NULL COMMENT 'Nombre del evento',
    fecha_evento DATE NOT NULL COMMENT 'Fecha programada del evento',
    hora_evento TIME NOT NULL COMMENT 'Hora programada del evento',
    ciudad_evento VARCHAR(100) COMMENT 'Ciudad donde se realiza el evento',
    lugar_evento VARCHAR(255) NOT NULL COMMENT 'Lugar específico del evento',
    valor_evento INT COMMENT 'Valor monetario del evento',
    tipo_evento VARCHAR(100) COMMENT 'Tipo o categoría del evento',
    encabezado_evento VARCHAR(255) COMMENT 'Encabezado o título promocional del evento',
    estado_evento ENUM(
        'Propuesta', 
        'Confirmado', 
        'Documentación', 
        'En Producción', 
        'Finalizado', 
        'Reagendado', 
        'Cancelado'
    ) DEFAULT 'Propuesta' COMMENT 'Estado actual del evento',
    hotel ENUM('Si', 'No') DEFAULT 'No' COMMENT 'Indica si incluye hotel',
    traslados ENUM('Si', 'No') DEFAULT 'No' COMMENT 'Indica si incluye traslados',
    viaticos ENUM('Si', 'No') DEFAULT 'No' COMMENT 'Indica si incluye viáticos',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del evento',
    PRIMARY KEY (id),
    INDEX idx_evento_fecha (fecha_evento) COMMENT 'Índice para búsquedas por fecha',
    INDEX idx_evento_fecha_hora (fecha_evento, hora_evento) COMMENT 'Índice compuesto para búsquedas por fecha y hora',
    CONSTRAINT fk_evento_cliente 
        FOREIGN KEY (cliente_id) 
        REFERENCES clientes(id) 
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_evento_gira 
        FOREIGN KEY (gira_id) 
        REFERENCES giras(id) 
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_evento_artista 
        FOREIGN KEY (artista_id) 
        REFERENCES artistas(id) 
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB COMMENT='Tabla principal de eventos';

-- Agregar comentario general de la base de datos
-- Este script crea una base de datos para un sistema de gestión de eventos artísticos
-- que maneja clientes, empresas, artistas, giras y eventos.
-- Incluye relaciones entre todas las entidades y manejo de archivos binarios para imágenes.
-- Última actualización: 13/11/2024