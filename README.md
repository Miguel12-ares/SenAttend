# SENAttend - Sistema de Asistencia SENA

Sistema de gestión de asistencia para aprendices del SENA desarrollado con PHP 8.2+ y arquitectura MVC nativa.

## 📋 Descripción

**SENAttend** es un sistema MVP (Minimum Viable Product) para la gestión de asistencia de aprendices en el Servicio Nacional de Aprendizaje (SENA). Implementa una arquitectura MVC ligera, PSR-4, conexión PDO persistente a MySQL, y un sistema de autenticación con roles.

## Características Principales - Fase 0 MVP

- Arquitectura MVC con autoload PSR-4
- Conexión PDO Singleton con persistencia
- Sistema de autenticación seguro (login/logout)
- Middleware de protección de rutas
- Gestión de sesiones con seguridad (httpOnly, regeneración)
- Base de datos optimizada con índices
- Interfaz institucional SENA
- Repositorios para Usuarios, Fichas y Aprendices
- Seeds con 50 fichas y 500 aprendices

## 🛠️ Requisitos Técnicos

- **PHP**: 8.2 o superior
- **MySQL**: 8.0 o superior
- **Composer**: Para gestión de dependencias
- **Servidor Web**: Apache con mod_rewrite o Nginx
- **Extensiones PHP requeridas**:
  - PDO
  - pdo_mysql
  - mbstring
  - session

## 📁 Estructura del Proyecto

```
senattend/
├── config/
│   └── config.php                 # Configuración principal y carga de .env
├── database/
│   ├── schema.sql                 # Esquema de base de datos
│   └── seeds.sql                  # Datos iniciales
├── docs/
│   └── README.md                  # Documentación (este archivo)
├── public/
│   ├── index.php                  # Router frontal
│   ├── .htaccess                  # Configuración Apache
│   ├── css/
│   │   └── style.css              # Estilos institucionales
│   └── js/
│       └── app.js                 # JavaScript principal
├── src/
│   ├── Controllers/
│   │   ├── AuthController.php     # Controlador de autenticación
│   │   └── DashboardController.php # Controlador del dashboard
│   ├── Database/
│   │   └── Connection.php         # PDO Singleton
│   ├── Middleware/
│   │   └── AuthMiddleware.php     # Middleware de autenticación
│   ├── Repositories/
│   │   ├── UserRepository.php     # Repositorio de usuarios
│   │   ├── FichaRepository.php    # Repositorio de fichas
│   │   └── AprendizRepository.php # Repositorio de aprendices
│   ├── Services/
│   │   └── AuthService.php        # Servicio de autenticación
│   ├── Session/
│   │   └── SessionManager.php     # Gestor de sesiones
│   └── Support/
│       └── Response.php           # Helpers de respuesta HTTP
├── views/
│   ├── layouts/
│   │   └── base.php               # Layout base
│   ├── auth/
│   │   └── login.php              # Vista de login
│   ├── dashboard/
│   │   └── index.php              # Dashboard principal
│   └── errors/
│       ├── 404.php                # Página no encontrada
│       └── 500.php                # Error del servidor
├── .env                           # Variables de entorno (crear manualmente)
├── .env.example                   # Ejemplo de variables de entorno
├── .gitignore                     # Archivos ignorados por Git
├── composer.json                  # Dependencias y autoload PSR-4
└── README.md                      # Este archivo
```

## 🚀 Instalación

### 1. Clonar o descargar el proyecto

```bash
cd C:\xampp\htdocs\senattend
```

### 2. Instalar dependencias con Composer

```bash
composer install
```

