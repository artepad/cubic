-- ========================================
-- Creación de la base de datos
-- ========================================
CREATE DATABASE IF NOT EXISTS schaaf_producciones 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_general_ci;

USE schaaf_producciones;

-- Eliminar tablas existentes si es necesario (en orden inverso a las dependencias)
DROP TABLE IF EXISTS evento_archivos;
DROP TABLE IF EXISTS eventos;
DROP TABLE IF EXISTS artistas;
DROP TABLE IF EXISTS empresas;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS giras;
DROP TABLE IF EXISTS usuarios;

-- ========================================
-- Tabla: usuarios
-- Descripción: Almacena los usuarios del sistema con sus credenciales
-- ========================================
CREATE TABLE usuarios (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT 'Nombre de usuario para login',
    password VARCHAR(255) NOT NULL COMMENT 'Contraseña encriptada',
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre completo del usuario',
    email VARCHAR(100) NOT NULL UNIQUE COMMENT 'Correo electrónico del usuario',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del usuario',
    PRIMARY KEY (id)
) ENGINE=InnoDB COMMENT='Tabla de usuarios del sistema';

-- ========================================
-- Tabla: clientes
-- Descripción: Almacena la información de los clientes
-- ========================================
CREATE TABLE clientes (
    id INT AUTO_INCREMENT,
    nombres VARCHAR(100) NOT NULL COMMENT 'Nombres del cliente',
    apellidos VARCHAR(100) NOT NULL COMMENT 'Apellidos del cliente',
    rut VARCHAR(12) NULL COMMENT 'RUT del cliente en formato XX.XXX.XXX-X (Opcional)',
    correo VARCHAR(100) NULL COMMENT 'Correo electrónico del cliente (Opcional)',
    celular VARCHAR(15) NULL COMMENT 'Número de celular del cliente (Opcional)',
    genero ENUM('Masculino', 'Femenino', 'Otro') NOT NULL COMMENT 'Género del cliente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de registro del cliente',
    PRIMARY KEY (id),
    INDEX idx_cliente_rut (rut) COMMENT 'Índice para búsquedas por RUT'
) ENGINE=InnoDB COMMENT='Tabla de información de clientes';

-- ========================================
-- Tabla: empresas
-- Descripción: Almacena la información de las empresas asociadas a los clientes
-- ========================================
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

-- ========================================
-- Tabla: giras
-- Descripción: Almacena información de las giras artísticas
-- ========================================
CREATE TABLE giras (
    id INT AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL COMMENT 'Nombre de la gira',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación de la gira',
    PRIMARY KEY (id),
    INDEX idx_gira_nombre (nombre) COMMENT 'Índice para búsquedas por nombre de gira'
) ENGINE=InnoDB COMMENT='Tabla de giras artísticas';

-- ========================================
-- Tabla: artistas
-- Descripción: Almacena información de los artistas
-- ========================================
CREATE TABLE artistas (
    id INT AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre del artista',
    descripcion TEXT COMMENT 'Descripción detallada del artista',
    presentacion TEXT COMMENT 'Presentación para cotizaciones',
    genero_musical VARCHAR(50) NOT NULL COMMENT 'Género musical principal del artista',
    imagen_presentacion VARCHAR(255) COMMENT 'Ruta de la imagen de presentación',
    logo_artista VARCHAR(255) COMMENT 'Ruta del logo del artista',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de registro del artista',
    PRIMARY KEY (id),
    INDEX idx_artista_nombre (nombre) COMMENT 'Índice para búsquedas por nombre de artista'
) ENGINE=InnoDB COMMENT='Tabla de información de artistas';

