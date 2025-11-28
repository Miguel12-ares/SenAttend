# Instrucciones de Implementación - Relación Instructor-Fichas

## 📋 Resumen de Cambios

Se ha implementado una relación muchos a muchos entre **Instructores** y **Fichas** para que los instructores solo vean las fichas que tienen asignadas.

## 🚀 Pasos para Implementar

### 1. Ejecutar la Migración SQL

Ejecuta el siguiente comando en tu base de datos MySQL:

```bash
mysql -u root -p sena_asistencia < database/migrations/001_create_instructor_fichas_table.sql
```

O si prefieres usar phpMyAdmin:
1. Abre phpMyAdmin
2. Selecciona la base de datos `sena_asistencia`
3. Ve a la pestaña SQL
4. Copia y pega el contenido del archivo `database/migrations/001_create_instructor_fichas_table.sql`
5. Ejecuta

### 2. Verificar la Instalación

Ejecuta estas consultas para verificar que la tabla se creó correctamente:

```sql
-- Verificar que la tabla existe
SHOW TABLES LIKE 'instructor_fichas';

-- Ver la estructura de la tabla
DESCRIBE instructor_fichas;

-- Verificar datos de prueba (si los incluiste)
SELECT * FROM instructor_fichas;
```

### 3. Acceder al Módulo de Gestión

1. Inicia sesión como **Admin** o **Coordinador**
2. Navega a: `http://tu-dominio/instructor-fichas`
3. Desde aquí podrás:
   - Ver todos los instructores y sus fichas asignadas
   - Asignar/desasignar fichas a instructores
   - Ver estadísticas de asignaciones
   - Realizar asignaciones rápidas

## 📁 Archivos Creados

### Backend (PHP)
- `src/Repositories/InstructorFichaRepository.php` - Gestión de datos
- `src/Services/InstructorFichaService.php` - Lógica de negocio
- `src/Controllers/InstructorFichaController.php` - Controlador principal

### Frontend
- `views/instructor-fichas/index.php` - Vista principal de gestión
- `public/css/instructor-fichas.css` - Estilos del módulo
- `public/js/instructor-fichas.js` - Lógica JavaScript

### Base de Datos
- `database/migrations/001_create_instructor_fichas_table.sql` - Script de migración

### Archivos Modificados
- `src/Controllers/AsistenciaController.php` - Ahora filtra fichas por instructor
- `src/Repositories/UserRepository.php` - Agregados métodos findByRole() y countByRole()
- `public/index.php` - Agregadas rutas del nuevo módulo

## 🔐 Permisos

### Roles y Accesos

| Rol | Gestión de Asignaciones | Ver Solo Sus Fichas | Ver Todas las Fichas |
|-----|-------------------------|---------------------|---------------------|
| Admin | ✅ Sí | - | ✅ Sí |
| Coordinador | ✅ Sí | - | ✅ Sí |
| Instructor | ❌ No | ✅ Sí | ❌ No |

## 🧪 Pruebas Recomendadas

### 1. Como Admin/Coordinador
- Acceder a `/instructor-fichas`
- Asignar fichas a un instructor
- Verificar que las estadísticas se actualizan

### 2. Como Instructor
- Acceder a `/asistencia/registrar`
- Verificar que solo aparecen las fichas asignadas
- Intentar acceder a una ficha no asignada (debe denegar acceso)

### 3. Verificación de API
```javascript
// Probar en consola del navegador (estando logueado)
fetch('/api/instructor-fichas/estadisticas')
  .then(r => r.json())
  .then(console.log);
```

## 🛠️ Características Implementadas

### Gestión de Asignaciones
- ✅ Asignación múltiple de fichas a instructores
- ✅ Asignación múltiple de instructores a fichas
- ✅ Sincronización de asignaciones (reemplazar todas)
- ✅ Eliminación de asignaciones específicas
- ✅ Vista de asignaciones por instructor
- ✅ Vista de asignaciones por ficha
- ✅ Asignación rápida masiva

### Seguridad
- ✅ Validación de permisos por rol
- ✅ Instructores solo ven fichas asignadas
- ✅ Prevención de duplicados con UNIQUE KEY
- ✅ Claves foráneas con CASCADE
- ✅ Prepared Statements para prevenir SQL Injection

### UI/UX
- ✅ Interfaz responsive
- ✅ Tabs para diferentes vistas
- ✅ Búsqueda en tiempo real
- ✅ Modales para gestión
- ✅ Notificaciones de éxito/error
- ✅ Loading states
- ✅ Estadísticas en tiempo real

## 📊 Estructura de la Base de Datos

### Tabla: instructor_fichas
```sql
CREATE TABLE instructor_fichas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instructor_id INT NOT NULL,
    ficha_id INT NOT NULL,
    fecha_asignacion DATE,
    asignado_por INT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (instructor_id, ficha_id),
    FOREIGN KEY (instructor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (ficha_id) REFERENCES fichas(id) ON DELETE CASCADE,
    FOREIGN KEY (asignado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);
```

## 🎯 Principios SOLID Aplicados

1. **Single Responsibility**: Cada clase tiene una única responsabilidad
   - Repository: Acceso a datos
   - Service: Lógica de negocio
   - Controller: Manejo de requests/responses

2. **Open/Closed**: Extensible sin modificar código existente

3. **Dependency Inversion**: Controllers dependen de abstracciones (Services/Repositories)

4. **Interface Segregation**: Métodos específicos para cada necesidad

5. **Liskov Substitution**: Las clases pueden ser sustituidas por sus derivadas

## ⚠️ Notas Importantes

1. **Datos de Prueba**: El script SQL incluye asignaciones de prueba para los primeros 2 instructores
2. **Cache**: Si usas cache, limpia después de ejecutar la migración
3. **Sesiones**: Los instructores deben cerrar sesión y volver a iniciar para ver los cambios
4. **Backup**: Siempre haz backup antes de ejecutar migraciones

## 🐛 Solución de Problemas

### Error: "No tiene acceso a esta ficha"
- Verificar que el instructor tiene la ficha asignada en `instructor_fichas`
- Verificar que la asignación está activa (`activo = 1`)

### No aparecen fichas para el instructor
- Verificar asignaciones en la base de datos
- Verificar que las fichas están en estado 'activa'
- Limpiar caché del navegador

### Error 500 al acceder al módulo
- Verificar que todos los archivos fueron creados
- Revisar logs de PHP: `tail -f /var/log/php_errors.log`
- Verificar permisos de archivos

## 📞 Soporte

Si encuentras algún problema:
1. Revisa los logs del sistema
2. Verifica que seguiste todos los pasos
3. Consulta la documentación del código

---

**Implementación completada exitosamente** ✅
Desarrollado siguiendo principios SOLID y mejores prácticas de seguridad.
