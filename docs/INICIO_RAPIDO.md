# 🚀 Guía de Inicio Rápido - SENAttend

## Pasos para iniciar el proyecto

### 1️⃣ Instalar Composer (si no lo tienes)

Descarga e instala Composer desde: https://getcomposer.org/download/

Para Windows: Descarga el instalador `.exe`

Verifica la instalación:
```bash
composer --version
```

### 2️⃣ Instalar dependencias

Abre una terminal en `C:\xampp\htdocs\senattend` y ejecuta:

```bash
composer install
```

Si no tienes Composer, el autoload manual ya está configurado y puedes continuar.

### 3️⃣ Configurar variables de entorno

**⚠️ IMPORTANTE**: Crea un archivo `.env` en la raíz del proyecto con este contenido:

```env
APP_ENV=local
DB_HOST=127.0.0.1
DB_NAME=sena_asistencia
DB_USER=root
DB_PASS=
```

**Nota**: Con XAMPP, la contraseña de MySQL suele estar vacía. Si configuraste una contraseña, agrégala en `DB_PASS`.

### 4️⃣ Crear la base de datos

Opción A - **phpMyAdmin** (recomendado para principiantes):
1. Abre http://localhost/phpmyadmin
2. Click en "Nueva" (en el panel izquierdo)
3. Nombre: `sena_asistencia`
4. Cotejamiento: `utf8mb4_unicode_ci`
5. Click en "Crear"

Opción B - **Consola MySQL**:
```sql
CREATE DATABASE sena_asistencia DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5️⃣ Importar el esquema de base de datos

**phpMyAdmin**:
1. Selecciona la base de datos `sena_asistencia`
2. Click en la pestaña "Importar"
3. Click en "Seleccionar archivo"
4. Navega a `C:\xampp\htdocs\senattend\database\schema.sql`
5. Click en "Continuar"

### 6️⃣ Importar los datos de prueba

**phpMyAdmin**:
1. Con la base de datos `sena_asistencia` seleccionada
2. Click en "Importar" nuevamente
3. Selecciona el archivo `C:\xampp\htdocs\senattend\database\seeds.sql`
4. Click en "Continuar"

### 7️⃣ Iniciar el servidor

**Opción A - Servidor PHP integrado** (más simple):

```bash
cd C:\xampp\htdocs\senattend\public
php -S localhost:8000
```

Luego abre tu navegador en: **http://localhost:8000**

**Opción B - Apache de XAMPP** (configuración completa):

1. Edita `C:\xampp\apache\conf\extra\httpd-vhosts.conf` y agrega:

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

2. Edita `C:\Windows\System32\drivers\etc\hosts` como **administrador** y agrega:

```
127.0.0.1 senassist.local
```

3. Reinicia Apache desde el panel de XAMPP

4. Abre tu navegador en: **http://senassist.local**

### 8️⃣ Iniciar sesión

Usa estas credenciales:

- **Email**: `admin@sena.edu.co`
- **Password**: `admin123`

## ✅ Verificación Rápida

Ejecuta estas consultas SQL en phpMyAdmin para verificar:

```sql
-- Debe devolver 4
SELECT COUNT(*) as usuarios FROM usuarios;

-- Debe devolver 50
SELECT COUNT(*) as fichas FROM fichas;

-- Debe devolver 500
SELECT COUNT(*) as aprendices FROM aprendices;
```

## 🐛 Problemas Comunes

### Error: "Class not found"
```bash
composer dump-autoload
```

### Error: "Connection refused"
- Verifica que MySQL esté corriendo en XAMPP
- Verifica las credenciales en `.env`

### Error: ".env file not found"
- Asegúrate de crear el archivo `.env` en la raíz (no en /public)
- Copia el contenido de `.env.example` si existe

### Página en blanco o error 500
- Verifica que el archivo `.htaccess` exista en `/public`
- Revisa los logs en `C:\xampp\apache\logs\error.log`

### CSS/JS no cargan
- Asegúrate de que el DocumentRoot apunte a `/public`
- Verifica que la URL no incluya `/public` en el navegador

## 📚 Próximos Pasos

Una vez que el sistema esté funcionando:

1. ✅ Cambiar las contraseñas por defecto
2. ✅ Explorar el dashboard
3. ✅ Revisar el código en `/src`
4. ✅ Leer el `README.md` completo para más detalles

## 🆘 Necesitas Ayuda?

Revisa el archivo `README.md` para documentación completa.

---

**¡Listo para usar SENAttend! 🎉**

