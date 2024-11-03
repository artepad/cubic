# Sistema de Gestión de Eventos - Productora de Eventos

## 📋 Descripción
Sistema web desarrollado en PHP para la gestión integral de eventos, diseñado específicamente para productoras de eventos. Permite administrar clientes, eventos, cotizaciones y seguimiento de producción en tiempo real.

## 🚀 Características Principales
- Gestión de eventos y agenda
- Administración de clientes
- Sistema de cotizaciones automatizado
- Seguimiento de estado de eventos
- Panel de control con métricas
- Generación de reportes
- Sistema de autenticación y roles de usuario

## 🔧 Requisitos del Sistema
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web Apache/Nginx
- Extensiones PHP requeridas:
  - PDO
  - MySQLi
  - session
  - json
  - mbstring

## 💻 Instalación

1. Clonar el repositorio
```bash
git clone [URL_DEL_REPOSITORIO]
cd sistema-eventos
```

2. Configurar la base de datos
- Importar el archivo `database/schema.sql`
- Copiar el archivo de configuración
```bash
cp config/config.example.php config/config.php
```
- Editar `config/config.php` con los datos de conexión a la base de datos

3. Configurar el servidor web
- Asegurar que el directorio del proyecto sea accesible por el servidor web
- Configurar los permisos necesarios
```bash
chmod 755 -R /ruta/al/proyecto
chmod 777 -R /ruta/al/proyecto/uploads
```

4. Acceder al sistema
- URL: `http://tu-dominio/sistema-eventos`
- Usuario por defecto: `admin`
- Contraseña por defecto: `admin123`

## 📁 Estructura del Proyecto
```
sistema-eventos/
├── config/
│   ├── config.php
│   └── database.php
├── functions/
│   ├── functions.php
│   ├── auth_functions.php
│   └── event_functions.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── nav.php
│   └── sidebar.php
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
├── uploads/
├── database/
│   └── schema.sql
└── index.php
```

## 🔐 Seguridad
- Implementación de consultas preparadas para prevenir SQL Injection
- Escape de salida HTML para prevenir XSS
- Control de acceso basado en roles
- Validación de entrada de datos
- Protección contra CSRF
- Sesiones seguras

## 📊 Módulos del Sistema
1. **Gestión de Eventos**
   - Creación y edición de eventos
   - Seguimiento de estado
   - Calendario de eventos
   - Asignación de recursos

2. **Gestión de Clientes**
   - Base de datos de clientes
   - Historial de eventos por cliente
   - Información de contacto
   - Preferencias y notas

3. **Cotizaciones**
   - Generación automática
   - Plantillas personalizables
   - Historial de versiones
   - Exportación a PDF

4. **Reportes**
   - Eventos por período
   - Rendimiento financiero
   - Estadísticas de clientes
   - Análisis de recursos

## 🛠️ Tecnologías Utilizadas
- PHP 7.4
- MySQL
- JavaScript/jQuery
- Bootstrap
- DataTables
- Font Awesome
- Chart.js

## 📝 Mantenimiento
Para mantener el sistema actualizado y funcionando correctamente:
1. Realizar respaldos regulares de la base de datos
2. Mantener actualizado PHP y sus extensiones
3. Revisar los logs del sistema periódicamente
4. Actualizar las dependencias cuando sea necesario

## 🤝 Contribución
Si deseas contribuir al proyecto:
1. Haz un Fork del repositorio
2. Crea una nueva rama para tu característica
3. Envía un Pull Request

## 📄 Licencia
Este proyecto está bajo la Licencia [TU_LICENCIA] - ver el archivo LICENSE.md para más detalles

## 👥 Soporte
Para soporte y consultas:
- Email: [TU_EMAIL]
- Issues: GitHub Issues
- Documentación: [URL_DOCUMENTACION]

## ⚙️ Configuración Adicional
Para configuraciones específicas o personalizaciones, consultar la documentación detallada en la wiki del proyecto.
