# 📊 Módulo de Estadísticas - Documentación Técnica

## ✅ Estado: IMPLEMENTADO Y FUNCIONAL

El **Módulo de Estadísticas de Asistencia** ha sido implementado completamente siguiendo una arquitectura de 3 capas, con todos los endpoints REST funcionales y una interfaz web intuitiva.

---

## 🏗️ Arquitectura Implementada

### Estructura de 3 Capas

```
├── 📁 Capa de Datos (Repository)
│   └── EstadisticasRepository.php
├── 📁 Capa de Lógica de Negocio (Service)
│   └── EstadisticasService.php
└── 📁 Capa de Presentación (Controller + Views)
    ├── EstadisticasController.php
    └── views/estadisticas/index.php
```

### Principios de Diseño

- **Separación de responsabilidades**: Cada capa tiene un propósito claro
- **Inyección de dependencias**: Los servicios se pasan como parámetros
- **SOLID Principles**: Código mantenible y extensible
- **RBAC**: Control de acceso basado en roles (Admin/Coordinador/Instructor)

---

## 📋 Componentes Creados

### 1. EstadisticasRepository (Capa de Datos)

**Ubicación**: `src/Repositories/EstadisticasRepository.php`

#### Funciones Principales:

```php
// Consultas SQL optimizadas
- getTotalesPorEstado($filtros)          // Totales por estado (presente/ausente/tardanza)
- getInasistenciasPorDiaSemana($filtros) // Distribución por día de semana
- getInasistenciasPorJornada($filtros)   // Distribución por jornada (mañana/tarde/noche)
- getInasistenciasConExcusa($filtros)    // Inasistencias con/sin excusa
- getFechasInasistencia($idAprendiz, $filtros) // Fechas para calcular frecuencia
- getEstadisticasPorFicha($idFicha, $desde, $hasta) // Estadísticas agregadas por ficha
- getReportesPorAnalizar($filtros)       // Casos críticos para coordinadores
- getTopInasistentesPorFicha($idFicha, $desde, $hasta, $limit) // Top 5 inasistentes
```

#### Características Técnicas:

- **Prepared Statements**: Prevención de SQL injection
- **Transacciones**: Consistencia de datos
- **Filtros Dinámicos**: WHERE clauses construidos dinámicamente
- **Optimización**: Índices utilizados estratégicamente
- **Exclusión de Domingos**: Regla de negocio aplicada en SQL

### 2. EstadisticasService (Capa de Lógica de Negocio)

**Ubicación**: `src/Services/EstadisticasService.php`

#### Funciones Principales:

```php
// Servicios principales
- getEstadisticasAprendiz($filtros)     // Estadísticas detalladas por aprendiz(es)
- getEstadisticasFicha($idFicha, $desde, $hasta) // Estadísticas agregadas por ficha
- getReportesPorAnalizar($filtros)      // Casos marcados como críticos
- exportarDatos($filtros, $tipo)        // Datos tabulares para CSV

// Funciones de cálculo
- calcularEstadisticasAprendiz($totales, $excusas, $fechas, $periodos, $filtros)
- calcularPorcentajes($datos)
- calcularFrecuenciaInasistencias($fechas)
- detectarFlagsReporte($datosFicha, $inasistenciasDia)
- detectarFlagsReporteAprendiz($fechas, $inasistenciasDia, $porcentajes)
```

#### Reglas de Negocio Implementadas:

**🕐 Tardanzas:**
- **Mañana**: Inicio 06:00 → Tardanza si hora > 06:20
- **Tarde**: Inicio 12:00 → Tardanza si hora > 12:20
- **Noche**: Inicio 16:00/18:00 → Tardanza si hora > inicio + 20 minutos

**📅 Inasistencias:**
- Falta de registro en `asistencias` para (id_aprendiz, id_ficha, fecha)
- Exclusión automática de domingos
- Detección basada en calendario de la ficha

**📝 Excusas:**
- Tabla `anomalias` con tipo = 'excusa'
- Validación: created_at máximo 3 días después de la fecha en asistencias
- Conteo como "inasistencia con excusa"

**📊 Porcentajes:**
- `asistencia = presentes / (presentes + ausentes + tardanzas) * 100`
- `inasistencia = ausentes / (presentes + ausentes + tardanzas) * 100`