Si no tienes Composer instalado, descárgalo de [getcomposer.org](https://getcomposer.org/)

### 3. Configurar variables de entorno

Crea un archivo `.env` en la raíz del proyecto basándote en `.env.example`:

```env
APP_ENV=local
DB_HOST=127.0.0.1
DB_NAME=sena_asistencia
DB_USER=root
DB_PASS=tu_password
```

**Nota**: En Windows con XAMPP, normalmente la contraseña de root está vacía.

### 4. Crear la base de datos

Abre phpMyAdmin o la consola MySQL y ejecuta:

```sql
CREATE DATABASE sena_asistencia DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Importar el esquema

```bash
# Opción 1: Desde consola MySQL
mysql -u root -p sena_asistencia < database/schema.sql

# Opción 2: Desde phpMyAdmin
# Importar el archivo database/schema.sql
```

### 6. Importar los datos iniciales (seeds)

```bash
# Opción 1: Desde consola MySQL
mysql -u root -p sena_asistencia < database/seeds.sql

# Opción 2: Desde phpMyAdmin
# Importar el archivo database/seeds.sql
```

### 7. Configurar el servidor web

#### Opción A: Apache (XAMPP)

1. Edita el archivo `httpd-vhosts.conf` (C:\xampp\apache\conf\extra\httpd-vhosts.conf):

```apache
<VirtualHost *:80>
    ServerName senassist.local
    DocumentRoot "C:/xampp/htdocs/senattend/public"
    
    <Directory "C:/xampp/htdocs/senattend/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

2. Edita el archivo `hosts` (C:\Windows\System32\drivers\etc\hosts) como administrador:

```
127.0.0.1 senassist.local
```

3. Reinicia Apache desde el Panel de Control de XAMPP

4. Accede a: http://senassist.local

#### Opción B: PHP Built-in Server (Desarrollo rápido)

```bash
cd public
php -S localhost:8000
```

Accede a: http://localhost:8000

### 8. Probar el sistema

Accede al login con las credenciales por defecto:

- **Email**: `admin@sena.edu.co`
- **Password**: `admin123`

También puedes usar:
- `instr1@sena.edu.co` / `admin123` (Instructor)
- `instr2@sena.edu.co` / `admin123` (Instructor)
- `coordinador@sena.edu.co` / `admin123` (Coordinador)

## 📊 Esquema de Base de Datos

### Tablas Principales

1. **usuarios**: Instructores, coordinadores y administradores
2. **aprendices**: Estudiantes del SENA
3. **fichas**: Fichas de formación
4. **ficha_aprendiz**: Relación N:M entre fichas y aprendices
5. **asistencias**: Registros de asistencia

### Índices Optimizados

- `usuarios`: email, documento, rol
- `fichas`: numero_ficha, estado
- `aprendices`: documento, codigo_carnet, estado
- `asistencias`: fecha, unique_registro (previene duplicados por día)

## 🧪 Verificación de Instalación

Ejecuta estas consultas SQL para verificar que los datos se cargaron correctamente:

```sql
-- Verificar usuarios
SELECT COUNT(*) as total_usuarios FROM usuarios;
-- Resultado esperado: 4

-- Verificar fichas
SELECT COUNT(*) as total_fichas FROM fichas;
-- Resultado esperado: 50

-- Verificar aprendices
SELECT COUNT(*) as total_aprendices FROM aprendices;
-- Resultado esperado: 500

-- Verificar relaciones
SELECT COUNT(*) as total_relaciones FROM ficha_aprendiz;
-- Resultado esperado: ~500
```

## Criterios de Aceptación - Fase 0

### 1. Arquitectura MVC con PSR-4

- [x] Estructura de carpetas MVC implementada
- [x] Autoload PSR-4 configurado en composer.json
- [x] `composer dump-autoload` ejecuta sin errores
- [x] Clases se cargan automáticamente

**Verificación**:
```bash
composer dump-autoload
# No debe mostrar errores
```

### 2. Conexión PDO Persistente Operativa

- [x] Singleton implementado en `src/Database/Connection.php`
- [x] Conexión persistente (PDO::ATTR_PERSISTENT => true)
- [x] ERRMODE_EXCEPTION configurado
- [x] Charset UTF8MB4
- [x] Sin warnings ni fatal errors

**Verificación**: Acceder a cualquier página del sistema sin errores de conexión.

### 3. Login Funcional con Sesiones Seguras

- [x] Vista de login en `/login`
- [x] POST a `/auth/login` procesa credenciales
- [x] `password_verify()` valida contraseñas
- [x] Sesiones con `httpOnly` y `samesite`
- [x] Regeneración de ID de sesión post-login
- [x] Logout en `/auth/logout` destruye sesión completamente

**Verificación**: Iniciar sesión, navegar al dashboard, cerrar sesión.

### 4. Middleware de Autenticación

- [x] Rutas públicas: `/login`, `/auth/login`
- [x] Rutas protegidas: `/` (dashboard)
- [x] Redirección a `/login` si no hay sesión
- [x] Acceso permitido si hay sesión válida

**Verificación**: Intentar acceder a `/` sin sesión → redirección a `/login`.

### 5. Esquema y Seeds Importan sin Errores

- [x] `schema.sql` importa todas las tablas
- [x] `seeds.sql` inserta datos de prueba
- [x] 50 fichas creadas
- [x] 500 aprendices creados
- [x] 4 usuarios creados
- [x] Relaciones N:M funcionando

**Verificación**: Ejecutar las consultas SQL de la sección "Verificación de Instalación".

## 📖 Uso del Sistema

### Rutas Disponibles

| Método | Ruta | Descripción | Protegida |
|--------|------|-------------|-----------|
| GET | `/` | Dashboard principal | Sí |
| GET | `/login` | Vista de login | ❌ No |
| POST | `/auth/login` | Procesar login | ❌ No |
| GET | `/auth/logout` | Cerrar sesión | ❌ No |

### Roles de Usuario

- **admin**: Acceso completo al sistema
- **instructor**: Registro de asistencia
- **coordinador**: Visualización de reportes

## 🔒 Seguridad Implementada

1. **Contraseñas**: Hash con `password_hash()` (bcrypt)
2. **Sesiones**:
   - `httpOnly` cookies
   - Regeneración de ID post-login
   - `samesite=Strict`
3. **Validación**: Sanitización de inputs con `filter_input()`
4. **PDO**: Prepared statements previenen SQL injection
5. **Headers**: XSS Protection, X-Frame-Options, X-Content-Type-Options
6. **Errores**: No expone información sensible en producción

## 🎨 Paleta de Colores Institucional

- **Verde SENA**: `#39A900` (primario)
- **Verde Oscuro**: `#2d8600`
- **Azul Institucional**: `#00324D` (secundario)
- **Blanco**: `#FFFFFF`

## 📝 Comandos Útiles

```bash
# Instalar dependencias
composer install

# Regenerar autoload (después de crear nuevas clases)
composer dump-autoload

# Verificar errores PHP
php -l archivo.php

# Iniciar servidor PHP integrado
php -S localhost:8000 -t public

# Backup de base de datos
mysqldump -u root -p sena_asistencia > backup.sql

# Restaurar base de datos
mysql -u root -p sena_asistencia < backup.sql
```

## 🔧 Troubleshooting

### Error: "Class not found"
```bash
composer dump-autoload
```

### Error: ".env file not found"
Crea el archivo `.env` en la raíz del proyecto con las variables de `config/config.php`.

### Error: "Connection refused"
Verifica que MySQL esté corriendo y las credenciales en `.env` sean correctas.

### Error: "404 Not Found" en todas las rutas
Verifica que `mod_rewrite` esté habilitado en Apache y que el `.htaccess` exista en `/public`.

### Estilos CSS no cargan
Asegúrate de que el DocumentRoot apunte a la carpeta `/public`.

## 📚 Próximas Fases

### Fase 1 - Gestión de Fichas
- CRUD completo de fichas
- Asignación de aprendices a fichas
- Filtros y búsqueda

### Fase 2 - Gestión de Aprendices
- CRUD completo de aprendices
- Carga masiva desde Excel
- Vinculación con fichas

### Fase 3 - Registro de Asistencia
- Toma de asistencia por ficha
- Escaneo de carnets (QR/Barcode)
- Registro de tardanzas

### Fase 4 - Reportes
- Reportes por fecha
- Reportes por ficha
- Exportación a Excel/PDF

## 👥 Credenciales de Acceso

| Rol | Email | Password |
|-----|-------|----------|
| Administrador | admin@sena.edu.co | admin123 |
| Instructor 1 | instr1@sena.edu.co | admin123 |
| Instructor 2 | instr2@sena.edu.co | admin123 |
| Coordinador | coordinador@sena.edu.co | admin123 |

⚠️ **IMPORTANTE**: Cambiar estas contraseñas en producción.

## 📄 Licencia

Sistema desarrollado para el SENA - Servicio Nacional de Aprendizaje.

## 🤝 Soporte

Para reportar problemas o solicitar nuevas funcionalidades, contacta al equipo de desarrollo.

---

**SENAttend v1.0 MVP** - Sistema de Asistencia SENA  
© 2025 SENA - Servicio Nacional de Aprendizaje

