# 📊 Diagrama de Arquitectura - Módulo de Estadísticas

## Arquitectura de 3 Capas Implementada

```
┌─────────────────────────────────────────────────────────────┐
│                    🖥️ CAPA DE PRESENTACIÓN                  │
│  (Controller + Views)                                       │
├─────────────────────────────────────────────────────────────┤
│  📋 EstadisticasController.php                             │
│  ├── index()        → Vista dashboard web                  │
│  ├── aprendiz()     → GET /api/estadisticas/aprendiz       │
│  ├── ficha()        → GET /api/estadisticas/ficha          │
│  ├── reportes()     → GET /api/estadisticas/reportes       │
│  └── exportar()     → GET /api/estadisticas/exportar       │
│                                                             │
│  📄 views/estadisticas/index.php                           │
│  ├── Dashboard interactivo con filtros                     │
│  ├── AJAX calls a APIs                                     │
│  └── Exportación CSV directa                               │
└─────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────┐
│                 🧠 CAPA DE LÓGICA DE NEGOCIO                 │
│  (Service)                                                  │
├─────────────────────────────────────────────────────────────┤
│  🔧 EstadisticasService.php                                │
│  ├── getEstadisticasAprendiz()   → Calcula métricas        │
│  ├── getEstadisticasFicha()      → Agrega por ficha        │
│  ├── getReportesPorAnalizar()    → Detecta casos críticos  │
│  ├── exportarDatos()             → Prepara datos CSV       │
│  ├── calcularPorcentajes()       → % asistencia/inasistencia│
│  ├── calcularFrecuenciaInasistencias() → Promedio días     │
│  └── detectarFlagsReporte()      → Alertas automáticas     │
│                                                             │
│  📏 Reglas de Negocio Implementadas:                       │
│  ├── Tardanzas: 06:00-06:20, 12:00-12:20, etc.             │
│  ├── Inasistencias: Detección automática                   │
│  ├── Excusas: Tabla anomalias, validación 3 días          │
│  ├── Porcentajes: Cálculos precisos                       │
│  ├── Frecuencia: Promedio entre fechas                     │
│  └── Alertas: >40% día, ≤3 días, >15% caída               │
└─────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────┐
│                   💾 CAPA DE ACCESO A DATOS                 │
│  (Repository)                                              │
├─────────────────────────────────────────────────────────────┤
│  🗃️ EstadisticasRepository.php                            │
│  ├── getTotalesPorEstado()       → SELECT COUNT GROUP BY  │
│  ├── getInasistenciasPorDiaSemana() → GROUP BY DAYOFWEEK  │
│  ├── getInasistenciasPorJornada() → CASE WHEN TIME(hora)  │
│  ├── getInasistenciasConExcusa()  → LEFT JOIN anomalias    │
│  ├── getFechasInasistencia()      → SELECT fecha ORDER BY  │
│  ├── getEstadisticasPorFicha()    → SUM(CASE WHEN...)      │
│  ├── getReportesPorAnalizar()     → Casos críticos         │
│  └── getTopInasistentesPorFicha() → TOP 5 ORDER BY DESC    │
│                                                             │
│  🔍 Optimizaciones SQL:                                    │
│  ├── Prepared Statements → Seguridad                       │
│  ├── Índices estratégicos → Performance                    │
│  ├── WHERE dinámicos → Filtros flexibles                   │
│  ├── Exclusión domingos → Regla de negocio                 │
│  └── LEFT JOIN anomalias → Excusas opcionales              │
└─────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────┐
│                    🗄️ BASE DE DATOS                          │
├─────────────────────────────────────────────────────────────┤
│  📊 Tablas Utilizadas:                                     │
│                                                             │
│  asistencias (principal)                                    │
│  ├── id_aprendiz, id_ficha, fecha, hora, estado            │
│  ├── UNIQUE KEY (id_aprendiz, id_ficha, fecha)             │
│  └── Índices: fecha, id_aprendiz, id_ficha, estado         │
│                                                             │
│  anomalias (excusas)                                        │
│  ├── id_asistencia, tipo, motivo, documento_soporte        │
│  ├── registrado_por, created_at                            │
│  └── FK: id_asistencia → asistencias.id                    │
│                                                             │
│  aprendices, fichas, ficha_aprendiz, usuarios              │
│  └── Relaciones N:1 y N:M según requerimientos             │
└─────────────────────────────────────────────────────────────┘
```

## 🔄 Flujo de Datos

### 1. Solicitud desde Frontend
```
Usuario → Vista Web → AJAX → Controller → Service → Repository → Base de Datos
```

### 2. Procesamiento de Estadísticas
```
Datos Crudos → Cálculos de Negocio → Formateo → JSON Response → Frontend
```

### 3. Exportación CSV
```
Datos Tabulares → Array PHP → CSV String → Descarga Automática
```

## 🔐 Control de Acceso (RBAC)

### Por Rol de Usuario:

```
┌─────────────────┬─────────────┬──────────────┬─────────────┐
│ Funcionalidad   │  Admin      │ Coordinador  │ Instructor  │
├─────────────────┼─────────────┼──────────────┼─────────────┤
│ Ver Estadísticas│     ✅      │      ✅      │     ✅      │
│ Todas las Fichas│     ✅      │      ✅      │     ❌      │
│ Solo Mis Fichas │     ✅      │      ✅      │     ✅      │
│ Reportes Críticos│    ✅      │      ✅      │     ❌      │
│ Exportar CSV    │     ✅      │      ✅      │     ✅      │
└─────────────────┴─────────────┴──────────────┴─────────────┘
```

## 📊 Endpoints API Documentados

### GET /api/estadisticas/aprendiz
**Propósito**: Estadísticas detalladas de uno o varios aprendices
**Parámetros**: id_aprendiz, id_ficha, fecha_desde, fecha_hasta, jornada
**Respuesta**: Métricas completas + frecuencia + patrones + flags

### GET /api/estadisticas/ficha
**Propósito**: Estadísticas agregadas por ficha formativa
**Parámetros**: id_ficha, fecha_desde, fecha_hasta
**Respuesta**: Totales + porcentajes + top inasistentes + flags

### GET /api/estadisticas/reportes
**Propósito**: Casos marcados como "reporte por analizar"
**Parámetros**: tipo_entidad, id_aprendiz, id_ficha, fecha_desde, fecha_hasta
**Respuesta**: Lista de casos críticos (solo Admin/Coordinador)

### GET /api/estadisticas/exportar
**Propósito**: Datos tabulares para exportación CSV
**Parámetros**: tipo, id_aprendiz/id_ficha, fecha_desde, fecha_hasta
**Respuesta**: Array de datos listos para CSV

## 🎯 Métricas de Implementación

### Código por Capa:
- **Repository**: ~250 líneas (consultas SQL optimizadas)
- **Service**: ~300 líneas (lógica de negocio + cálculos)
- **Controller**: ~400 líneas (endpoints REST + validaciones)
- **Vista**: ~350 líneas (dashboard interactivo)
- **Total**: ~1,300 líneas de código funcional

### Funcionalidades Implementadas:
- ✅ 8 consultas SQL optimizadas
- ✅ 15+ funciones de cálculo
- ✅ 8 reglas de negocio
- ✅ 4 endpoints REST completos
- ✅ 6 validaciones de seguridad
- ✅ Dashboard web responsive
- ✅ Exportación CSV automática
- ✅ Control de acceso RBAC
- ✅ Documentación completa

---

**📝 Documento generado automáticamente - Módulo Estadísticas SENAttend**