**🔄 Frecuencia de Inasistencias:**
- Cálculo del promedio de días entre inasistencias
- Algoritmo: Diferencia entre fechas ordenadas → promedio

**🚩 Reportes por Analizar:**
Marcado automático cuando:
- Más del 40% de inasistencias en un día de la semana
- Frecuencia promedio de inasistencia ≤ 3 días
- Caída de porcentaje de asistencia > 15% respecto al período anterior

### 3. EstadisticasController (Capa de API)

**Ubicación**: `src/Controllers/EstadisticasController.php`

#### Endpoints REST Implementados:

**📊 Estadísticas por Aprendiz**
```http
GET /api/estadisticas/aprendiz?id_aprendiz=1&id_ficha=2&fecha_desde=2024-01-01&fecha_hasta=2024-12-31&jornada=mañana
```

**Parámetros:**
- `id_aprendiz` (obligatorio): ID del aprendiz (puede ser array)
- `id_ficha` (opcional): Filtrar por ficha específica
- `fecha_desde` (opcional): Fecha inicio del período
- `fecha_hasta` (opcional): Fecha fin del período
- `jornada` (opcional): mañana|tarde|noche

**Respuesta JSON:**
```json
{
  "success": true,
  "data": {
    "id_aprendiz": 1,
    "total_asistencias": 45,
    "total_inasistencias": 5,
    "total_tardanzas": 2,
    "total_inasistencias_con_excusa": 1,
    "porcentaje_asistencia": 85.7,
    "porcentaje_inasistencia": 9.5,
    "frecuencia_inasistencia_dias_promedio": 7.2,
    "inasistencias_por_semana": {"2024-W01": 1, "2024-W02": 2},
    "inasistencias_por_mes": {"2024-01": 3, "2024-02": 2},
    "inasistencias_por_dia_semana": {"lunes": 2, "martes": 1},
    "inasistencias_por_jornada": {"mañana": 3, "tarde": 2},
    "flags": {
      "reporte_por_analizar": false,
      "motivos": []
    }
  }
}
```

**📁 Estadísticas por Ficha**
```http
GET /api/estadisticas/ficha?id_ficha=1&fecha_desde=2024-01-01&fecha_hasta=2024-12-31
```

**Parámetros:**
- `id_ficha` (obligatorio): ID de la ficha
- `fecha_desde` (obligatorio): Fecha inicio
- `fecha_hasta` (obligatorio): Fecha fin

**Respuesta JSON:**
```json
{
  "success": true,
  "data": {
    "id_ficha": 1,
    "totales": {
      "total_registros": 500,
      "presentes": 425,
      "ausentes": 50,
      "tardanzas": 25
    },
    "porcentajes": {
      "asistencia": 85.0,
      "inasistencia": 10.0,
      "tardanza": 5.0
    },
    "distribucion_dia_semana": {
      "lunes": 12,
      "martes": 8,
      "miercoles": 15
    },
    "distribucion_jornada": {
      "mañana": 20,
      "tarde": 18,
      "noche": 12
    },
    "top_inasistentes": [
      {
        "nombre": "Juan",
        "apellido": "Pérez",
        "total_inasistencias": 8,
        "inasistencias_con_excusa": 2
      }
    ],
    "flags": {
      "reporte_por_analizar": false,
      "motivos": []
    }
  }
}
```

**🚨 Reportes por Analizar**
```http
GET /api/estadisticas/reportes?tipo_entidad=aprendiz&id_aprendiz=1&fecha_desde=2024-01-01&fecha_hasta=2024-12-31
```

**Parámetros:**
- `tipo_entidad` (opcional): aprendiz|ficha
- `id_aprendiz` (opcional): ID del aprendiz
- `id_ficha` (opcional): ID de la ficha
- `fecha_desde` (opcional): Fecha inicio
- `fecha_hasta` (opcional): Fecha fin

**📤 Exportar a CSV**
```http
GET /api/estadisticas/exportar?tipo=aprendiz&id_aprendiz=1&fecha_desde=2024-01-01&fecha_hasta=2024-12-31
```

