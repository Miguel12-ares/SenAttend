# 📊 Módulo de Estadísticas - Guía Rápida

## 🚀 Inicio Rápido

### 1. Acceder al Dashboard
```bash
# Desde el navegador (después de login)
http://tu-dominio/estadisticas
```

### 2. Usar las APIs
```bash
# Estadísticas por aprendiz
curl "http://tu-dominio/api/estadisticas/aprendiz?id_aprendiz=1&fecha_desde=2024-01-01&fecha_hasta=2024-12-31"

# Estadísticas por ficha
curl "http://tu-dominio/api/estadisticas/ficha?id_ficha=1&fecha_desde=2024-01-01&fecha_hasta=2024-12-31"

# Reportes críticos (solo admin/coordinador)
curl "http://tu-dominio/api/estadisticas/reportes"

# Exportar CSV
curl "http://tu-dominio/api/estadisticas/exportar?tipo=aprendiz&id_aprendiz=1&fecha_desde=2024-01-01&fecha_hasta=2024-12-31"
```

---

## 📋 ¿Qué Hace Este Módulo?

### 🎯 Objetivo Principal
Analizar la asistencia de aprendices y fichas formativas con métricas detalladas, patrones de comportamiento y alertas automáticas.

### 👥 Usuarios Objetivo
- **Administradores**: Visión completa de todas las fichas y aprendices
- **Coordinadores**: Análisis global + reportes críticos
- **Instructores**: Estadísticas solo de sus fichas asignadas

---

## 🔧 Arquitectura Técnica

### Estructura de Archivos
```
src/
├── Controllers/EstadisticasController.php     # Endpoints REST
├── Services/EstadisticasService.php           # Lógica de negocio
├── Repositories/EstadisticasRepository.php    # Consultas SQL
└── ...

views/estadisticas/
└── index.php                                  # Dashboard web

database/migrations/
└── 005_create_anomalias_table.sql            # Tabla excusas
```

### Tecnologías
- **Backend**: PHP 8.1+ con PDO
- **Frontend**: HTML5 + CSS3 + JavaScript (Vanilla)
- **Base de Datos**: MySQL 8.0+ con índices optimizados
- **Arquitectura**: MVC + 3 Capas (Repository → Service → Controller)

---

## 📊 Funcionalidades Disponibles

### 1. Estadísticas por Aprendiz
- ✅ Porcentajes de asistencia/inasistencia
- ✅ Total de tardanzas e inasistencias
- ✅ Frecuencia promedio entre inasistencias
- ✅ Distribución por día de semana
- ✅ Distribución por jornada
- ✅ Alertas automáticas de riesgo

### 2. Estadísticas por Ficha
- ✅ Totales agregados por estado
- ✅ Porcentajes globales
- ✅ Top 5 aprendices con más inasistencias
- ✅ Distribución por día de semana
- ✅ Distribución por jornada
- ✅ Flags de casos críticos

### 3. Reportes por Analizar
- ✅ Casos con alta concentración en un día
- ✅ Frecuencias críticas (≤3 días promedio)
- ✅ Patrón de riesgo de deserción
- ✅ Filtros por ficha o aprendiz específico

### 4. Exportación de Datos
- ✅ Formato CSV listo para Excel
- ✅ Headers descriptivos
- ✅ Datos tabulares completos
- ✅ Descarga automática

---

## 📏 Reglas de Negocio Implementadas

### Tardanzas Automáticas
```php
// Jornada Mañana: 06:00 - 06:20
if ($hora > '06:20') → tardanza

// Jornada Tarde: 12:00 - 12:20
if ($hora > '12:20') → tardanza

// Jornada Noche: variable - +20 minutos
if ($hora > $inicio_jornada + 20min) → tardanza
```

### Cálculo de Porcentajes
```php
$asistencia = presentes / (presentes + ausentes + tardanzas) * 100
$inasistencia = ausentes / (presentes + ausentes + tardanzas) * 100
```

### Frecuencia de Inasistencias
```php
// Ordenar fechas de inasistencia
// Calcular diferencias entre fechas consecutivas
// Promedio = suma_diferencias / cantidad_intervalos
```

### Alertas Automáticas
```php
// Alta concentración: >40% inasistencias en un día
// Frecuencia crítica: ≤3 días promedio entre ausencias
// Caída significativa: >15% menos asistencia vs período anterior
```

---

## 🔐 Permisos y Seguridad

### Control de Acceso por Rol

