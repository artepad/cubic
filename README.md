# Sistema de Gestión de Eventos - Cubic

## 📋 Descripción
Sistema web especializado en la gestión integral de la industria del entretenimiento, desarrollado en PHP. Diseñado para optimizar las operaciones diarias de managers, representantes artísticos y productoras de eventos.

### 🎯 Objetivo Principal
Proporcionar una plataforma centralizada para la administración eficiente de eventos, artistas y relaciones con clientes, facilitando la generación automatizada de documentación esencial y el seguimiento en tiempo real de shows.

### 🚀 Características Principales
- **Gestión de Eventos**: Seguimiento completo desde la planificación hasta la ejecución, incluyendo estado y agenda
- **Administración de Artistas**: Perfiles detallados y gestión integral de representación artística
- **Gestión de Clientes**: Base de datos centralizada con exportación en formato CSV
- **Calendario Interactivo**: Visualización y seguimiento de eventos en tiempo real
- **Documentación Automatizada**:
  - Generación de contratos automatizada
  - Generación de cotizaciones automatizada
  - Generación de itinerarios en formato PDF
- **Panel Administrativo**: Interface intuitiva para gestión de recursos
- **Sistema de Autenticación**: Control de acceso y seguridad de la plataforma

### 🔧 Requisitos del Sistema
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web Apache/Nginx
- Extensiones PHP requeridas:
  - PDO
  - MySQLi
  - session
  - json
  - mbstring

###  📁 Estructura del Proyecto
```
admin/
├── assets/
│   ├── bd/
│   ├── bootstrap/
│   ├── css/
│   ├── img/
│   ├── js/
│   ├── less/
│   └── plugins/
│
├── config/
│   ├── config.php
│   ├── paths.php
│   └── test.php
│
├── functions/
│   ├── actualizar_evento.php
│   ├── crear_evento.php
│   ├── functions.php
│   ├── obtener_cliente.php
│   ├── obtener_eventos_calendario.php
│   ├── plantilla.php
│   ├── plantilla3.php
│   └── procesar_artista.php
│
├── includes/
│   ├── footer.php
│   ├── head.php
│   ├── header.php
│   ├── nav.php
│   ├── scripts.php
│   └── sidebar.php
│
├── logs/
├── uploads/
└── vendor/
    ├── .htaccess
    ├── archivo.md
    ├── cambiar_estado.php
    ├── composer.json
    ├── composer.lock
    ├── configuracion.php
    ├── descargar_archivo.php
    ├── eliminar_archivo.php
    ├── eliminar_cliente.php
    ├── eliminar_evento.php
    ├── exportar_clientes.php
    ├── generar_contrato.php
    ├── generar_cotizacion.php
    ├── generar_itinerario.php
    ├── index.php
    ├── ingreso_artista.php
    ├── ingreso_cliente.php
    ├── ingreso_evento.php
    ├── ingreso_giras.php
    ├── listar_agenda.php
    ├── listar_artistas.php
    ├── listar_calendario.php
    ├── listar_clientes.php
    ├── login.php
    ├── logout.php
    ├── subir_archivos.php
    ├── ver_artista.php
    ├── ver_cliente.php
    ├── ver_evento.php
    └── verificar_eventos.php
```

###  📋 Descripción del Sistema

Este sistema está organizado en los siguientes componentes principales:

### 🔧 Módulos Core
- 📅 Gestión de eventos
- 🎭 Gestión de artistas
- 👥 Gestión de clientes
- 📄 Generación de documentos
- 🔐 Sistema de autenticación

### 📂 Directorios Principales
- 🎨 `assets/`: Recursos estáticos (CSS, JS, imágenes)
- ⚙️ `config/`: Archivos de configuración
- 💻 `functions/`: Lógica de negocio
- 🧩 `includes/`: Componentes reutilizables
- 📦 `vendor/`: Dependencias y archivos principales

### ⚡ Funcionalidades
- 🔑 Sistema de login/logout
- 📝 CRUD de eventos, artistas y clientes
- 📊 Generación de documentos (contratos, cotizaciones, itinerarios)
- 📁 Gestión de archivos
- 📅 Visualización de calendario
- 📤 Exportación de datos


## 👥 Soporte
Para soporte y consultas:
- 📧 Email: mi.saavedra.q@gmail.com