**Parámetros:**
- `tipo` (obligatorio): aprendiz|ficha
- `id_aprendiz` (opcional): Para tipo=aprendiz
- `id_ficha` (opcional): Para tipo=ficha
- Filtros de fecha

### 4. Vista Web (Dashboard)

**Ubicación**: `views/estadisticas/index.php`

#### Características:

- **Interfaz Moderna**: Diseño responsive con CSS Grid
- **4 Opciones Principales**:
  - 📊 Estadísticas por Aprendiz
  - 👥 Estadísticas por Ficha
  - 🚨 Reportes por Analizar
  - 📥 Exportar Datos

- **Filtros Dinámicos**:
  - Rango de fechas
  - Selección de ficha
  - Búsqueda de aprendices (carga AJAX)

- **Visualización Interactiva**:
  - Tarjetas con métricas principales
  - Tablas para datos detallados
  - Mensajes de carga y error
  - Exportación directa a CSV

---

## 🔐 Control de Acceso (RBAC)

### Permisos por Rol:

**👑 Administrador:**
- ✅ Ver estadísticas de todas las fichas
- ✅ Ver estadísticas de todos los aprendices
- ✅ Acceder a reportes críticos
- ✅ Exportar datos a CSV

**👨‍🏫 Coordinador:**
- ✅ Ver estadísticas de todas las fichas
- ✅ Ver estadísticas de todos los aprendices
- ✅ Acceder a reportes críticos
- ✅ Exportar datos a CSV

**👨‍🎓 Instructor:**
- ✅ Ver estadísticas solo de fichas asignadas
- ✅ Ver estadísticas de aprendices en sus fichas
- ❌ No puede ver reportes críticos
- ✅ Exportar datos de sus fichas

### Validaciones Implementadas:

```php
// En EstadisticasController
private function validarPermisosEstadisticas(array $user): bool
private function validarAccesoFicha(array $user, int $fichaId): bool
private function obtenerFichasPermitidas(array $user): array
```

---

## 🗄️ Estructura de Base de Datos

### Tabla `anomalias` (Nueva)

```sql
CREATE TABLE anomalias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_asistencia INT NOT NULL,
    tipo ENUM('excusa','correccion','observacion') NOT NULL DEFAULT 'excusa',
    motivo TEXT NOT NULL,
    documento_soporte VARCHAR(255) DEFAULT NULL,
    registrado_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_asistencia) REFERENCES asistencias(id) ON DELETE CASCADE,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE RESTRICT,

    INDEX idx_anomalias_id_asistencia (id_asistencia),
    INDEX idx_anomalias_tipo (tipo),
    INDEX idx_anomalias_registrado_por (registrado_por),
    INDEX idx_anomalias_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tablas Utilizadas:

- **`asistencias`**: Registros de asistencia diaria
- **`aprendices`**: Información de aprendices
- **`fichas`**: Información de fichas formativas
- **`ficha_aprendiz`**: Relación ficha-aprendiz
- **`usuarios`**: Instructores y administradores
- **`anomalias`**: Excusas y correcciones

---

## 🚀 Guía de Uso

### 1. Acceso al Módulo

```php
// URL de acceso
GET /estadisticas

// Desde el menú principal o barra de navegación
```

### 2. Usar Estadísticas por Aprendiz

```javascript
// Seleccionar opción "Estadísticas por Aprendiz"
// Configurar filtros:
// - Fecha desde/hasta
// - Seleccionar ficha
// - Seleccionar aprendiz
// Hacer clic en "Buscar"

// Resultado: Métricas detalladas del aprendiz
```

### 3. Usar Estadísticas por Ficha

```javascript
// Seleccionar opción "Estadísticas por Ficha"
// Configurar filtros:
// - Fecha desde/hasta
// - Seleccionar ficha
// Hacer clic en "Buscar"

// Resultado: Estadísticas agregadas + top 5 inasistentes
```

### 4. Ver Reportes Críticos

```javascript
// Solo para Admin/Coordinador
// Seleccionar opción "Reportes por Analizar"
// Configurar filtros opcionales
// Hacer clic en "Buscar"

// Resultado: Lista de casos que requieren atención
```

### 5. Exportar Datos

```javascript
// Seleccionar opción "Exportar Datos"
// Configurar filtros
// Hacer clic en "Buscar"
// Hacer clic en "Descargar CSV"

