# Sistema Municipal San Francisco

Sistema de gestión municipal tipo Notion/Trello desarrollado en PHP Slim Framework con funcionalidades completas de gestión de tareas, casilla digital ciudadana, chat interno y notificaciones por email.

## 🚀 Características Principales

- **Tableros Kanban**: Gestión visual de tareas estilo Trello/Notion
- **Casilla Digital**: Portal para solicitudes ciudadanas
- **Sistema de Chat**: Mensajería interna entre usuarios
- **Notificaciones**: Sistema completo con envío por email
- **Gestión de Usuarios**: Roles y permisos diferenciados
- **Dashboard Interactivo**: Métricas y estadísticas en tiempo real
- **Responsive Design**: Interfaz moderna y adaptable
- **API REST**: Backend completo con endpoints documentados

## 📋 Requisitos del Sistema

- PHP 8.0 o superior
- MySQL 5.7+ o MariaDB 10.3+
- Composer
- Servidor web (Apache/Nginx)
- Extensiones PHP requeridas:
  - PDO MySQL
  - JSON
  - MBString
  - OpenSSL
  - FileInfo

## 🛠️ Instalación Rápida

### 1. Clonar y configurar el proyecto

```bash
# Crear directorio del proyecto
mkdir sistema-municipal-sf
cd sistema-municipal-sf

# Crear estructura de directorios
mkdir -p src/{Controllers,Models,Middleware,Services,Views}
mkdir -p public
mkdir -p uploads
mkdir -p logs
mkdir -p cache/twig

# Descargar composer si no está instalado
curl -sS https://getcomposer.org/installer | php
```

### 2. Crear composer.json

Copiar el contenido del archivo `composer.json` proporcionado en el proyecto.

### 3. Instalar dependencias

```bash
composer install
```

### 4. Configurar base de datos

```bash
# Crear base de datos
mysql -u root -p -e "CREATE DATABASE municipal_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importar estructura (copiar el SQL del archivo database.sql)
mysql -u root -p municipal_system < database.sql
```

### 5. Configurar variables de entorno

```bash
# Copiar archivo de configuración
cp .env.example .env

# Editar configuraciones
nano .env
```

Configurar las variables principales en `.env`:

```bash
# Base de datos
DB_HOST=localhost
DB_NAME=municipal_system
DB_USERNAME=root
DB_PASSWORD=tu_password

# JWT
JWT_SECRET=tu_clave_secreta_muy_segura_cambiar_en_produccion

# Email (configurar con tus datos SMTP)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=sistema@sanfrancisco.gov.ar
MAIL_PASSWORD=tu_password_email
MAIL_FROM_ADDRESS=sistema@sanfrancisco.gov.ar
```

### 6. Configurar permisos

```bash
# Permisos de escritura
chmod 755 uploads/
chmod 755 logs/
chmod 755 cache/

# Si es necesario en algunos sistemas
sudo chown -R www-data:www-data uploads/ logs/ cache/
```

### 7. Configurar servidor web

#### Apache (.htaccess en public/)

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

#### Nginx

```nginx
server {
    listen 80;
    server_name sistema-municipal.local;
    root /path/to/proyecto/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 8. Iniciar el sistema

```bash
# Opción 1: Servidor incorporado de PHP (desarrollo)
composer start
# o
php -S localhost:8080 -t public

# Opción 2: Configurar virtual host en Apache/Nginx
```

## 🔐 Usuarios por Defecto

El sistema incluye usuarios predeterminados:

| Usuario | Email | Contraseña | Rol |
|---------|--------|------------|-----|
| admin | admin@sanfrancisco.gov.ar | password | Administrador |
| intendente | intendente@sanfrancisco.gov.ar | password | Administrador |
| secretario | secretario@sanfrancisco.gov.ar | password | Manager |

**⚠️ IMPORTANTE**: Cambiar estas contraseñas inmediatamente en producción.

## 📱 Uso del Sistema

### Dashboard Principal
- Acceder a `http://localhost:8080/dashboard`
- Vista general de métricas y actividad reciente
- Navegación por módulos desde el sidebar

### Gestión de Tableros
1. **Crear Tablero**: Botón "Nuevo Tablero" en la sección Tableros
2. **Agregar Columnas**: Las columnas por defecto se crean automáticamente
3. **Crear Tareas**: Arrastar y soltar entre columnas
4. **Asignar Usuarios**: Seleccionar responsables de tareas
5. **Agregar Comentarios**: Colaboración en tiempo real

### Portal Ciudadano
- Acceso público en `http://localhost:8080/citizen-portal`
- Los ciudadanos pueden enviar solicitudes sin registrarse
- Auto-asignación por categorías configurables
- Notificaciones automáticas por email

### Sistema de Notificaciones
- Notificaciones en tiempo real en el dashboard
- Envío automático de emails para:
  - Tareas asignadas
  - Comentarios nuevos
  - Solicitudes ciudadanas
  - Recordatorios de vencimiento

## 🔧 Configuración Avanzada

### Configurar Email SMTP

Para Gmail u otros proveedores:

```bash
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-app-password
MAIL_ENCRYPTION=tls
```

### Configurar Categorías de Solicitudes

Editar en `CitizenRequestController.php` las reglas de auto-asignación:

```php
$assignmentRules = [
    'obras_publicas' => ['department' => 'Obras Públicas'],
    'servicios' => ['department' => 'Servicios'],
    'tramites' => ['role' => 'manager'],
    // Agregar más reglas según necesidades
];
```

### Personalizar Roles y Permisos

En el modelo `User.php` puedes agregar métodos para roles específicos:

```php
public function isSecretario(): bool
{
    