| Funcionalidad | Admin | Coordinador | Instructor |
|---------------|-------|-------------|------------|
| Ver Estadísticas | ✅ | ✅ | ✅ |
| Todas las Fichas | ✅ | ✅ | ❌ |
| Solo Mis Fichas | ✅ | ✅ | ✅ |
| Reportes Críticos | ✅ | ✅ | ❌ |
| Exportar Datos | ✅ | ✅ | ✅ |

### Validaciones Implementadas
- ✅ Autenticación requerida
- ✅ Autorización por roles
- ✅ Rate limiting en APIs
- ✅ Sanitización de parámetros
- ✅ Validación de acceso a fichas

---

## 🗄️ Base de Datos

### Tabla Principal: `anomalias`
```sql
CREATE TABLE anomalias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_asistencia INT NOT NULL,
    tipo ENUM('excusa','correccion','observacion'),
    motivo TEXT NOT NULL,
    documento_soporte VARCHAR(255),
    registrado_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_asistencia) REFERENCES asistencias(id),
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
);
```

### Índices Optimizados
- `idx_anomalias_id_asistencia`
- `idx_anomalias_tipo`
- `idx_anomalias_registrado_por`
- `idx_anomalias_created_at`

---

## 🎨 Interfaz de Usuario

### Dashboard Principal
- **4 tarjetas principales**: Aprendiz, Ficha, Reportes, Exportar
- **Filtros dinámicos**: Fechas, ficha, búsqueda de aprendices
- **Resultados visuales**: Tablas, métricas, gráficos conceptuales
- **Responsive**: Funciona en móvil y desktop

### Características UX
- ✅ Carga asíncrona (AJAX)
- ✅ Mensajes de estado
- ✅ Validaciones en tiempo real
- ✅ Exportación directa
- ✅ Navegación intuitiva

---

## 🚨 Casos de Uso Comunes

### Como Instructor
1. Ir a `/estadisticas`
2. Seleccionar "Estadísticas por Ficha"
3. Elegir una ficha asignada
4. Configurar rango de fechas
5. Ver métricas y top inasistentes

### Como Coordinador
1. Acceder a reportes críticos
2. Filtrar por ficha problemática
3. Identificar patrones de riesgo
4. Tomar acciones correctivas

### Como Administrador
1. Visión global de todas las fichas
2. Exportar datos para análisis externos
3. Monitorear tendencias generales
4. Configurar alertas y reportes

---

## 🔧 Desarrollo y Mantenimiento

### Agregar Nueva Métrica
1. Crear consulta en `EstadisticasRepository`
2. Implementar cálculo en `EstadisticasService`
3. Exponer en `EstadisticasController`
4. Actualizar vista si es necesario

### Modificar Reglas de Negocio
1. Actualizar constantes en `EstadisticasService`
2. Modificar funciones de cálculo
3. Probar con datos de prueba
4. Actualizar documentación

### Optimización de Performance
1. Revisar consultas SQL con `EXPLAIN`
2. Agregar índices si es necesario
3. Implementar cache si hay consultas repetidas
4. Monitorear logs de ejecución

---

## 📞 Solución de Problemas

### Error: "No tiene permisos"
**Solución**: Verificar rol del usuario en tabla `usuarios`

### Error: "Ficha no encontrada"
**Solución**: Verificar asignación instructor-ficha en tabla `instructor_ficha`

### Error: "Datos vacíos"
**Solución**: Verificar que existan registros en `asistencias` para el período

### Error: API retorna 500
**Solución**: Revisar logs de PHP y validar parámetros enviados

---

## 📚 Documentación Relacionada

- **[Documentación Completa](MODULO_ESTADISTICAS.md)**: Detalles técnicos exhaustivos
- **[Diagrama de Arquitectura](DIAGRAMA_ARQUITECTURA_ESTADISTICAS.md)**: Flujo visual de componentes
- **[Estado de Implementación](../docs/ESTADO_IMPLEMENTACION_MVP.md)**: Avance general del proyecto

---

## 🎯 Próximos Pasos

### Mejoras Sugeridas
1. **Gráficos Interactivos**: Charts.js para visualizaciones
2. **Notificaciones**: Email automático para alertas
3. **Cache**: Redis para mejorar performance
4. **PWA**: App móvil offline
5. **API Docs**: Swagger/OpenAPI documentation

### Métricas de Éxito
- ✅ **85% MVP completado** (con este módulo)
- ✅ **Arquitectura escalable** implementada
- ✅ **APIs RESTful** funcionales
- ✅ **Seguridad robusta** aplicada

---

**📝 Para más detalles técnicos, consulte la [documentación completa](MODULO_ESTADISTICAS.md)**