// Resultado: Archivo CSV descargado automáticamente
```

---

## 🔧 Configuración y Dependencias

### Rutas Registradas

**Archivo**: `public/index.php`

```php
// Rutas web
'GET' => [
    '/estadisticas' => [
        'controller' => EstadisticasController::class,
        'action' => 'index',
        'middleware' => ['auth']
    ],
    // ... otras rutas
]

// Rutas API
'/api/estadisticas/aprendiz' => [...],
'/api/estadisticas/ficha' => [...],
'/api/estadisticas/reportes' => [...],
'/api/estadisticas/exportar' => [...],
```

### Dependencias Inyectadas

```php
// En index.php - inicialización
$estadisticasRepository = new EstadisticasRepository();
$estadisticasService = new EstadisticasService($estadisticasRepository);

// Inyección en controlador
$controller = new EstadisticasController(
    $estadisticasService,
    $authService,
    $fichaRepository
);
```

---

## 🧪 Testing y Validación

### Casos de Prueba Recomendados:

1. **Estadísticas por Aprendiz**:
   - Aprendiz con asistencias perfectas
   - Aprendiz con múltiples inasistencias
   - Aprendiz con excusas registradas
   - Filtros por fecha y jornada

2. **Estadísticas por Ficha**:
   - Ficha con alta asistencia
   - Ficha con problemas de asistencia
   - Verificación de top 5 inasistentes

3. **Reportes por Analizar**:
   - Casos que cumplen criterios de alerta
   - Filtros por entidad específica

4. **Exportación CSV**:
   - Formato correcto de datos
   - Headers apropiados
   - Encoding UTF-8

### Validaciones de Seguridad:

- ✅ Autenticación requerida
- ✅ Autorización por roles
- ✅ Rate limiting en APIs
- ✅ Validación de parámetros
- ✅ Sanitización de entradas

---

## 📈 Métricas de Implementación

### Estadísticas del Módulo:

- **Archivos PHP**: 3 (Repository, Service, Controller)
- **Archivos de Vista**: 1 (Dashboard HTML/JS/CSS)
- **Líneas de Código**:
  - Repository: ~250 líneas
  - Service: ~300 líneas
  - Controller: ~400 líneas
  - Vista: ~350 líneas
  - **Total**: ~1,300 líneas

### Endpoints API: 4
### Funciones de Negocio: 15+
### Reglas de Negocio: 8
### Validaciones de Seguridad: 6

---

## 🔄 Próximas Mejoras Sugeridas

### Funcionalidades Adicionales:

1. **📊 Gráficos Interactivos**
   - Charts.js para visualizaciones
   - Gráficos de líneas para tendencias
   - Gráficos circulares para distribuciones

2. **📧 Notificaciones Automáticas**
   - Alertas por email para casos críticos
   - Recordatorios automáticos a coordinadores

3. **📱 Dashboard Móvil**
   - Optimización para dispositivos móviles
   - PWA capabilities

4. **📈 Análisis Predictivo**
   - Machine learning básico
   - Predicción de riesgo de deserción

5. **🔍 Búsqueda Avanzada**
   - Filtros por múltiples criterios
   - Búsqueda full-text en motivos de excusas

### Optimizaciones Técnicas:

1. **Cache de Consultas**
   - Redis para resultados frecuentes
   - Cache de estadísticas por períodos

2. **Procesamiento Asíncrono**
   - Jobs para cálculos pesados
   - Queue system para reportes

3. **Base de Datos**
   - Particionamiento por fechas
   - Optimización de índices

---

## 📞 Soporte y Mantenimiento

### Logs de Error:
```
error_log("Error en EstadisticasService::getEstadisticasAprendiz: " . $e->getMessage());
```

### Monitoreo:
- **Performance**: Queries SQL optimizadas
- **Security**: Rate limiting y validaciones
- **Availability**: Manejo de excepciones completo

### Documentación de API:
- Endpoints documentados inline
- Ejemplos de request/response
- Códigos de error consistentes

---

**✅ Implementación Completada y Documentada**

*Desarrollado siguiendo las mejores prácticas de PHP, arquitectura limpia y principios SOLID.*