-- ========================================
-- Tabla: eventos
-- Descripción: Almacena la información de los eventos
-- ========================================
CREATE TABLE eventos (
    id INT AUTO_INCREMENT,
    cliente_id INT COMMENT 'ID del cliente que solicita el evento',
    gira_id INT COMMENT 'ID de la gira asociada',
    artista_id INT COMMENT 'ID del artista que se presenta',
    nombre_evento VARCHAR(255) NOT NULL COMMENT 'Nombre del evento',
    fecha_evento DATE NOT NULL COMMENT 'Fecha programada del evento',
    hora_evento TIME  NULL COMMENT 'Hora programada del evento',
    ciudad_evento VARCHAR(100) COMMENT 'Ciudad donde se realiza el evento',
    lugar_evento VARCHAR(255) COMMENT 'Lugar específico del evento',
    valor_evento INT COMMENT 'Valor monetario del evento',
    tipo_evento VARCHAR(100) COMMENT 'Tipo o categoría del evento',
    encabezado_evento VARCHAR(255) COMMENT 'Encabezado o título promocional del evento',
    estado_evento ENUM('Propuesta', 'Confirmado', 'Finalizado', 'Reagendado', 'Cancelado') 
        NOT NULL DEFAULT 'Propuesta' COMMENT 'Estado actual del evento',
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

-- ========================================
-- Tabla: evento_archivos
-- Descripción: Almacena referencias a archivos asociados a eventos
-- ========================================
CREATE TABLE evento_archivos (
    id INT AUTO_INCREMENT,
    evento_id INT NOT NULL,
    nombre_original VARCHAR(255) NOT NULL COMMENT 'Nombre original del archivo',
    nombre_archivo VARCHAR(255) NOT NULL COMMENT 'Nombre del archivo en el sistema',
    tipo_archivo VARCHAR(100) NOT NULL COMMENT 'Tipo MIME del archivo',
    tamano INT NOT NULL COMMENT 'Tamaño del archivo en bytes',
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de subida del archivo',
    PRIMARY KEY (id),
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    INDEX idx_evento_archivos (evento_id) COMMENT 'Índice para búsquedas por evento'
) ENGINE=InnoDB COMMENT='Tabla para almacenar referencias a archivos de eventos';

-- ========================================
-- Datos iniciales
-- ========================================

-- Usuario administrador inicial (password: admin123)
INSERT INTO usuarios (username, password, nombre, email) VALUES 
('admin', '$2y$10$R65JBBwJOc3ZnLyqPHpeS.TXe1bsHfvjOXKl3YDFB87yl6nMT33E.', 'miguel', 'nuevo_admin@example.com');

-- Giras de ejemplo
INSERT INTO giras (nombre) VALUES 
('Gira Verano 2025'),
('Gira Otoño 2025');

-- Artistas de ejemplo
INSERT INTO artistas (nombre, genero_musical, descripcion, presentacion) VALUES 
('Agrupación Marilyn', 'Cumbia Testimonial', 'Descripción detallada de Agrupación Marilyn...', 'Agrupación Marilyn ha conseguido un lugar especial en el corazón de seguidores tanto a nivel nacional como internacional. Su música, definida por la cumbia romántica y testimonial, narra historias que reflejan el cotidiano vivir con las cuales todos podemos identificarnos. Entre sus éxitos destacan Su florcita, Me enamoré, Te falta sufrir y Madre soltera. Actualmente, Agrupación Marilyn trabaja en su sexto disco, del cual ya han lanzado los exitosos singles: Abismo, Siento, Piel y Huesos, que adelantan una propuesta fresca y poderosa, fiel a su estilo.'),
('Flor Alvarez', 'Cumbia', 'Descripción detallada de Flor Alvarez...', 'Agradecemos desde ya su interés en la talentosa cantante argentina Flor Álvarez, una joven promesa que ha conquistado corazones con su música. Desde sus inicios cantando en el subte de Buenos Aires, Flor ha logrado posicionarse como una figura destacada en la música urbana y cumbia romántica. Con éxitos como Con Vos, Tattoo, Me Toco Perder, El Amor de mi Vida, y Sin Querer, acumula millones de reproducciones. Su música refleja una propuesta fresca y emotiva, consolidando su lugar en la escena musical. Actualmente, trabaja en nuevas colaboraciones que prometen sorprender, llevando su música a niveles internacionales.');