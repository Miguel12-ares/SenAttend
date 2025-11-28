# Implementación del Sistema RBAC (Role-Based Access Control)

## Fecha de Implementación
Implementado para corregir vulnerabilidad crítica de seguridad donde usuarios con rol `admin` podían acceder a rutas exclusivas de `instructor` (ej: `/qr/escanear`).

---

## Cambios Realizados

### 1. Sistema Centralizado de Permisos

#### Archivo: `config/permissions_config.php`
- **Constantes de roles**: `ROLE_ADMIN`, `ROLE_INSTRUCTOR`, `ROLE_COORDINADOR`, `ROLE_ESTUDIANTE`, `ROLE_ADMINISTRATIVO`
- **Matriz de permisos**: Define qué roles pueden acceder a cada ruta
  - `exact`: Rutas estáticas con coincidencia exacta
  - `patterns`: Rutas dinámicas con parámetros (usando regex)
- **Funciones helper**:
  - `route_allowed($method, $uri, $role)`: Verifica si un rol tiene permiso
  - `get_allowed_roles_for_route($method, $uri)`: Obtiene roles permitidos

#### Archivo: `src/Middleware/PermissionMiddleware.php`
- Middleware centralizado que valida permisos en cada petición
- Registra intentos de acceso no autorizado en logs
- Redirige con código 403 cuando se detecta acceso no autorizado

### 2. Integración en Router Principal

#### Archivo: `public/index.php`
- Carga la configuración de permisos al inicio
- Instancia `PermissionMiddleware`
- Aplica validación de permisos **después** de la autenticación básica
- Todas las rutas resueltas pasan por validación RBAC

### 3. Correcciones en Controladores

#### Archivo: `src/Controllers/QRController.php`
- **Método `escanear()`**: Corregido para NO permitir `admin`
  - **Antes**: Permitía `['instructor', 'coordinador', 'admin']`
  - **Ahora**: Solo permite `['instructor', 'coordinador']`
  - Alineado con la matriz RBAC que bloquea `admin` en `/qr/escanear`

**Nota**: Los métodos `apiHistorialDiario()` y `apiProcesarQR()` mantienen `admin` porque la lógica de negocio lo requiere (admin puede ver historial y procesar QR, pero no usar la interfaz de escaneo).

---

## Matriz de Permisos por Categoría

### Rutas Públicas (sin autenticación)
- `/`, `/home`, `/login`, `/auth/logout`
- `/api/public/aprendiz/validar`

### Rutas Exclusivas de Admin
- `/configuracion/horarios` (GET)
- `/configuracion/horarios/actualizar` (POST)
- `/fichas/{id}/eliminar` (POST)
- `/aprendices/{id}/eliminar` (POST)
- `/api/fichas/{id}` (DELETE)
- `/api/aprendices/{id}` (DELETE)

### Rutas Exclusivas de Instructor
- `/qr/escanear` (GET) - **CRÍTICO**: Solo instructores pueden acceder
- `/asistencia/registrar` (GET)
- `/asistencia/guardar` (POST)

### Rutas Compartidas
- **Admin + Coordinador + Instructor**: Gestión de fichas y aprendices
- **Admin + Coordinador**: Importación masiva, cambios de estado
- **Admin + Coordinador + Instructor + Administrativo**: Visualización de datos

---

## Seguridad Implementada

### Validación en Múltiples Capas
1. **Middleware RBAC** (nivel router): Bloquea acceso antes de llegar al controlador
2. **Validaciones en controladores**: Doble verificación para operaciones críticas
3. **Validación en servicios**: `AsistenciaService::validarPermisosUsuario()` para lógica de negocio

### Registro de Intentos No Autorizados
- Todos los intentos de acceso denegado se registran en el log de PHP
- Formato: `RBAC_DENIED: {"timestamp": "...", "method": "...", "uri": "...", "role": "...", "reason": "..."}`
- Incluye IP y User-Agent para auditoría

### Redirecciones Seguras
- Usuario no autenticado → `/login`
- Usuario autenticado sin permisos → `/dashboard` (con código 403)

---

## Cobertura de Rutas

### Rutas Mapeadas (100% de rutas críticas)
- ✅ Todas las rutas de gestión (fichas, aprendices, asistencia)
- ✅ Todas las rutas de API
- ✅ Rutas de configuración
- ✅ Rutas de módulo QR
- ✅ Rutas de asignación instructor-ficha

### Rutas No Mapeadas (permitidas por compatibilidad)
- `/test-routes` (solo desarrollo)

**Recomendación**: Agregar todas las rutas a la matriz para tener control total.

---

## Uso de Funciones Helper

```php
// Verificar si un rol tiene permiso
if (route_allowed('GET', '/qr/escanear', 'instructor')) {
    // Permitir acceso
}

// Obtener roles permitidos
$allowedRoles = get_allowed_roles_for_route('POST', '/fichas');
// Retorna: ['admin', 'coordinador', 'instructor'] o null si no está mapeada
```

---

## Testing

### Script de Prueba
- Archivo: `tests/permissions_matrix_test.php`
- Ejecutar: `php tests/permissions_matrix_test.php`
- Muestra todas las rutas configuradas y sus roles permitidos

### Verificación Manual
1. Iniciar sesión como `admin`
2. Intentar acceder a `/qr/escanear`
3. **Resultado esperado**: Redirección a `/dashboard` con código 403
4. Verificar log: Debe aparecer entrada `RBAC_DENIED`

---

## Mantenimiento

### Agregar Nueva Ruta Protegida
1. Agregar ruta en `config/permissions_config.php`:
   ```php
   'GET' => [
       '/nueva-ruta' => [ROLE_ADMIN, ROLE_INSTRUCTOR],
   ],
   ```

2. Si es ruta dinámica, usar patrón:
   ```php
   'patterns' => [
       'GET' => [
           [
               'pattern' => '#^/ruta/(\d+)$#',
               'roles' => [ROLE_ADMIN],
           ],
       ],
   ],
   ```

### Modificar Permisos de Ruta Existente
- Editar directamente en `config/permissions_config.php`
- Los cambios se aplican inmediatamente (sin reiniciar servidor)

---

## Notas Importantes

⚠️ **CRÍTICO**: El middleware RBAC se aplica **antes** de que el request llegue al controlador. Si una ruta está bloqueada en la matriz, el controlador nunca se ejecutará.

✅ **Buenas Prácticas**: Mantener validaciones en controladores como capa de defensa adicional, pero confiar principalmente en el middleware RBAC.

📝 **Documentación**: Este documento debe actualizarse cuando se agreguen nuevas rutas o se modifiquen permisos.

---

## Referencias

- Archivo de configuración: `config/permissions_config.php`
- Middleware: `src/Middleware/PermissionMiddleware.php`
- Router principal: `public/index.php`
- Script de prueba: `tests/permissions_matrix_test.php`

