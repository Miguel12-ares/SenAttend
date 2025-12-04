Walkthrough: Módulo de Analítica y Reportes para Administrativos
Resumen de la Implementación
Se ha implementado exitosamente un módulo completo de analítica y reportes para usuarios con rol administrativo en el sistema SenAttend. El módulo permite generar reportes estadísticos detallados de asistencia en formato Excel, con análisis semanal y mensual.

Archivos Creados
Backend - Repositorios
AnalyticsRepository.php
Repositorio especializado en consultas analíticas complejas con los siguientes métodos:

getAttendanceStatsByFicha()
: Estadísticas generales de asistencia por ficha
getAttendanceStatsByAprendiz()
: Estadísticas detalladas por cada aprendiz
getTardinessPatterns()
: Patrones de tardanzas agrupados por día de la semana
getJustifiedTardiness()
: Tardanzas justificadas mediante tabla de anomalías
getAverageEntryTime()
: Media de hora de ingreso
getAbsencesByDay()
: Ausencias agrupadas por día de la semana
getProblematicStudents()
: Aprendices con problemas recurrentes de asistencia
Características técnicas:

Consultas SQL optimizadas con JOINs eficientes
Cálculo automático de porcentajes
Manejo de días hábiles (lunes a viernes)
Prepared statements para seguridad
Backend - Servicios
AnalyticsService.php
Servicio de lógica de negocio que coordina la generación de reportes:

generateWeeklyReport()
: Genera datos para reporte semanal
generateMonthlyReport()
: Genera datos para reporte mensual
getFichasDisponibles()
: Obtiene fichas activas del sistema
calculateAttendancePercentage()
: Calcula porcentajes de asistencia
identifyProblematicStudents()
: Identifica aprendices con problemas
formatForExcel()
: Formatea datos para exportación a Excel
Principios SOLID aplicados:

Single Responsibility: Cada método tiene una responsabilidad única
Dependency Injection: Recibe repositorios vía constructor
Open/Closed: Extensible para nuevos tipos de reportes
Backend - Controladores
AnalyticsController.php
Controlador HTTP que maneja las peticiones del módulo:

index()
: Vista principal del módulo
generateWeeklyReport()
: Endpoint AJAX para reporte semanal
generateMonthlyReport()
: Endpoint AJAX para reporte mensual
generateExcelFile()
: Genera archivo Excel con múltiples hojas
populateSheet()
: Puebla hojas de Excel con estilos SENA
Seguridad implementada:

Validación de permisos (solo admin y administrativo)
Protección CSRF
Validación de entrada
Manejo de errores robusto
Frontend - Vistas
index.php
Vista principal del módulo con interfaz intuitiva:

Selector de tipo de reporte (semanal/mensual)
Búsqueda y selección de fichas
Opciones de período personalizables
Información sobre estadísticas incluidas
Sin CSS inline (todo en archivo separado)
Características UX:

Diseño responsive
Iconos Font Awesome
Mensajes de estado claros
Formulario validado
Frontend - Estilos
analytics.css
Estilos dedicados siguiendo el diseño SENA:

Paleta de colores verde SENA (#39A900)
Animaciones suaves
Cards con hover effects
Diseño responsive
Estados de carga visuales
Componentes estilizados:

Selector de tipo de reporte con radio buttons visuales
Formularios con validación visual
Botones con estados de carga
Alertas con animaciones
Cards informativos
Frontend - JavaScript
analytics.js
Lógica del lado del cliente:

Manejo de cambio de tipo de reporte
Búsqueda en tiempo real de fichas
Peticiones AJAX para generación de reportes
Estados de carga del botón
Descarga automática de archivos
Sistema de alertas
Características técnicas:

Código modular con IIFE
Event listeners organizados
Manejo de errores
Feedback visual inmediato
Configuración del Sistema
Rutas Añadidas
GET Routes
'/analytics' => [
    'controller' => \App\Controllers\AnalyticsController::class,
    'action' => 'index',
    'middleware' => ['auth']
]
POST Routes
'/analytics/generar-semanal' => [
    'controller' => \App\Controllers\AnalyticsController::class,
    'action' => 'generateWeeklyReport',
    'middleware' => ['auth']
],
'/analytics/generar-mensual' => [
    'controller' => \App\Controllers\AnalyticsController::class,
    'action' => 'generateMonthlyReport',
    'middleware' => ['auth']
]
Permisos Configurados
GET Permissions
'/analytics' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO]
POST Permissions
'/analytics/generar-semanal' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO],
'/analytics/generar-mensual' => [ROLE_ADMIN, ROLE_ADMINISTRATIVO]
Inyección de Dependencias
Se configuró la inyección de dependencias en 
public/index.php
:

} elseif ($controllerClass === \App\Controllers\AnalyticsController::class) {
    $analyticsRepository = new \App\Repositories\AnalyticsRepository();
    $asistenciaRepository = new \App\Repositories\AsistenciaRepository();
    $anomaliaRepository = new \App\Repositories\AnomaliaRepository();
    $fichaRepository = new \App\Repositories\FichaRepository();
    $analyticsService = new \App\Services\AnalyticsService(
        $analyticsRepository,
        $asistenciaRepository,
        $anomaliaRepository,
        $fichaRepository
    );
    $excelExportService = new \App\Gestion_reportes\Services\ExcelExportService();
    $controller = new $controllerClass(
        $authService,
        $session,
        $analyticsService,
        $excelExportService
    );
}
Dashboard Card
Se añadió una card en el dashboard administrativo:

<!-- Analítica y Reportes (Admin y Administrativo) -->
<div class="action-card-sena">
    <div class="action-icon-sena">
        <i class="fas fa-chart-line"></i>
    </div>
    <h4>Analítica y Reportes</h4>
    <p>Generar reportes estadísticos de asistencia semanales y mensuales.</p>
    <div class="action-buttons">
        <a href="/analytics" class="btn-sena">
            <i class="fas fa-file-excel"></i>
            Ver Analítica
        </a>
    </div>
</div>
Funcionalidades Implementadas
Reporte Semanal
Datos incluidos:

Resumen general de la ficha
Estadísticas por aprendiz
Porcentaje de asistencia
Media de hora de ingreso
Patrones de tardanzas por día
Aprendices problemáticos
Tardanzas justificadas
Período: Últimos 7 días (personalizable)

Reporte Mensual
Datos incluidos:

Todos los datos del reporte semanal
Análisis extendido del mes completo
Estadísticas acumuladas
Período: Mes completo (personalizable por mes y año)

Formato Excel
Estructura del archivo:

Hoja 1 - Resumen General: Métricas clave de la ficha
Hoja 2 - Estadísticas Aprendices: Detalle por cada aprendiz
Hoja 3 - Aprendices Problemáticos: Lista de aprendices con problemas
Hoja 4 - Patrones Tardanzas: Análisis de tardanzas por día
Características:

Formato profesional con colores SENA
Headers en verde (#39A900)
Columnas autoajustadas
Listo para imprimir o compartir
Métricas Calculadas
Por Ficha
Total de aprendices activos
Días registrados en el período
Total de presentes, ausentes y tardanzas
Porcentaje de asistencia
Porcentaje de ausencias
Porcentaje de tardanzas
Media de hora de ingreso
Por Aprendiz
Días registrados
Presentes, ausentes y tardanzas
Porcentaje de asistencia individual
Promedio de hora de ingreso
Identificación de problemas recurrentes
Análisis Adicional
Patrones de tardanzas por día de la semana
Ausencias agrupadas por día
Tardanzas justificadas vía anomalías
Aprendices con problemas críticos
Arquitectura y Principios SOLID
Single Responsibility Principle (SRP)
Repository: Solo consultas a base de datos
Service: Solo lógica de negocio
Controller: Solo manejo de HTTP
Open/Closed Principle (OCP)
Extensible para nuevos tipos de reportes sin modificar código existente
Fácil añadir nuevas métricas
Dependency Inversion Principle (DIP)
Dependencias inyectadas vía constructor
Fácil testing y mantenimiento
Separation of Concerns
CSS separado en archivo dedicado
JavaScript separado en archivo dedicado
Vistas sin lógica de negocio
Testing Recomendado
1. Acceso al Módulo
 Login como usuario administrativo
 Verificar que aparece card "Analítica y Reportes" en dashboard
 Click en card y verificar acceso a /analytics
 Verificar que usuarios instructor no tienen acceso
2. Generación de Reporte Semanal
 Seleccionar una ficha con datos
 Seleccionar "Reporte Semanal"
 Click en "Generar Reporte"
 Verificar descarga de archivo .xlsx
 Abrir archivo y validar:
Hoja de resumen general
Hoja de estadísticas por aprendiz
Datos correctos y formateados
3. Generación de Reporte Mensual
 Seleccionar una ficha
 Seleccionar "Reporte Mensual"
 Seleccionar mes y año
 Click en "Generar Reporte"
 Verificar descarga y contenido
4. Validaciones
 Intentar generar reporte sin seleccionar ficha
 Verificar mensaje de error
 Probar búsqueda de fichas
 Verificar cambio entre tipos de reporte
Próximos Pasos Sugeridos
Agregar Gráficos: Integrar gráficos visuales en los reportes Excel
Historial: Implementar historial de reportes generados
Filtros Avanzados: Añadir filtros por instructor, programa, etc.
Exportar PDF: Opción adicional para exportar en PDF
Reportes Programados: Generación automática periódica
Comparativas: Comparar períodos diferentes
Conclusión
El módulo de Analítica y Reportes ha sido implementado exitosamente siguiendo:

✅ Arquitectura MVC
✅ Principios SOLID
✅ Separación de CSS y JavaScript
✅ Sistema de permisos RBAC
✅ Validaciones de seguridad
✅ Diseño responsive
✅ Código documentado

El módulo está listo para ser probado y utilizado en producción.

